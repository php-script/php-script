# Quick start: Break and Continue Keywords

**Feature**: Loop Control with Break and Continue
**For**: PHP Script Developers
**Created**: 2025-11-18

## What's New

PHP Script now supports `break` and `continue` keywords for loop control, matching the familiar syntax from JavaScript and PHP:

- **`break`** - Exit a loop immediately
- **`continue`** - Skip to the next iteration
- **Level support** - Control nested loops with `break 2`, `continue 3`, etc.

---

## Basic Usage

### Exit a Loop with `break`

Stop loop execution immediately and continue with code after the loop:

```javascript
// Find first matching user
found = null
foreach (users as user) {
    if (user.id == targetId) {
        found = user
        break  // Exit the loop
    }
}

if (found) {
    echo found.name
}
```

**Before break (old workaround)**:
```javascript
found = null
foundFlag = false
foreach (users as user) {
    if (!foundFlag) {
        if (user.id == targetId) {
            found = user
            foundFlag = true
        }
    }
}
```

**After break (clean and clear)** ✨:
```javascript
found = null
foreach (users as user) {
    if (user.id == targetId) {
        found = user
        break
    }
}
```

---

### Skip Iterations with `continue`

Skip the rest of the current iteration and move to the next one:

```javascript
// Process only valid items
foreach (items as item) {
    if (!item.valid) {
        continue  // Skip this item, move to next
    }

    // Process valid item
    echo item.name
    processItem(item)
}
```

**Before continue (nested conditionals)**:
```javascript
foreach (items as item) {
    if (item.valid) {
        // All processing nested inside
        echo item.name
        processItem(item)
    }
}
```

**After continue (flatter structure)** ✨:
```javascript
foreach (items as item) {
    if (!item.valid) {
        continue  // Guard clause - exit early
    }

    // Main logic not nested
    echo item.name
    processItem(item)
}
```

---

## Loop Types Support

Break and continue work in **all loop types**:

### For Loops

```javascript
for (i = 0; i < 100; i = i + 1) {
    if (i == 50) {
        break  // Stop at 50
    }

    if (i % 2 == 0) {
        continue  // Skip even numbers
    }

    echo i  // Only odd numbers printed
}
```

### Foreach Loops

```javascript
foreach (products as product) {
    if (product.outOfStock) {
        continue  // Skip out-of-stock items
    }

    if (product.featured) {
        echo product.name
        break  // Show only first featured item
    }
}
```

### While Loops

```javascript
while (hasMoreData()) {
    item = getNextItem()

    if (item == null) {
        break  // No more items
    }

    if (item.skip) {
        continue  // Skip this item
    }

    processItem(item)
}
```

---

## Nested Loop Control

Control multiple loop levels with numeric parameters:

### Breaking Out of Nested Loops

```javascript
// Find a specific cell in a grid
for (row = 0; row < 10; row = row + 1) {
    for (col = 0; col < 10; col = col + 1) {
        if (grid[row][col] == target) {
            echo 'Found at row ' + row + ', col ' + col
            break 2  // Exit BOTH loops
        }
    }
}

echo 'Search complete'
```

Without `break 2`, you'd need flag variables:

```javascript
// Old workaround (complex)
found = false
for (row = 0; row < 10; row = row + 1) {
    for (col = 0; col < 10; col = col + 1) {
        if (!found && grid[row][col] == target) {
            echo 'Found at row ' + row + ', col ' + col
            found = true
        }
    }
    if (found) {
        break
    }
}
```

### Continuing Outer Loops

```javascript
// Skip to next batch when error found
for (batchId = 1; batchId <= 10; batchId = batchId + 1) {
    echo 'Processing batch ' + batchId

    foreach (items as item) {
        if (item.hasError) {
            echo 'Error in batch ' + batchId
            continue 2  // Skip to next batch (outer loop)
        }

        processItem(item)
    }
}
```

### Three-Level Nesting

```javascript
for (i = 0; i < 5; i = i + 1) {
    for (j = 0; j < 5; j = j + 1) {
        for (k = 0; k < 5; k = k + 1) {
            if (i + j + k > 10) {
                break 3  // Exit all three loops
            }

            if (i == j) {
                continue 2  // Skip to next j iteration
            }

            echo i + ',' + j + ',' + k
        }
    }
}
```

---

## Common Patterns

### Pattern 1: Find First Match

```javascript
result = null
foreach (collection as item) {
    if (matches(item)) {
        result = item
        break
    }
}
```

### Pattern 2: Filter Processing

```javascript
foreach (items as item) {
    // Skip unwanted items
    if (item.archived) { continue }
    if (item.deleted) { continue }
    if (!item.active) { continue }

    // Process only active, non-archived, non-deleted items
    processItem(item)
}
```

### Pattern 3: Early Exit on Condition

```javascript
success = true
for (i = 0; i < tests.length; i = i + 1) {
    if (!runTest(tests[i])) {
        success = false
        break  // Stop on first failure
    }
}
```

### Pattern 4: Skip Invalid Data

```javascript
foreach (records as record) {
    // Validation checks
    if (record.id == null) { continue }
    if (record.value < 0) { continue }
    if (record.status != 'active') { continue }

    // Only valid records reach here
    saveRecord(record)
}
```

---

## Error Scenarios

### Break/Continue Outside Loop

