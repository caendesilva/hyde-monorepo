<?php

declare(strict_types=1);

/**
 * @internal
 */

use Hyde\Pages\Concerns\HydePage;
use Illuminate\Support\Str;

require_once __DIR__.'/../../../vendor/autoload.php';

$timeStart = microtime(true);

// Arguments supported:
// --class=HydePage
// --instanceVariableName=$page
// --outputFile=api-docs.md

// Get argument list from command line
$requiredArguments = ['--class', '--instanceVariableName', '--outputFile'];
$defaultArguments['--class'] = HydePage::class;
$defaultArguments['--instanceVariableName'] = '$page';
$defaultArguments['--outputFile'] = 'api-docs.md';
$options = parseArguments($requiredArguments, $defaultArguments);

$class = $options['--class'];
$instanceVariableName = $options['--instanceVariableName'];
$outputFile = $options['--outputFile'];

$reflection = new ReflectionClass($class);

$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
$fileName = (new ReflectionClass($class))->getFileName();

// Remove methods defined in traits or parent classes
$methods = array_filter($methods, function (ReflectionMethod $method) use ($fileName) {
    return $method->getFileName() === $fileName;
});

// Split methods into static and non-static

$staticMethods = array_filter($methods, function (ReflectionMethod $method) {
    return $method->isStatic();
});

$instanceMethods = array_filter($methods, function (ReflectionMethod $method) {
    return ! $method->isStatic();
});

$output = [];

// Generate static methods
foreach ($staticMethods as $method) {
    documentMethod($method, $output);
}

// Generate instance methods
foreach ($instanceMethods as $method) {
    documentMethod($method, $output);
}

// Assemble end time in milliseconds
$timeEnd = microtime(true);
$time = number_format(($timeEnd - $timeStart) * 1000, 2);
$metadata = sprintf('Generated by HydePHP DocGen script at %s in %sms', date('Y-m-d H:i:s'), $time);

// Join the output
$text = implode("\n", $output);
$startMarker = '<!-- Start generated docs for '.$class.' -->';
$metadataMarker = "<!-- $metadata -->";
$endMarker = '<!-- End generated docs for '.$class.' -->';
$classKebabName = Str::kebab(class_basename($class));
$text = "<section id=\"$classKebabName-methods\">\n\n$startMarker\n$metadataMarker\n\n$text\n$endMarker\n\n</section>\n";

// Run any post-processing
$text = postProcess($text);

// Output the documentation
echo $text;

// Save the documentation to a file
file_put_contents($outputFile, $text);

echo "\n\n\033[32mAll done in $time ms!\033[0m Convents saved to ".realpath($outputFile)."\n";

// Helpers

