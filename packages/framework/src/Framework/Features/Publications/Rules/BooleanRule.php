<?php

declare(strict_types=1);

namespace Hyde\Framework\Features\Publications\Rules;

use Illuminate\Contracts\Validation\InvokableRule;

use function in_array;

class BooleanRule implements InvokableRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure  $fail
     * @return void
     */
    public function __invoke($attribute, $value, $fail)
    {
        $acceptable = ['true', 'false', true, false, 0, 1, '0', '1'];

        if (! in_array($value, $acceptable, true)) {
            $fail('The :attribute must be true or false');
        }
    }
}
