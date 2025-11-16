---
title: Whitelist Functions
parent: PHP
nav_order: 2
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

We try to support a high level of security for running the Script Engine. Therefore we have just a whitelist 
implementation for native php functions. So you have to allow alle necessary php functions to use them inside the 
PHP Script.

```php
$engine->allow('count')
    ->allow('strtoupper');
```

When php functions are allowed, the Monarch Language definition provides them with the according code completion
suggestion for the editor.