function documentMethod(ReflectionMethod $method, array &$output): void
{
    $template = <<<'MARKDOWN'
    #### `{{ $methodName }}()`
    
    {{ $description }}
    
    ```php
    // torchlight! {"lineNumbers": false}
    {{ $signature }}({{ $argList }}): {{ $returnType }}
    ```

    MARKDOWN;

    $staticSignatureTemplate = '{{ $className }}::{{ $methodName }}';
    $instanceSignatureTemplate = '{{ $instanceVariableName }}->{{ $methodName }}';

    $signatureTemplate = $method->isStatic() ? $staticSignatureTemplate : $instanceSignatureTemplate;

    if ($method->getName() === '__construct') {
        $signatureTemplate = '{{ $instanceVariableName }} = new {{ $className }}';
    }

    $methodName = $method->getName();
    $docComment = parsePHPDocs($method->getDocComment() ?: '');
    $description = $docComment['description'];

    global $class;
    $className = class_basename($class);

    $parameters = array_map(function (ReflectionParameter $parameter) {
        $name = '$'.$parameter->getName();
        if ($parameter->getType()) {
            if ($parameter->getType() instanceof ReflectionUnionType) {
                $type = implode('|', array_map(function (ReflectionNamedType $type) {
                    return $type->getName();
                }, $parameter->getType()->getTypes()));
            } else {
                $type = $parameter->getType()->getName();
            }
        } else {
            $type = 'mixed';
        }

        return trim($type.' '.$name);
    }, $method->getParameters());
    $returnType = $method->getReturnType() ? $method->getReturnType()->getName() : 'void';

    // If higher specificity return type is provided in docblock, use that instead
    if (isset($docComment['properties']['return'])) {
        $returnValue = $docComment['properties']['return'];
        // If there is a description, put it in a comment
        if (str_contains($returnValue, ' ')) {
            $exploded = explode(' ', $returnValue, 2);
            $type = $exploded[0];
            $comment = ' // '.$exploded[1];
            $returnValue = $type;
        } else {
            $comment = null;
        }
        $returnType = $returnValue.($comment ?? '');
    }

    $parameterDocs = [];
    // Map docblock params
    if (isset($docComment['properties']['params'])) {
        $newParams = array_map(function (string $param) use (&$parameterDocs) {
            $param = str_replace('  ', ' ', trim($param));
            $comment = $param;
            $param = explode(' ', $param, 3);
            $type = $param[0];
            $name = $param[1];
            if (isset($param[2])) {
                $parameterDocs[$type] = $comment;
            }

            return trim($type.' '.$name);
        }, $docComment['properties']['params']);
    }
    // If higher specificity argument types are provided in docblock, merge them with the actual types
    if (isset($newParams)) {
        foreach ($newParams as $index => $newParam) {
            if (isset($parameters[$index])) {
                $parameters[$index] = $newParam;
            }
        }
    }

    $argList = implode(', ', $parameters);

    $before = null;
    $beforeSignature = null;
    if ($parameterDocs) {
        if (count($parameterDocs) > 1) {
            $beforeSignature = 'FIX'.'ME: Support multiple parameter types';
        } else {
            $param = array_values($parameterDocs)[0];
            $beforeSignature = "/** @param $param */";
        }
    }

    global $instanceVariableName;
    $signature = ($beforeSignature ? $beforeSignature."\n" : '').str_replace(
            ['{{ $instanceVariableName }}', '{{ $methodName }}', '{{ $className }}'],
            [$instanceVariableName, $methodName, $className],
            $signatureTemplate
        );

    $replacements = [
        '{{ $signature }}' => $signature,
        '{{ $methodName }}' => e($methodName),
        '{{ $description }}' => e($description),
        '{{ $className }}' => e($className),
        '{{ $argList }}' => e($argList),
        '{{ $returnType }}' => ($returnType),
    ];
    $markdown = ($before ? $before."\n" : '').str_replace(array_keys($replacements), array_values($replacements), $template);

    // Throws
    if (isset($docComment['properties']['throws'])) {
        $markdown .= "\n";
        foreach ($docComment['properties']['throws'] as $throw) {
            $markdown .= e("- **Throws:** $throw\n");
        }
    }

    // Debug breakpoint
    if (str_contains($markdown, 'foo')) {
        // dd($markdown);
    }

    $output[] = $markdown;
}

function parsePHPDocs(string $comment): array
{
    // Normalize
    $comment = array_map(function (string $line) {
        return trim($line, " \t*/");
    }, explode("\n", $comment));

    $description = '';
    $properties = [];

    // Parse
    foreach ($comment as $line) {
        if (str_starts_with($line, '@')) {
            $propertyName = substr($line, 1, strpos($line, ' ') - 1);
            $propertyValue = substr($line, strpos($line, ' ') + 1);
            // If property allows multiple we add to subarray
            if ($propertyName === 'return') {
                $properties[$propertyName] = $propertyValue;
            } else {
                $name = str_ends_with($propertyName, 's') ? $propertyName : $propertyName.'s';
                $properties[$name][] = $propertyValue;
            }
        } else {
            $shouldAddNewline = empty($line);
            $description .= ($shouldAddNewline ? "\n\n" : '').ltrim($line.' ');
        }
    }

    return [
        'description' => trim($description) ?: 'No description provided.',
        'properties' => $properties,
    ];
}

function parseArguments(array $requiredArguments, array $defaultArguments): array
{
    global $argv;
    $arguments = $argv;
    array_shift($arguments);

    // Parse arguments
    $arguments = array_map(function (string $argument) {
        $argument = explode('=', $argument, 2);
        if (count($argument) === 1) {
            return [$argument[0], true];
        }

        return $argument;
    }, $arguments);

    // Convert to associative array
    $arguments = array_reduce($arguments, function (array $carry, array $argument) {
        $carry[$argument[0]] = $argument[1];

        return $carry;
    }, []);

    // Validate arguments
    foreach ($requiredArguments as $requiredArgument) {
        if (! isset($arguments[$requiredArgument])) {
            // throw new Exception("Missing required argument: $requiredArgument");
            // Set to default values
            $options[$requiredArgument] = $defaultArguments[$requiredArgument];
        } else {
            $options[$requiredArgument] = $arguments[$requiredArgument];
        }
    }

    return $options;
}

function postProcess(string $text): string
{
    // Unescape escaped code that will be escaped again
    $replace = ['`&lt;' => '`<', '&gt;`' => '>`'];
    $text = str_replace(array_keys($replace), array_values($replace), $text);

    return $text;
}
