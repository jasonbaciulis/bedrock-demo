<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Statamic\Facades\Antlers;
use Statamic\Modifiers\ModifierException;

function renderRequired(mixed $value): string
{
    return (string) Antlers::parse("{{ value | required('The value is missing.') }}", ['value' => $value], trusted: true);
}

test('a filled value passes through unchanged', function (): void {
    expect(renderRequired('Services'))->toBe('Services');
});

test('an empty value throws with the given message in debug mode', function (string $value): void {
    config(['app.debug' => true]);

    expect(fn (): string => renderRequired($value))
        ->toThrow(ModifierException::class, 'The value is missing.');
})->with(['empty string' => '', 'whitespace' => '   ']);

test('an empty value without a message throws a default message', function (): void {
    config(['app.debug' => true]);

    expect(fn (): string => (string) Antlers::parse('{{ value | required }}', ['value' => ''], trusted: true))
        ->toThrow(ModifierException::class, 'A required value is missing.');
});

test('an empty value is logged and rendered when debug mode is off', function (): void {
    config(['app.debug' => false]);
    Log::spy();

    expect(renderRequired(''))->toBeEmpty();

    Log::shouldHaveReceived('notice')->once()->with('Error in [required] modifier: The value is missing.');
});
