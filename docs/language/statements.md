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

## Loop Control Statements

### Break Statement

The `break` statement terminates the current loop immediately. It accepts an optional numeric level to exit multiple nested loops.

```javascript
// Exit a single loop
for (i = 0; i < 10; i++) {
    if (i == 5) {
        break; // Exit the loop
    }
}

// Exit multiple nested loops
for (i = 0; i < 10; i++) {
    for (j = 0; j < 10; j++) {
        break 2; // Exit both loops
    }
}
```

See [Control Flow](control-flow.html#break-statement) for detailed examples and usage patterns.

### Continue Statement

The `continue` statement skips the rest of the current loop iteration and continues with the next iteration. It accepts an optional numeric level to continue an outer loop.

```javascript
// Skip to next iteration
for (i = 0; i < 10; i++) {
    if (i % 2 == 0) {
        continue; // Skip even numbers
    }
    echo i;
}

// Continue outer loop
for (i = 0; i < 10; i++) {
    for (j = 0; j < 10; j++) {
        if (j == 5) {
            continue 2; // Continue outer loop
        }
    }
}
```

See [Control Flow](control-flow.html#continue-statement) for detailed examples and usage patterns.
