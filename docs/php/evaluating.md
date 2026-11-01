---
title: Evaluating and Running
parent: PHP
nav_order: 2
layout: default
---

# Evaluating and Running
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

The engine offers three ways to run PHP Script code.

| Method | Input | Returns |
|--------|-------|---------|
| `execute($script)` | a script | the echo output as string |
| `run($script)` | a script | an `ExecutionResult` with output, returned value and operation count |
| `evaluate($expression)` | a single expression | the typed value of the expression |

## Expressions

`evaluate()` computes one expression and returns its PHP value: `bool`, `int`, `float`,
`string`, `null`, a list (`array`) or a map (`stdClass`). Use it for conditions and mappings.

```php
use PhpScript\Core\Engine;

$engine = new Engine;
$engine->set('data', ['amount' => 150, 'customer' => ['name' => 'Ada']])
    ->set('now', new DateTimeImmutable);

$engine->evaluate('data.amount > 100');        // true
$engine->evaluate('data.amount + 50');         // 200 (int)
$engine->evaluate('data.amount * 1.5');        // 225.0 (float)
$engine->evaluate('data.customer.name');       // 'Ada'
$engine->evaluate("now.modify('+14 days')");   // DateTimeImmutable
```

{: .note }
Expressions cannot assign, but they can call methods of context objects. `evaluate()` is only
free of side effects when the methods you expose are.

Statements are not expressions. `echo`, assignments, `++`/`--`, loops and `return` throw an
`EngineException`, as do incomplete expressions like `data.amount >`.

## Returning values from scripts

`run()` executes a script and returns the value of its `return` statement next to the echo
output. A script without `return` returns `null`.

```php
$result = $engine->run(<<<'SCRIPT'
    echo 'calculating'
    net = data.amount
    return {net: net, gross: net * 1.19}
    SCRIPT);

$result->output;     // 'calculating'
$result->value;      // stdClass {net: 150, gross: 178.5}
$result->operations; // 3
```

`execute()` keeps working as before and returns only the output.
