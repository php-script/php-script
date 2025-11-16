---
title: Basic Statements and Expressions
parent: PHP Script Language Reference
nav_order: 1
layout: default
---

# Basic Statements and Expressions
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Echo Statement

The `echo` statement is used to output values.

```javascript
echo 'Hello World!';
echo 123;
echo true;
echo LINEBREAK; // Outputs a new line
```

## Variable Assignment

Variables can be assigned values using the `=` operator. Variables are dynamically typed.

```javascript
myVariable = 10;
anotherVariable = 'some text';
booleanVariable = true;
```

## Literals

PHP Script supports the following literal types:

-   **Numbers:** Integers and floating-point numbers.
    ```javascript
    number = 123;
    floatNumber = 3.14;
    ```
-   **Strings:** Enclosed in single or double quotes.
    ```javascript
    singleQuoteString = 'Hello';
    doubleQuoteString = "World";
    ```
-   **Booleans:** `true` and `false`.
    ```javascript
    isTrue = true;
    isFalse = false;
    ```
-   **Null:** `null`.
    ```javascript
    emptyValue = null;
    ```
-   **LINEBREAK:** A special keyword representing a new line character.
    ```javascript
    echo 'Line 1' + LINEBREAK + 'Line 2';
    ```
