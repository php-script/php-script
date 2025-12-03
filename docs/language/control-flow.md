---
title: Control Flow
parent: PHP Script Language Reference
nav_order: 3
layout: default
---

# Control Flow
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## If-Else Statement

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

## For Loop

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
    if (shouldExit) {
        break; // Exit the infinite loop
    }
}
```

## Foreach Loop

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

## Break Statement

Terminates the current loop immediately and continues execution after the loop.

```javascript
// Exit loop early when condition is met
for (i = 0; i < 10; i++) {
    if (i == 5) {
        break;
    }
    echo 'Iteration: ' + i + LINEBREAK;
}
// Output: Iteration: 0 through 4

// Break in foreach loop
foreach (items as item) {
    if (item == 'stop') {
        break;
    }
    echo item + LINEBREAK;
}
```

### Breaking Multiple Nested Loops

The `break` statement accepts an optional numeric level to exit multiple nested loops.

```javascript
// Break out of nested loops
for (i = 0; i < 3; i++) {
    for (j = 0; j < 3; j++) {
        if (i == 1 && j == 1) {
            break 2; // Exits both inner and outer loop
        }
        echo 'i=' + i + ', j=' + j + LINEBREAK;
    }
}
```

The level must be a positive integer (1, 2, 3, etc.) indicating how many nested loops to exit. The default is `break;` or `break 1;` which exits only the current loop.

## Continue Statement

Skips the rest of the current loop iteration and continues with the next iteration.

```javascript
// Skip even numbers
for (i = 0; i < 10; i++) {
    if (i % 2 == 0) {
        continue;
    }
    echo 'Odd number: ' + i + LINEBREAK;
}
// Output: Odd number: 1, 3, 5, 7, 9

// Skip invalid items in foreach
foreach (users as user) {
    if (user.status == 'inactive') {
        continue;
    }
    echo 'Active user: ' + user.name + LINEBREAK;
}
```

### Continuing Outer Loops

Like `break`, `continue` accepts an optional numeric level to skip to the next iteration of an outer loop.

```javascript
// Continue outer loop from inner loop
for (i = 0; i < 3; i++) {
    for (j = 0; j < 3; j++) {
        if (j == 1) {
            continue 2; // Skip to next iteration of outer loop
        }
        echo 'i=' + i + ', j=' + j + LINEBREAK;
    }
}
```

The level must be a positive integer indicating which loop level to continue. The default is `continue;` or `continue 1;` which continues only the current loop.

## Error Handling

Using `break` or `continue` outside of a loop will produce a runtime error:

```javascript
// ERROR: Cannot use 'break' outside of a loop
if (condition) {
    break; // Not inside a loop!
}
```

Using a level greater than the available nesting depth will also produce an error:

```javascript
// ERROR: Cannot break 3 levels, only 2 level(s) available
for (i = 0; i < 5; i++) {
    for (j = 0; j < 5; j++) {
        break 3; // Only 2 loops!
    }
}
```
