# Data Model: Break and Continue Keywords

**Feature**: Break and Continue Keywords
**Branch**: 001-break-continue
**Created**: 2025-11-18
**Based on**: [research.md](./research.md)

## Overview

This document defines the data structures (AST nodes) and runtime context needed to support `break` and `continue` keywords in PHP Script. The design follows the existing AST node patterns and integrates seamlessly with the Lexer → Parser → AstTraverser pipeline.

---

## AST Node Classes

### 1. BreakStatement

**File**: `src/Ast/BreakStatement.php`

**Purpose**: Represents a `break` statement in the Abstract Syntax Tree, used to exit one or more enclosing loops.

**Class Definition**:

```php
<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\AstTraverserInterface;

final readonly class BreakStatement extends BaseNode
{
    public function __construct(
        public int $level = 1,
    ) {
        parent::__construct();
    }

    public function accept(AstTraverserInterface $traverser): string
    {
        return $traverser->visitBreakStatement($this);
    }
}
```

**Properties**:

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `level` | `int` | `1` | Number of nested loop levels to break out of |

**Validation Rules**:
- `level` must be a positive integer (≥ 1)
- Runtime validation: `level` must not exceed current loop nesting depth
- Parse-time validation: Reject `break 0`, `break -1`, non-integer values

**Usage Examples**:

```javascript
// Break immediate loop (level = 1, default)
for (i = 0; i < 10; i = i + 1) {
    if (i == 5) {
        break  // BreakStatement(level: 1)
    }
}

// Break outer loop (level = 2)
for (i = 0; i < 10; i = i + 1) {
    for (j = 0; j < 10; j = j + 1) {
        if (i * j > 50) {
            break 2  // BreakStatement(level: 2)
        }
    }
}
```

---

### 2. ContinueStatement

**File**: `src/Ast/ContinueStatement.php`

**Purpose**: Represents a `continue` statement in the Abstract Syntax Tree, used to skip the remainder of the current iteration and proceed to the next iteration of one or more enclosing loops.

**Class Definition**:

```php
<?php

declare(strict_types=1);

namespace PhpScript\Ast;

use PhpScript\Contracts\AstTraverserInterface;

final readonly class ContinueStatement extends BaseNode
{
    public function __construct(
        public int $level = 1,
    ) {
        parent::__construct();
    }

    public function accept(AstTraverserInterface $traverser): string
    {
        return $traverser->visitContinueStatement($this);
    }
}
```

**Properties**:

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `level` | `int` | `1` | Number of nested loop levels to continue |

**Validation Rules**:
- `level` must be a positive integer (≥ 1)
- Runtime validation: `level` must not exceed current loop nesting depth
- Parse-time validation: Reject `continue 0`, `continue -1`, non-integer values

**Usage Examples**:

```javascript
// Continue immediate loop (level = 1, default)
foreach (items as item) {
    if (!item.valid) {
        continue  // ContinueStatement(level: 1)
    }
    echo item.name
}

// Continue outer loop (level = 2)
for (i = 0; i < 10; i = i + 1) {
    for (j = 0; j < 10; j = j + 1) {
        if (j == 5) {
            continue 2  // ContinueStatement(level: 2)
        }
    }
}
```

---

## Runtime Context Tracking

### Loop Depth Tracking (in AstTraverser)

**Purpose**: Track the current loop nesting depth during code generation to validate break/continue levels.

**Implementation Location**: `src/Core/AstTraverser.php`

**Property**:

```php
private int $loopDepth = 0;
```

**Methods**:

| Method | Description |
|--------|-------------|
| `enterLoop(): void` | Increment `$loopDepth` when entering any loop (for, foreach, while) |
| `exitLoop(): void` | Decrement `$loopDepth` when exiting any loop |
| `validateBreakLevel(int $level): void` | Validate that break level doesn't exceed `$loopDepth` |
| `validateContinueLevel(int $level): void` | Validate that continue level doesn't exceed `$loopDepth` |

**Usage Pattern**:

```php
// In visitForStatement, visitForeachStatement, etc.
public function visitForStatement(ForStatement $node): string
{
    $this->enterLoop();  // Increment depth before processing loop body

    // ... generate loop code ...

    $this->exitLoop();   // Decrement depth after loop body

    return $output;
}

// In visitBreakStatement
public function visitBreakStatement(BreakStatement $node): string
{
    // Validate level is within available loops
    if ($this->loopDepth === 0) {
        throw EngineException::runtimeError(
            "'break' can only be used inside a loop",
            $node->getToken()
        );
    }

    if ($node->level > $this->loopDepth) {
        throw EngineException::runtimeError(
            "Cannot break {$node->level} levels (only {$this->loopDepth} loop(s) available)",
            $node->getToken()
        );
    }

    // Generate PHP break statement
    return $node->level === 1
        ? 'break;'
        : "break {$node->level};";
}
```

---

## Token Types

New token types needed in `src/Core/TokenType.php`:

```php
enum TokenType: string
{
    // ... existing tokens ...

    case T_BREAK = 'T_BREAK';
    case T_CONTINUE = 'T_CONTINUE';

    // ... existing tokens ...
}
```

---

## Parser State Changes

No new parser state is required. The Parser already has statement parsing infrastructure. Break and continue are simple statements parsed like `echo`:

