# Changelog

## v2.0.0 (2026-11-01)

This release adds features for embedding PHP Script in workflow engines and hardens the sandbox.
Several changes break existing scripts or host code; see "Breaking changes" before upgrading.

### Added

- `Engine::evaluate()` computes a single expression and returns its typed value.
- `return` statement and `Engine::run()`, which returns an `ExecutionResult` (output, value,
  operation count).
- List (`[1, 2]`) and map (`{a: 1}`) literals.
- Operation, memory and execution time limits (`setOperationLimit()`, `setMemoryLimit()`,
  `setExecutionTimeLimit()`), reported as `BudgetExceededException` with the source position.
- Deterministic mode (`deterministic()`, `allowNonDeterministic()`).
- Linter diagnostics with rules, severities and an `infinite-loop` warning.
- `Engine::allowWithCallbacks()` for functions that take callbacks.

### Security

- Callbacks are checked at runtime. A script can no longer pass `'shell_exec'`,
  `'Class::method'` or `[object, 'method']` to `array_map`, `usort` or a method of a context
  object.
- `allow()` refuses functions that would break the sandbox (`Engine::NEVER_ALLOWED`, e.g.
  `extract`, `get_defined_vars`, `ini_set`, `set_error_handler`, `ob_*`).
- Static methods cannot be called through context objects, and methods of closures cannot be
  called at all.
- A function that takes callbacks cannot itself be passed as a callback, so
  `array_map('array_map', …)` cannot smuggle an unchecked callback.
- `==` limits how many values it compares, so nested lists cannot make it run for hours.
- Scripts nested deeper than 256 levels are rejected instead of exhausting the PHP stack.
- String literals with control characters can no longer break the generated code or the
  source positions of errors.
- Temporary files are removed even after fatal errors; the error handler, `SIGALRM` handler and
  time limit are restored after every run.

### Maintenance

- CI tests PHP 8.4, 8.5 and 8.6; PHP 8.5 is required to pass. GitHub Actions are pinned to commit
  SHAs.

### Breaking changes

- **`allow()` validates names.** Unknown functions, namespaced names, functions in
  `NEVER_ALLOWED` and functions that take callbacks throw an `InvalidArgumentException`. Use
  `allowWithCallbacks()` for `array_map`, `array_filter`, `usort` and similar functions.
- **Deterministic mode uses an allowlist** (`DeterminismProfile::PURE_FUNCTIONS`). Functions that
  are not on it, such as `sprintf`, now need `allowNonDeterministic()`.
- **`==` never converts types.** `1 == '1'` and `null == false` are `false`. Lists, maps and
  objects are compared by value.
- **`+` adds numbers only** and concatenates everything else: `'42' + 1` is `'421'`. Lists, maps
  and objects without `__toString` cannot be concatenated.
- **`<`, `>`, `<=`, `>=` only compare values of the same kind** (numbers, strings, dates).
- **Precedence:** relational operators bind tighter than `==` and `!=`, as in JavaScript.
- **Missing keys and properties are errors** instead of `null`. Writes no longer create
  intermediate arrays (`a.b.c = 1` needs `a.b` to exist).
- **Assignments and `++`/`--` need a variable or a path below it** as target.
- **Line breaks end `return`, `break` and `continue`.** `return` followed by a value on the next
  line returns `null`.
- **Integer literals that do not fit into a PHP integer** are a parse error.
- **Reserved names:** variables cannot be named like PHP superglobals or `this`, or start with
  `__ps_`.
- **Linter:** `isValid()` ignores warnings; the `infinite-loop` rule reports warnings. Unknown
  rule names throw an `InvalidArgumentException`.
- Compile errors (e.g. calling a function that is not allowed) no longer start with
  `Runtime error:`.
