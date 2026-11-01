---
title: Getting Started
parent: PHP
nav_order: 1
layout: default
---

# Getting Started
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Installation

You can install the package via composer:

```bash
composer require php-script/php-script
```

PHP Script requires PHP 8.4 or newer. Coming from v1? Read [Upgrading to v2](./upgrading.html).

## Usage

### Setting up the Engine

```php
use PhpScript\Core\Engine;

$engine = new Engine;
$engine->set('name', 'Ada')
    ->allow('strtoupper')
    ->setExecutionTimeLimit(5)     // seconds
    ->setOperationLimit(100_000);  // stops endless loops

try {
    echo $engine->execute("echo 'Hello ' + strtoupper(name)"); // Hello ADA
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```

Scripts only see variables you pass with `set()` and functions you allow with `allow()`. See
[Context](./context.html), [Functions](./functions.html) and [Limits](./limits.html).

### Running and evaluating

- `execute()` returns the echo output as string.
- `run()` returns an `ExecutionResult` with the output, the value of `return` and the number of
  operations.
- `evaluate()` computes a single expression and returns its typed value.

```php
$engine->run('return 1 + 2')->value;   // 3
$engine->evaluate('name == "Ada"');    // true
```

See [Evaluating and Running](./evaluating.html).
