---
title: Upgrading to v2
parent: PHP
nav_order: 10
layout: default
---

# Upgrading from v1 to v2
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

v2 hardens the sandbox and makes the language behave more like JavaScript. Most scripts keep
working, but several changes can break existing scripts or host code. Go through this page
before you upgrade, and lint your stored scripts with the new version.

```bash
composer require php-script/php-script:^2.0
```

PHP Script v2 requires PHP 8.4 or newer.

## Host code

### `allow()` validates function names

`allow()` now throws an `InvalidArgumentException` for

- functions that do not exist,
- namespaced names,
- functions that can never be exposed (`Engine::NEVER_ALLOWED`, e.g. `extract`, `ini_set`,
  `call_user_func`, `unserialize`), and
- functions that take a callback, such as `array_map`, `array_filter` or `usort`.

Allow functions that take callbacks with `allowWithCallbacks()`. A script can only pass functions
as callbacks that you allowed as well:

```php
// v1
$engine->allow('array_map', 'strtoupper');

// v2
$engine->allowWithCallbacks('array_map')->allow('strtoupper');
```

See [Functions](./functions.html) for details.

### Editor

`getDefinition()` of the `MonarchLanguageDefinitionService` has a new `loopKeywords` key, and the
tokenizer highlights `break` and `continue` as keywords. See [Integrate Editor](./editor.html).

## Scripts

### String literals stay strings

A string literal that looks like a number is a string. In v1 it was turned into a number.

| Expression | v1 | v2 |
|------------|----|----|
| `'007'` | `7` | `'007'` |
| `'1.5'` | `1.5` | `'1.5'` |

### `==` never converts types

`==` and `!=` compare without type juggling, like `===` in JavaScript. Integers and floats with
the same value are equal. Lists, maps and objects are compared by value.

| Expression | v1 | v2 |
|------------|----|----|
| `1 == '1'` | `true` | `false` |
| `10 == 10.0` | `false` | `true` |
| Two objects with the same properties | `false` | `true` |

### `+` adds numbers only

`+` adds two numbers. With any other operand it concatenates strings, as in JavaScript. Numeric
strings from the context are strings too.

| Expression | v1 | v2 |
|------------|----|----|
| `count + 1` with `count = '42'` | `43` | `'421'` |
| `'Hello ' + name` | `'Hello Ada'` | `'Hello Ada'` |

Lists, maps and objects without `__toString()` cannot be concatenated.

### Comparisons need values of the same kind

`<`, `>`, `<=` and `>=` compare numbers with numbers, strings with strings and dates with dates.
`'10' > 9` was `true` in v1 and throws `Cannot compare string with int` in v2.

### Writes no longer create intermediate arrays

`a.b.c = 1` needs `a.b` to exist. In v1 missing levels were created.

```javascript
// v2
a = {b: {}}
a.b.c = 1
```

### Assignment targets

Assignments, `++` and `--` need a variable or a path below a variable, such as `a`, `a.b` or
`a[0]`. `o.f().a = 2` throws `Invalid assignment target.`

### Number literals

Integer literals that do not fit into a PHP integer, such as `99999999999999999999`, are a parse
error. v1 turned them into floats.

### Reserved variable names

Variables cannot be named `this`, like a PHP superglobal (`_GET`, `_POST`, `GLOBALS`, …) or start
with `__ps_`.

## New features

v2 also adds features you may want to use after upgrading:

- `evaluate()` for single expressions and `run()` with `return` values —
  [Evaluating and Running](./evaluating.html)
- List and map literals (`[1, 2]`, `{a: 1}`) — [Statements](../language/statements.html#literals)
- Operation and memory limits, and a deterministic mode with an allowlist of pure functions —
  [Limits](./limits.html#deterministic-mode)
- Linter diagnostics with rules and severities, and an `infinite-loop` warning —
  [Linting](./linting.html)

The full list is in the
[changelog](https://github.com/php-script/php-script/blob/main/CHANGELOG.md).
