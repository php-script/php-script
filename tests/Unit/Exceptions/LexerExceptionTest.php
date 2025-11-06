<?php

declare(strict_types=1);

use PhpScript\Exceptions\LexerException;

it('creates exception with correct message', function (): void {
    $remainingCode = '@#$%';
    $exception = LexerException::unknownCharOrSyntaxError($remainingCode);

    expect($exception)->toBeInstanceOf(LexerException::class)
        ->and($exception->getMessage())->toBe('Unknown character or syntax error `@#$%` at line 0, column 0.');
});

it('creates exception with abbreviated token message', function (): void {
    $remainingCode = '@#$% ' . "\n" . ' foo.bar()';
    $exception = LexerException::unknownCharOrSyntaxError($remainingCode);

    expect($exception)->toBeInstanceOf(LexerException::class)
        ->and($exception->getMessage())->toBe('Unknown character or syntax error `@#$%` at line 0, column 0.');
});
