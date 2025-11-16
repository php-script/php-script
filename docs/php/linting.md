---
title: Linting
parent: PHP
nav_order: 4
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
