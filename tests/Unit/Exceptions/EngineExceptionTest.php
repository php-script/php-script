<?php

declare(strict_types=1);

use PhpScript\Exceptions\EngineException;

it('keeps the reason without prefix and source position', function (): void {
    $runtime = EngineException::runtimeError('Division by zero', 2, 3, 10, 1);
    $compile = EngineException::compileError('Invalid assignment target.', 1, 1, 0, 1);
    $call = EngineException::invalidFunctionCall('system', 1, 1, 0, 6);

    expect($runtime->getMessage())->toBe('Runtime error: Division by zero in line: 2, column: 3, offset: 10')
        ->and($runtime->reason)->toBe('Division by zero')
        ->and($compile->reason)->toBe('Invalid assignment target.')
        ->and($call->reason)->toBe('Invalid function call: system');
});

it('uses the message as reason by default', function (): void {
    expect(new EngineException('Something failed.')->reason)->toBe('Something failed.');
});
