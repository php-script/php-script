<?php

declare(strict_types=1);

use PhpScript\Exceptions\LexerException;

it('creates exception with correct message', function (): void {
    $remainingCode = '@#$%';
    $exception = LexerException::unknownCharOrSyntaxError($remainingCode);

    expect($exception)->toBeInstanceOf(LexerException::class)
        ->and($exception->getMessage())->toBe('Unknown char or syntax error: @#$%');
});
