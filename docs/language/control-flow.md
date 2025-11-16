---
title: Control Flow
parent: PHP Script Language Reference
nav_order: 3
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
    // Note: Break statement is not yet supported.
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
