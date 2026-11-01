---
title: Whitelist Functions
parent: PHP
nav_order: 3
layout: default
---

# Whitelist PHP Functions
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Allow the execution of PHP functions

Scripts can only call PHP functions that you allow explicitly. Allow only what end-users need.

```php
$engine->allow('count')
    ->allow('strtoupper', 'strtolower');
```

When PHP functions are allowed, the Monarch language definition offers them as code completion
suggestions in the editor.

`allow()` validates every name and throws an `InvalidArgumentException` when:

- the function does not exist (names must be plain global function names, no namespaces),
- the function can never be allowed (see below),
- the function takes a callback (see [Callbacks](#callbacks)).

## Functions that can never be allowed

Some functions would break the sandbox no matter how carefully the host uses them, e.g. `exit`,
`eval`, `extract`, `get_defined_vars`, `ini_set`, `set_error_handler`, `header` and every
output buffering function (`ob_*`). Functions that call names hidden in arrays or options, such as
`call_user_func`, `filter_var` (`FILTER_CALLBACK`) and `preg_replace_callback_array`, are on the list
too, as are `unserialize` and `constant`. The full list is in `Engine::NEVER_ALLOWED`.

## Callbacks

Functions like `array_map`, `array_filter` or `usort` call a function that the script passes as an
argument. A script could pass the name of *any* PHP function as a string, e.g.
`array_map('shell_exec', ['id'])`. Such functions must be allowed with `allowWithCallbacks()`:

```php
$engine->allow('strtoupper')
    ->allowWithCallbacks('array_map', 'array_filter');
```

At runtime, every argument in a callback position is checked. It passes when it is:

- the name of an allowed function that does not take callbacks itself (`array_map('strtoupper', list)`;
  `array_map('array_map', …)` is refused),
- a closure or other object set by the host (`$engine->set('double', fn ($x) => $x * 2)`),
- `null`.

Everything else, such as `'shell_exec'`, `'DateTime::createFromFormat'` or `['DateTime', 'createFromFormat']`,
stops the script with an `EngineException`.

The same check applies to methods of context objects: arguments for parameters typed `callable` or
`Closure` are checked like callbacks. For untyped or `mixed` parameters, static callables
(`'Class::method'`, `[object, 'method']`) are refused, but plain strings pass, because they are
mostly data (`query.where('date', value)`).

{: .warning }
A method with an untyped or `mixed` parameter that calls its argument (`$callback($item)`) can
still be given the name of a PHP function as a plain string. Type such parameters as `callable`
or `Closure`, or do not expose those methods to scripts.
