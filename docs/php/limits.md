---
title: Limits and Determinism
parent: PHP
nav_order: 7
layout: default
---

# Limits and Determinism
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Operation limit

`setOperationLimit()` limits how many operations a single `execute()`, `run()` or `evaluate()`
call may perform. Each statement and each loop iteration counts as one operation. When the
limit is exceeded, a `BudgetExceededException` aborts the script. The PHP process keeps running,
which makes the limit safe for long running queue workers, where PHP's own time limit would end
the whole worker.

```php
$engine->setOperationLimit(10_000);
```

The counter starts at zero for every call. `0` means unlimited, which is the default. Negative
values are treated as `0`. The same applies to the memory and time limits.

## Memory limit

`setMemoryLimit()` limits how many bytes a single call may allocate. It is best effort: the
engine measures the growth of the process memory at each operation, and string concatenation
with `+` reserves the size of its result before it allocates it.

{: .warning }
The engine cannot see inside allowed PHP functions. A single call like `str_repeat('x', 400000000)`,
`range(1, 100000000)` or `array_fill(…)` can allocate more than the limit before the next check,
and may even hit PHP's own `memory_limit`, which ends the process with a fatal error. Only allow
such functions for trusted scripts, or wrap them in host functions that check their arguments.

```php
$engine->setMemoryLimit(8 * 1024 * 1024); // 8 MB
```

## Execution time limit

`setExecutionTimeLimit()` sets a time limit in seconds. With the `pcntl` extension the engine
uses an alarm signal and throws a `BudgetExceededException` when the time is up. The previous
`SIGALRM` handler and async signal setting are restored afterwards. Without `pcntl` it falls back
to PHP's `set_time_limit()`, which ends the whole process with a fatal error. Prefer the
operation limit in workers.

## Nesting limit

The parser rejects scripts nested deeper than 256 levels (parentheses, operators, blocks, lists,
member chains) with `Script is nested too deeply.`. This keeps a short script from exhausting the
PHP stack.

## Deterministic mode

`deterministic()` only accepts functions whose result depends on nothing but their arguments.
It uses an allowlist, `DeterminismProfile::PURE_FUNCTIONS` (string, math, type, array, hash, JSON
and regex functions). Everything else, such as `time`, `date`, `rand`, `random_int`, `uniqid`,
`getenv`, `sprintf` (locale) or `spl_object_id`, is refused. "Same input, same output" assumes
the same PHP version and ini settings on every run. Use it when the same input must always give
the same result, e.g. to replay a process from stored events.

```php
$engine = new Engine()
    ->deterministic()
    ->allow('strtoupper')            // fine
    ->set('now', new DateTimeImmutable('2026-01-31 12:00'));

$engine->allow('time');              // throws EngineException
```

Pass the current time as a context variable `now` instead of calling a clock function. Scripts
then work with its methods and comparisons:

```javascript
data.deadline < now
now.modify('+14 days').format('Y-m-d')
```

If the host takes responsibility for a function anyway, release it explicitly:

```php
$engine->allowNonDeterministic('random_int');
```

Switching an engine to deterministic mode checks the functions that are already allowed. If
one of them is not deterministic, `deterministic()` throws and the engine stays in normal mode.
