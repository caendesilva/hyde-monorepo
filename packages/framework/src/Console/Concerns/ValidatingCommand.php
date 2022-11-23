<?php

declare(strict_types=1);

namespace Hyde\Console\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use LaravelZero\Framework\Commands\Command;
use RuntimeException;
use function ucfirst;

/**
 * An extended Command class that provides validation methods.
 *
 * @see \Hyde\Framework\Testing\Feature\ValidatingCommandTest
 */
class ValidatingCommand extends Command
{
    /** @var int How many times can the validation loop run? Guards against infinite loops. */
    protected final const RETRY_COUNT = 10;

    /**
     * Ask for a CLI input value until we pass validation rules.
     *
     * @param  string  $name
     * @param  string  $message
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $rules
     * @param  mixed|null  $default
     * @param  bool  $isBeingRetried
     * @return mixed
     *
     * @throws RuntimeException
     */
    public function askWithValidation(
        string $name,
        string $message,
        Arrayable|array $rules = [],
        mixed $default = null,
        bool $isBeingRetried = false
    ): mixed {
        static $tries = 0;
        if (! $isBeingRetried) {
            $tries = 0;
        }

        if ($rules instanceof Arrayable) {
            $rules = $rules->toArray();
        }

        $answer = $this->ask(ucfirst($message), $default);
        $factory = app(ValidationFactory::class);
        $validator = $factory->make([$name => $answer], [$name => $rules]);

        if ($validator->passes()) {
            return $answer;
        }

        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        $tries++;

        if ($tries >= self::RETRY_COUNT) {
            throw new RuntimeException(sprintf("Too many validation errors trying to validate '$name' with rules: [%s]", implode(', ', $rules)));
        }

        return $this->askWithValidation($name, $message, $rules, isBeingRetried: true);
    }
}