```php
// In Parser::parseStatement()
private function parseStatement(): Node
{
    return match ($this->current()->type) {
        // ... existing cases ...
        TokenType::T_BREAK => $this->parseBreakStatement(),
        TokenType::T_CONTINUE => $this->parseContinueStatement(),
        // ... existing cases ...
    };
}

private function parseBreakStatement(): BreakStatement
{
    $token = $this->consume(TokenType::T_BREAK);

    // Check for optional level parameter
    $level = 1;
    if ($this->current()->type === TokenType::T_INT_LITERAL) {
        $levelToken = $this->consume(TokenType::T_INT_LITERAL);
        $level = (int) $levelToken->value;

        if ($level < 1) {
            throw ParseException::atToken(
                "Break level must be a positive integer (got {$level})",
                $levelToken
            );
        }
    }

    return new BreakStatement($level);
}
```

---

## Lexer Patterns

New keyword patterns needed in `src/Core/Lexer.php`:

```php
private const KEYWORDS = [
    // ... existing keywords ...
    'break' => TokenType::T_BREAK,
    'continue' => TokenType::T_CONTINUE,
    // ... existing keywords ...
];
```

**Word Boundary Handling**: The existing keyword matching already handles word boundaries, so `breakpoint` won't be tokenized as `break` + `point`, but as an identifier.

---

## Error Messages

### Parse-Time Errors

| Scenario | Error Message | Exception Type |
|----------|---------------|----------------|
| `break 0` | "Break level must be a positive integer (got 0)" | `ParseException` |
| `break -1` | "Break level must be a positive integer (got -1)" | `ParseException` |
| `break foo` | "Unexpected identifier 'foo', expected integer or end of statement" | `ParseException` |
| `break 2.5` | "Unexpected float literal, expected integer or end of statement" | `ParseException` |

### Runtime Errors (Code Generation)

| Scenario | Error Message | Exception Type |
|----------|---------------|----------------|
| `break` outside loop | "'break' can only be used inside a loop" | `EngineException` |
| `break 5` with only 2 loops | "Cannot break 5 levels (only 2 loop(s) available)" | `EngineException` |
| `continue` outside loop | "'continue' can only be used inside a loop" | `EngineException` |
| `continue 3` with only 1 loop | "Cannot continue 3 levels (only 1 loop(s) available)" | `EngineException` |

---

## Code Generation Output

The AstTraverser generates PHP code, which is then executed. Break and continue map directly to PHP's native break/continue:

| PHP Script Input | Generated PHP Code | Result |
|------------------|-------------------|--------|
| `break` | `break;` | Exit innermost loop |
| `break 2` | `break 2;` | Exit 2 nested loops |
| `continue` | `continue;` | Skip to next iteration |
| `continue 3` | `continue 3;` | Skip to next iteration of 3rd outer loop |

---

## Integration Points

### Modified Components

1. **Lexer** (`src/Core/Lexer.php`)
   - Add `break` and `continue` to KEYWORDS map
   - No changes to tokenization logic needed

2. **Parser** (`src/Core/Parser.php`)
   - Add `parseBreakStatement()` method
   - Add `parseContinueStatement()` method
   - Add cases to `parseStatement()` match

3. **AstTraverser** (`src/Core/AstTraverser.php`)
   - Add `$loopDepth` property
   - Add `enterLoop()` / `exitLoop()` methods
   - Add `visitBreakStatement()` method
   - Add `visitContinueStatement()` method
   - Modify `visitForStatement()`, `visitForeachStatement()`, `visitWhileStatement()` to track depth

4. **PhpScriptRenderer** (`src/Core/PhpScriptRenderer.php`)
   - Add `visitBreakStatement()` method (render back to source)
   - Add `visitContinueStatement()` method (render back to source)

### New Components

1. **BreakStatement** (`src/Ast/BreakStatement.php`) - New AST node
2. **ContinueStatement** (`src/Ast/ContinueStatement.php`) - New AST node

---

## Testing Requirements

### AST Node Tests

Per constitution (100% coverage mandatory), each node must have:

- Constructor tests (default level, explicit level)
- Constructor validation tests (reject 0, negative)
- `accept()` method tests (calls traverser correctly)
- Property accessor tests

### Parser Tests

- Parse `break` / `continue` without level
- Parse with explicit levels (1, 2, 3)
- Parse errors for invalid levels (0, -1, float, string)

### Traverser Tests

- Execute break in all loop types (for, foreach, while)
- Execute continue in all loop types
- Execute with levels (2, 3) in nested loops
- Validate errors (outside loop, level too high)
- Verify loop depth tracking (increment/decrement)

### Renderer Tests

- Render `break` / `continue` to source
- Render with levels
- Round-trip tests (code → AST → code)

---

## Summary

This data model provides:

✅ **Simple, elegant AST nodes** following existing patterns
✅ **Minimal runtime overhead** (single integer depth counter)
✅ **Clear validation model** (parse-time + runtime checks)
✅ **Direct PHP code generation** (no exceptions for control flow)
✅ **Comprehensive error messages** (helpful, with source locations)
✅ **Full backward compatibility** (no changes to existing code)

The design aligns perfectly with PHP Script's architecture and constitution requirements for quality, simplicity, and developer experience.
