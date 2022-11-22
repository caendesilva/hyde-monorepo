<?php

declare(strict_types=1);

namespace Hyde\Framework\Actions;

use Hyde\Framework\Actions\Interfaces\CreateActionInterface;
use Hyde\Framework\Concerns\InteractsWithDirectories;
use Hyde\Framework\Features\Publications\Models\PublicationField;
use Hyde\Framework\Features\Publications\Models\PublicationType;
use Hyde\Framework\Features\Publications\PublicationHelper;
use Illuminate\Support\Str;
use Rgasch\Collection\Collection;
use RuntimeException;

use function Safe\date;
use function Safe\file_put_contents;

/**
 * Scaffold a publication file.
 *
 * @see \Hyde\Framework\Testing\Feature\Actions\CreatesNewPublicationFileTest
 */
class CreatesNewPublicationFile implements CreateActionInterface
{
    use InteractsWithDirectories;

    protected string $result;

    public function __construct(
        protected PublicationType $pubType,
        protected Collection $fieldData,
        protected bool $force = false
    ) {
    }

    public function create(): void
    {
        $dir = ($this->pubType->getDirectory());
        $canonicalFieldName = $this->pubType->canonicalField;
        $canonicalFieldDefinition = $this->pubType->getFields()->filter(fn (PublicationField $field): bool => $field->name === $canonicalFieldName)->first() ?? throw new RuntimeException("Could not find field definition for '$canonicalFieldName'");
        $canonicalValue = $canonicalFieldDefinition->type !== 'array' ? $this->fieldData->{$canonicalFieldName} : $this->fieldData->{$canonicalFieldName}[0];
        $canonicalStr = Str::of($canonicalValue)->substr(0, 64);
        $slug = $canonicalStr->slug()->toString();
        $fileName = PublicationHelper::formatNameForStorage($slug);
        $outFile = "$dir/$fileName.md";
        if (file_exists($outFile) && ! $this->force) {
            throw new \InvalidArgumentException("File [$outFile] already exists");
        }

        $now = date('Y-m-d H:i:s');
        $output = "---\n";
        $output .= "__createdAt: {$now}\n";
        foreach ($this->fieldData as $name => $value) {
            /** @var PublicationField $fieldDefinition */
            $fieldDefinition = $this->pubType->getFields()->where('name', $name)->first();

            if ($fieldDefinition->type == 'text') {
                $output .= "{$name}: |\n";
                foreach ($value as $line) {
                    $output .= "  $line\n";
                }
                continue;
            }

            if ($fieldDefinition->type == 'array') {
                $output .= "{$name}:\n";
                foreach ($value as $item) {
                    $output .= "  - \"$item\"\n";
                }
                continue;
            }

            $output .= "{$name}: {$value}\n";
        }
        $output .= "---\n";
        $output .= "Raw MD text ...\n";

        $this->result = $output;
        echo "Saving publication data to [$outFile]\n";

        $this->needsParentDirectory($outFile);
        file_put_contents($outFile, $output);
    }

    public function getResult(): string
    {
        return $this->result;
    }
}
