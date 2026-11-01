---
title: Linting
parent: PHP
nav_order: 5
layout: default
---

# Linting PHP Script
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Linter built-in

We have a linter built-in in the engine to support linting out of the box.

```php
try {
    $linted = $engine->linter($phpScript)->linted();
    return json_encode(['script' => $phpScript, 'linted' => $linted]);
} catch (EngineException $e) {
    die($e->getMessage());
}
```

With this you could update or persist the PHP Script code server-side in a unified way - no matter who is editing it.

## Diagnostics

`diagnostics()` returns a list of `LintDiagnostic` objects with `rule`, `message`, `line`,
`column`, `offset`, `length` and `severity` (`LintDiagnostic::SEVERITY_ERROR` or
`SEVERITY_WARNING`). `isValid()` is `true` when there are no errors; warnings do not make a
script invalid. The
linter works lazily: nothing is parsed before you ask for a result.

```php
$linter = $engine->linter($script);

foreach ($linter->diagnostics() as $diagnostic) {
    echo "{$diagnostic->line}:{$diagnostic->column} [{$diagnostic->rule}] {$diagnostic->message}\n";
}
```

Use `asExpression()` to lint a single expression as `Engine::evaluate()` expects it.

## Rules

| Rule | Checks |
|------|--------|
| `syntax` | The script can be tokenized and parsed. |
| `compile` | Only allowed functions are called and no reserved names are used. Active when an allowlist is known: always via `$engine->linter()`, or with `withAllowedFunctions()` on a standalone `LinterService`. |
| `infinite-loop` | Warns when a `for` loop without a condition (or with a constant `true` one) has no `break` or `return`. |

All rules are on by default. Turn one off with `withoutRule()`:

```php
$engine->linter($script)->withoutRule(LinterService::RULE_INFINITE_LOOP)->isValid();
```

`withoutRule()` and `withRule()` throw an `InvalidArgumentException` for unknown rule names.

The `infinite-loop` rule only finds obvious cases. Use an operation limit to stop every
endless loop at runtime.

