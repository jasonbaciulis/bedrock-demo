<?php

declare(strict_types=1);

namespace App\Modifiers;

use Statamic\Modifiers\Modifier;
use Statamic\Modifiers\ModifierException;

/**
 * Antlers rethrows the exception when app.debug is on, and otherwise logs it and keeps the value.
 */
final class Required extends Modifier
{
    /**
     * @param  array<int, string>  $params
     */
    public function index(mixed $value, array $params): mixed
    {
        if (blank($value)) {
            throw new ModifierException($params[0] ?? 'A required value is missing.');
        }

        return $value;
    }
}