```javascript
// ❌ Error: 'break' can only be used inside a loop
if (condition) {
    break
}
```

```javascript
// ✅ Correct: break inside loop
foreach (items as item) {
    if (condition) {
        break
    }
}
```

### Invalid Level

```javascript
// ❌ Error: Cannot break 5 levels (only 2 loop(s) available)
for (i = 0; i < 10; i = i + 1) {
    for (j = 0; j < 10; j = j + 1) {
        break 5  // Only 2 loops available!
    }
}
```

```javascript
// ✅ Correct: level matches available loops
for (i = 0; i < 10; i = i + 1) {
    for (j = 0; j < 10; j = j + 1) {
        break 2  // Exactly 2 loops available
    }
}
```

### Negative or Zero Level

```javascript
// ❌ Parse Error: Break level must be a positive integer
for (i = 0; i < 10; i = i + 1) {
    break 0   // Invalid!
    break -1  // Invalid!
}
```

```javascript
// ✅ Correct: positive integer levels
for (i = 0; i < 10; i = i + 1) {
    break     // Default to level 1
    break 1   // Explicit level 1
}
```

---

## Editor Support

### Syntax Highlighting

`break` and `continue` are now recognized as keywords and highlighted accordingly in Monaco Editor.

### Code Completion

When typing inside a loop, the editor suggests:

- **`break`** - Exit the current loop
- **`continue`** - Skip to next iteration
- **`break 2`** - Exit nested loops (when applicable)
- **`continue 2`** - Continue outer loop (when applicable)

### Linting

The linter detects invalid usage **before execution**:

- ⚠️ Break/continue outside loop context
- ⚠️ Invalid level parameters
- ⚠️ Level exceeds available loops (when statically determinable)

---

## Comparison with PHP

PHP Script's break/continue work **exactly like PHP**:

| PHP | PHP Script | Notes |
|-----|------------|-------|
| `break;` | `break` | Exit immediate loop (no semicolon in PHP Script) |
| `break 2;` | `break 2` | Exit 2 nested loops |
| `continue;` | `continue` | Skip to next iteration |
| `continue 3;` | `continue 3` | Continue 3rd outer loop |

**Main difference**: PHP Script doesn't require semicolons at the end of statements.

---

## Comparison with JavaScript

JavaScript supports labeled break/continue, while PHP Script uses numeric levels:

**JavaScript (labels)**:
```javascript
outer: for (let i = 0; i < 10; i++) {
    for (let j = 0; j < 10; j++) {
        if (i * j > 50) {
            break outer;  // Break the labeled loop
        }
    }
}
```

**PHP Script (levels)**:
```javascript
for (i = 0; i < 10; i = i + 1) {
    for (j = 0; j < 10; j = j + 1) {
        if (i * j > 50) {
            break 2  // Break 2 levels
        }
    }
}
```

Both accomplish the same goal, but PHP Script follows PHP's numeric level approach for consistency.

---

## Best Practices

### ✅ Do

- Use `break` for early exit conditions (find first match, stop on error)
- Use `continue` for filtering/guard clauses (skip invalid items)
- Keep nesting levels low (prefer 1-2 levels max for readability)
- Use descriptive conditions before break/continue
- Document why you're breaking multiple levels

### ❌ Don't

- Don't use break/continue outside loops (will error)
- Don't use excessive nesting (more than 3 levels hard to follow)
- Don't use break where `if` condition would be clearer
- Don't use continue to skip large blocks (consider extracting to function)

---

## Examples from Real Scenarios

### Example 1: User Authentication

```javascript
authenticated = false
foreach (users as user) {
    if (user.email == inputEmail) {
        if (checkPassword(user, inputPassword)) {
            authenticated = true
            currentUser = user
            break  // Found and authenticated
        }
    }
}
```

### Example 2: Data Validation

```javascript
errors = []
foreach (records as record) {
    // Skip records that are flagged for deletion
    if (record.markedForDeletion) {
        continue
    }

    // Validate required fields
    if (record.name == null || record.name == '') {
        errors.push('Record ' + record.id + ' missing name')
        continue
    }

    // Process valid record
    saveRecord(record)
}
```

### Example 3: Batch Processing

```javascript
for (pageNum = 1; pageNum <= totalPages; pageNum = pageNum + 1) {
    items = fetchPage(pageNum)

    foreach (items as item) {
        if (item.processed) {
            continue  // Skip already processed
        }

        success = processItem(item)
        if (!success) {
            echo 'Failed to process item ' + item.id
            break 2  // Stop entire batch on critical failure
        }
    }
}
```

---

## Learn More

- **Language Reference**: See `docs/language/control-flow.md` for detailed documentation
- **Statement Reference**: See `docs/language/statements.md` for statement syntax
- **Editor Guide**: See `docs/php/editor.md` for Monaco Editor integration details

---

**Quick Reference Card**:

| Statement | Action | Default Level | With Level |
|-----------|--------|---------------|------------|
| `break` | Exit loop | 1 (innermost) | `break 2` |
| `continue` | Skip iteration | 1 (innermost) | `continue 3` |

**Validation**:
- ✅ Level must be positive integer
- ✅ Level must not exceed available loops
- ✅ Must be used inside a loop

**Error Messages**:
- "break can only be used inside a loop"
- "Cannot break N levels (only M loop(s) available)"

---

Happy looping! 🔁
