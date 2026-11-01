---
title: Error Handling
parent: PHP
nav_order: 6
layout: default
---

# Error Handling
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## EngineException

The main exception the engine throws on error is the [`EngineException`](https://github.com/php-script/php-script/blob/main/src/Exceptions/EngineException.php)

```php
try {
    $result = $engine->execute($phpScript);
} catch (EngineException $e) {
    var_dump($e);
}
```

The `EngineException` provides with `$line` and `$column` the x, y coordinates for the cause of an error. Additionally 
it provides `$offset` and `$length` too. The `$offset` is the offset from the very beginning and the `$length` marks the 
whole cause source of the error. Within the editor we can show exactly the position for the cause of an error.

If you provide your own editor, you can too by using this position data of the committed PHP Script code.

## Runtime errors

Errors while the script runs are re-thrown as `EngineException` with the position of the
script line that failed. This includes reading a missing key (`data.missing`), a division by
zero and PHP warnings raised by allowed functions.

## BudgetExceededException

Thrown when a script exceeds its operation or memory limit (see
[Limits and Determinism](./limits.html)). It extends `EngineException`, so existing error
handling keeps working. Only the script is aborted, the PHP process continues.

```php
use PhpScript\Exceptions\BudgetExceededException;

try {
    $engine->setOperationLimit(10_000)->run($script);
} catch (BudgetExceededException $e) {
    // e.g. "Operation limit of 10000 exceeded in line: 3, column: 5, offset: 20"
}
```

## LexerException

The `LexerException` can be fired during errors in the Lexer which is in charge on transforming the given PHP Script
into tokens. It offers the same position data as the `EngineException` does and gets re-thrown as `EngineException` with
itself into the previous property on the thrown `EngineExcpetion`.

## ParseException

The parser parses the tokens and when having an error, the parser throws a `ParseException`. This exception itself gets 
also catched at the core Engine and re-thrown with the error position data as usual.

## AstTraverserException

During the Transformation from `PHP Script` into `PHP` code. But this gets catched and will be thrown as
`EngineException` to your implementation. During the transformation process the cause of an error can not be positioned
as before, because the Traverser works on the parsed tokens which are far away from the original source code as 
PHP Script. But it gets re-thrown as `EngineException` as well, but with just the zero-based position data.
