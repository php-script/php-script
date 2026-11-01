---
title: Operators
parent: PHP Script Language Reference
nav_order: 2
layout: default
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
-   **Modulo:** `%`

```javascript
result = 10 + 5;    // 15
result = 20 - 7;    // 13
result = 4 * 6;     // 24
result = 100 / 10;  // 10
result = 7 % 3;     // 1
greeting = 'Hello' + ' ' + 'World!'; // "Hello World!"
```

`+` adds when both operands are numbers and concatenates otherwise. Number types stay
PHP-native: `int + int` is an `int`, `int * float` is a `float`. A numeric string like `'42'`
stays a string: `'42' + 1` is `'421'`. `true` becomes `'1'`, `false` and `null` become `''`.
Lists, maps and objects without `__toString` cannot be concatenated and stop the script.

## Comparison Operators

Used for comparing values.

-   **Equal to:** `==` (also `===`)
-   **Not equal to:** `!=` (also `!==`)
-   **Greater than:** `>`
-   **Greater than or equal to:** `>=`
-   **Less than:** `<`
-   **Less than or equal to:** `<=`

`==` never converts types, so `==` and `===` behave the same: `1 == '1'` and `null == false`
are `false`. Integers and floats are both numbers: `10 == 10.0` is `true`. Lists and maps are
equal when they have the same keys and equal values (key order of maps does not matter, order of
lists does). Objects are equal when they are the same instance, or of the same class with equal
properties. Two `DateTimeImmutable` instances for the same point in time are equal.

`<`, `>`, `<=` and `>=` only compare numbers with numbers, strings with strings and dates with
dates. Any other combination, such as a date string with a date object, stops the script with a
runtime error instead of returning a wrong result.

```javascript
isEqual = (10 == '10');   // false, different types
isNotEqual = (10 != 5);   // true
isGreater = (20 > 10);    // true
isAtLeast = (10 >= 10);   // true
isLess = (5 < 10);        // true
isOverdue = deadline < now; // dates compare by time
```

## Logical Operators

-   **And:** `&&`
-   **Or:** `||`

```javascript
canOrder = isActive && amount > 0;
needsReview = amount > 1000 || isNewCustomer;
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

## Operator Precedence

From strongest to weakest binding:

| Operators | Description |
|-----------|-------------|
| `.` `[]` `()` | member access, index access, call |
| `!` `-` | unary not, negation |
| `*` `/` `%` | multiplicative |
| `+` `-` | additive |
| `<` `>` `<=` `>=` | relational |
| `==` `!=` | equality |
| `&&` | logical and |
| `\|\|` | logical or |

Relational operators bind tighter than equality, as in JavaScript: `a < b == c < d` is
`(a < b) == (c < d)`.

Operators of the same level are evaluated from left to right: `10 - 5 - 3` is `2`. Use
parentheses to change the order: `10 - (5 - 3)` is `8`.

