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

## Usage

### Setting up the Engine

```php
use PhpScript\Core\Engine;

$engine = new Engine;
// Optionally, set an execution time limit to prevent infinite loops
// $engine->setExecutionTimeLimit(5); // Script will time out after 5 seconds

try {
    echo $engine->execute($phpScript);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```
