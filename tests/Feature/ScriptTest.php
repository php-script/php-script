<?php

declare(strict_types=1);

use PhpScript\Core\Engine;

it('should handle NBSP char', function (): void {
    $phpScript = <<<'PHPSCRIPT'
echo 'USER: ' ~ currentUser ~ LINEBREAK

echo 'BEFORE: ' ~ task.assignee ~ LINEBREAK

task.assignee = currentUser

echo 'AFTER: ' ~ task.assignee ~ LINEBREAK
PHPSCRIPT;

    $task = new stdClass;
    $task->assignee = null;

    $user = 'John';

    $engine = new Engine;
    $engine->set('task', $task)
        ->set('currentUser', $user);
    $result = $engine->execute($phpScript);

    expect($result)->toBe(
        'USER: John' . PHP_EOL .
        'BEFORE: ' . PHP_EOL .
        'AFTER: John' . PHP_EOL
    );
});
