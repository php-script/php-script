---
title: Language Reference
nav_order: 1
layout: default
---

# PHP Script Language Reference

This section provides a comprehensive overview of the PHP Script language syntax, including all supported statements, expressions, and their variations.

## 1. Basic Statements and Expressions

### Echo Statement

The `echo` statement is used to output values.

```javascript
echo 'Hello World!';
echo 123;
echo true;
echo LINEBREAK; // Outputs a new line
```

### Variable Assignment

Variables can be assigned values using the `=` operator. Variables are dynamically typed.

```javascript
myVariable = 10;
anotherVariable = 'some text';
booleanVariable = true;
```

### Literals

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

## 2. Operators

### Arithmetic and Concatenation Operators

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

### Comparison Operators

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

### Unary Operators

Operators that operate on a single operand.

-   **Negation:** `-` (for numbers)
-   **Logical NOT:** `!` (for booleans)

```javascript
negativeNumber = -10;
isNotTrue = !true; // false
```

### Postfix Operators

Operators that appear after their operand.

-   **Increment:** `++`
-   **Decrement:** `--`

```javascript
count = 0;
count++; // count is now 1
count--; // count is now 0
```

## 3. Control Flow

### If-Else Statement

Executes a block of code if a condition is true. An optional `else` block can be provided for when the condition is false.

```javascript
// Basic if statement
if (user.hasPermission('admin')) {
    echo 'Access granted!';
}

// If-else statement
if (totalLogins > 100) {
    echo 'High activity!';
} else {
    echo 'Normal activity.';
}
```

### For Loop

Executes a block of code a specified number of times. It consists of an initializer, a condition, and an increment expression, all of which are optional.

```javascript
// Standard for loop
for (i = 0; i < 5; i++) {
    echo 'Iteration: ' + i + LINEBREAK;
}

// For loop with optional parts
for (; count < 10;) { // No initializer, no increment
    echo 'Count: ' + count + LINEBREAK;
    count++;
}

for (;;) { // Infinite loop (use with caution, requires a break condition inside the body)
    // Note: Break statement is not yet supported.
}
```

### Foreach Loop

Iterates over elements of an array or iterable object.

```javascript
// Foreach loop over values
foreach (users_list as u) {
    echo '- ' + u + LINEBREAK;
}

// Foreach loop over key-value pairs (key is optional)
foreach (users_map as key, value) {
    echo key + ': ' + value + LINEBREAK;
}
```

## 4. Object and Array Interaction

### Member Access

Access properties or methods of an object using the `.` operator.

```javascript
userName = user.name;
loginCount = user.logins.count();
```

### Array Access

Access elements of an array using square brackets `[]`.

```javascript
firstUser = users_list[0];
secondUser = users_list[1];
```

### Function Calls

Call functions or methods with arguments.

```javascript
echo 'Hello World!'; // Calling a global function (if exposed)
user.hasPermission('admin'); // Calling a method on an object
```
