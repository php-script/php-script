---
title: Operators
parent: PHP Script Language Reference
nav_order: 2
---

# Operators
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
   {:toc}

---

## Arithmetic and Concatenation Operators

Standard arithmetic operations are supported. The `+` operator is used for both addition and string concatenation.

-   **Addition for numeric values / Concatenation for string values:** `+`
-   **Subtraction:** `-`
-   **Multiplication:** `*`
-   **Division:** `/`

```javascript
result = 10 + 5;    // 15
result = 20 - 7;    // 13
result = 4 * 6;     // 24
result = 100 / 10;  // 10
greeting = 'Hello' + ' ' + 'World!'; // "Hello World!"
```

## Comparison Operators

Used for comparing values.

-   **Equal to:** `==`
-   **Strictly equal to:** `===`
-   **Not equal to:** `!=`
-   **Strictly not equal to:** `!==`
-   **Greater than:** `>`
-   **Less than:** `<`

Comparison with loose or strictly definition will be transferred **always** to strictly comparison on the php side. So there is no difference in PHP Script.

```javascript
isEqual = (10 == '10');   // true
isStrictlyEqual = (10 === '10'); // false
isNotEqual = (10 != 5);   // true
isStrictlyNotEqual = (10 !== '10'); // true
isGreater = (20 > 10);    // true
isLess = (5 < 10);        // true
```

## Unary Operators

Operators that operate on a single operand.

-   **Negation:** `-` (for numbers)
-   **Logical NOT:** `!` (for booleans)

```javascript
negativeNumber = -10;
isNotTrue = !true; // false
```

## Postfix Operators

Operators that appear after their operand.

-   **Increment:** `++`
-   **Decrement:** `--`

```javascript
count = 0;
count++; // count is now 1
count--; // count is now 0
```
