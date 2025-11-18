# Technical Research: Break and Continue Keywords Implementation

**Feature**: Break and Continue Keywords for Loop Control
**Branch**: `001-break-continue`
**Research Date**: 2025-11-18
**Related Documents**: [spec.md](./spec.md), [plan.md](./plan.md)

---

## Table of Contents

1. [Break/Continue Semantics](#1-breakcontinue-semantics)
2. [AST Node Design](#2-ast-node-design)
3. [Loop Context Tracking](#3-loop-context-tracking)
4. [Exception/Signal Strategy](#4-exceptionsignal-strategy)
5. [Monaco Editor Integration](#5-monaco-editor-integration)
6. [Documentation Structure](#6-documentation-structure)
7. [Summary and Recommendations](#7-summary-and-recommendations)

---

## 1. Break/Continue Semantics

### Research Findings

PHP implements `break` and `continue` with optional numeric level parameters that specify how many nested loop structures to affect.

**Official PHP Documentation Findings:**

- **Syntax**: `break;` or `break numeric_argument;` and `continue;` or `continue numeric_argument;`
- **Default Behavior**: When no level is specified, the default is `1`, affecting only the immediate enclosing loop
- **Level Parameter**: Must be a positive integer literal (not a variable or expression)
- **Supported Structures**: Works with `for`, `foreach`, `while`, `do-while`, and `switch` (PHP treats switch as a looping structure for `continue`)
- **Error Conditions**:
  - Using `break` or `continue` outside a loop context: Fatal error
  - Using a level greater than available nesting depth: Fatal error "Cannot break/continue N levels"
  - Using `break 0` or negative levels: Parse error "Break/continue level must be greater than zero"

### Existing PHP Script Loop Handling

**Current Loop Structures in PHP Script:**

From `/Users/rok/workspace/php-script/php-script/src/Ast/ForStatement.php`:
```php
final readonly class ForStatement extends BaseNode
{
    public function __construct(
        public ?Node $initializer,
        public ?Node $condition,
        public ?Node $increment,
        public Node $body,
        Token $token
    ) {
        parent::__construct($token);
    }
}
```

From `/Users/rok/workspace/php-script/php-script/src/Ast/ForeachStatement.php`:
```php
final readonly class ForeachStatement extends BaseNode
{
    public function __construct(
        public Node $iterable,
        public Variable $value,
        public ?Variable $key,
        public Node $body,
        Token $token
    ) {
        parent::__construct($token);
    }
}
```

**Current Loop Execution in AstTraverser:**

From `/Users/rok/workspace/php-script/php-script/src/Core/AstTraverser.php` (lines 135-167):
- For loops: Generated as PHP `for` statements with initialization, condition, increment, and body
- Foreach loops: Generated as PHP `foreach` statements with iterable, key/value variables, and body
- Both loop types traverse their body using `$this->doTraverse($node->body)`
- No current mechanism exists for early loop termination or iteration skipping

### Decision: PHP-Compatible Break/Continue Semantics

**Approach**: Implement break and continue following PHP semantics with numeric level support.

**Exact Behavior**:
1. **Basic Usage**:
   - `break;` or `break 1;` exits the immediate enclosing loop
   - `continue;` or `continue 1;` skips to the next iteration of the immediate enclosing loop

2. **Level Parameters**:
   - `break 2;` exits two nested loops
   - `continue 2;` skips to the next iteration of the loop two levels up
   - Level must be a positive integer literal (not computed or variable)

3. **Error Messages**:
   - Parse-time errors (detected during parsing):
     - "Cannot break/continue outside of a loop" (when used outside loop context)
     - "Break/continue level must be greater than zero" (for `break 0` or negative)
   - Runtime errors (detected during traversal):
     - "Cannot break/continue N levels, only M levels available" (when level exceeds nesting depth)

**Rationale**:
- PHP Script is described as "JavaScript-inspired" but runs in PHP, making PHP semantics the natural choice
- Numeric level support is powerful for nested loops and matches developer expectations
- PHP's error messages are clear and informative, providing good UX
- Consistency with PHP makes the language easier to learn for PHP developers

**Alternatives Considered**:
1. **JavaScript semantics** (no numeric levels, only label support):
   - Rejected: More complex to implement, less intuitive for simple nested loops
   - Labels add parser complexity and are less commonly used
2. **Python semantics** (no level support at all):
   - Rejected: Forces developers to use boolean flags for nested loop control
   - Reduces expressiveness unnecessarily

**Implementation Notes**:
- Level parameter validation should happen at parse time when possible (literal values)
- Level range validation (exceeding nesting depth) must happen at runtime
- PHP Script currently has `for` and `foreach` loops; no `while` or `do-while` loops yet
- Documentation should note this is PHP-style break/continue, not JavaScript

---

## 2. AST Node Design

### Research Findings

**Existing Statement Node Patterns:**

All statement nodes in PHP Script follow a consistent pattern:

1. **Base Structure** (from `/Users/rok/workspace/php-script/php-script/src/Ast/BaseNode.php`):
```php
abstract readonly class BaseNode implements Node
{
    public function __construct(private ?Token $token = null) {}

    public function getToken(): ?Token
    {
        return $this->token;
    }
}
```

2. **Simple Statement Pattern** (from `/Users/rok/workspace/php-script/php-script/src/Ast/EchoStatement.php`):
```php
final readonly class EchoStatement extends BaseNode
{
    public function __construct(
        public Node $expression,
        ?Token $token = null,
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'expression' => $this->expression->toArray(),
        ];
    }
}
```

3. **Complex Statement Pattern** (from `/Users/rok/workspace/php-script/php-script/src/Ast/IfStatement.php`):
```php
final readonly class IfStatement extends BaseNode
{
    public function __construct(
        public Node $condition,
        public Node $then,
        public ?Node $else,
        Token $token
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'condition' => $this->condition->toArray(),
            'then' => $this->then->toArray(),
            'else' => $this->else?->toArray() ?? null,
        ];
    }
}
```

**Key Patterns Observed**:
- All nodes are `final readonly class` (immutable)
- Extend `BaseNode` and implement `Node` contract
- Constructor accepts data properties + required `Token $token` parameter
- Constructor passes token to parent via `parent::__construct($token)`
- Required properties are non-nullable, optional properties use `?Type`
- All nodes implement `toArray()` method for serialization/debugging
- Properties are public and readonly (PHP 8.1+ feature)

### Decision: Simple Statement Pattern with Level Property

**Approach**: Create `BreakStatement` and `ContinueStatement` following the simple statement pattern.

**Node Structure**:

```php
// src/Ast/BreakStatement.php
final readonly class BreakStatement extends BaseNode
{
    public function __construct(
        public int $level,
        Token $token
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'level' => $this->level,
        ];
    }
}

// src/Ast/ContinueStatement.php
final readonly class ContinueStatement extends BaseNode
{
    public function __construct(
        public int $level,
        Token $token
    ) {
        parent::__construct($token);
    }

    public function toArray(): array
    {
        return [
            'type' => self::class,
            'level' => $this->level,
        ];
    }
}
```

**Rationale**:
- Level is always present (defaults to 1 if not specified in source), so it's non-nullable
- Storing as `int` allows for efficient comparison and validation
- Simple structure matches the nature of these control flow statements
- No child nodes needed (unlike IfStatement which has condition/branches)
- Token provides line/column info for error messages

**Alternatives Considered**:
1. **Nullable level (`?int $level`)** with `null` meaning "default":
   - Rejected: Forces null checks throughout codebase
   - Default of 1 can be applied during parsing, keeping AST simple
2. **Storing level as a Literal node**:
   - Rejected: Over-complication; level is always a simple integer
   - Doesn't add value since level must be literal, not expression
3. **Single `LoopControlStatement` with type enum**:
   - Rejected: Separate classes are clearer and follow existing patterns (separate ForStatement/ForeachStatement)
   - Type safety: Can't accidentally treat break as continue

**Implementation Notes**:
- Both classes should be in separate files: `src/Ast/BreakStatement.php` and `src/Ast/ContinueStatement.php`
- Follow existing file structure and naming conventions
- Include full namespace: `namespace PhpScript\Ast;`
- Import required classes: `use PhpScript\Core\Token;`
- Add `declare(strict_types=1);` header as in all other files

---

## 3. Loop Context Tracking

### Research Findings

**Current AstTraverser Architecture:**

From `/Users/rok/workspace/php-script/php-script/src/Core/AstTraverser.php`:

1. **Traversal Pattern**:
```php
final class AstTraverser implements AstTraverserInterface
{
    private string $generatedCode = '';
    private array $sourceMap = [];
    private int $currentLine = 1;
    private array $allowedFunctions = [];

    public function traverse(Node $node): string
    {
        $this->generatedCode = '';
        $this->sourceMap = [];
        $this->currentLine = 1;
        $this->doTraverse($node);
        return $this->generatedCode;
    }

    private function doTraverse(Node $node): void
    {
        match ($node::class) {
            Program::class => $this->traverseProgram($node),
            EchoStatement::class => $this->traverseEchoStatement($node),
            IfStatement::class => $this->traverseIfStatement($node),
            ForStatement::class => $this->traverseForStatement($node),
            ForeachStatement::class => $this->traverseForeachStatement($node),
            // ... other nodes
            default => throw AstTraverserException::unknownNodeType($node::class),
        };
    }
}
```

2. **Current Loop Traversal**:
```php
private function traverseForStatement(ForStatement $node): void
{
    $this->generatedCode .= 'for (';
    if ($node->initializer instanceof Node) {
        $this->doTraverse($node->initializer);
    }
    $this->generatedCode .= '; ';
    if ($node->condition instanceof Node) {
        $this->doTraverse($node->condition);
    }
    $this->generatedCode .= '; ';
    if ($node->increment instanceof Node) {
        $this->doTraverse($node->increment);
    }
    $this->generatedCode .= ') {';
    $this->doTraverse($node->body);  // <-- Body traversal, no context tracking
    $this->generatedCode .= '}';
}

private function traverseForeachStatement(ForeachStatement $node): void
{
    $this->generatedCode .= 'foreach (';
    $this->doTraverse($node->iterable);
    $this->generatedCode .= ' as ';
    if ($node->key instanceof Variable) {
        $this->doTraverse($node->key);
        $this->generatedCode .= ' => ';
    }
    $this->doTraverse($node->value);
    $this->generatedCode .= ') {';
    $this->doTraverse($node->body);  // <-- Body traversal, no context tracking
    $this->generatedCode .= '}';
}
```

**Key Observations**:
- AstTraverser translates PHP Script AST to PHP code (transpilation)
- No current tracking of execution context or nesting
- Loop bodies are traversed recursively via `doTraverse()`
- State is maintained in private instance properties
- The traverser is stateful and resets on each `traverse()` call

### Decision: Integer Stack for Loop Depth Tracking

**Approach**: Add a loop depth counter that increments when entering loops and decrements when exiting.

**Mechanism**:
```php
final class AstTraverser implements AstTraverserInterface
{
    // Existing properties...
    private int $loopDepth = 0;  // <-- New property

    public function traverse(Node $node): string
    {
        $this->generatedCode = '';
        $this->sourceMap = [];
        $this->currentLine = 1;
        $this->loopDepth = 0;  // <-- Reset on each traverse
        $this->doTraverse($node);
        return $this->generatedCode;
    }

    private function traverseForStatement(ForStatement $node): void
    {
        $this->loopDepth++;  // <-- Enter loop context

        $this->generatedCode .= 'for (';
        // ... generate for loop code
        $this->doTraverse($node->body);
        $this->generatedCode .= '}';

        $this->loopDepth--;  // <-- Exit loop context
    }

    private function traverseForeachStatement(ForeachStatement $node): void
    {
        $this->loopDepth++;  // <-- Enter loop context

        $this->generatedCode .= 'foreach (';
        // ... generate foreach loop code
        $this->doTraverse($node->body);
        $this->generatedCode .= '}';

        $this->loopDepth--;  // <-- Exit loop context
    }

    private function traverseBreakStatement(BreakStatement $node): void
    {
        // Validate level against current depth
        if ($this->loopDepth === 0) {
            throw AstTraverserException::breakOutsideLoop($node->getToken());
        }
        if ($node->level > $this->loopDepth) {
            throw AstTraverserException::breakLevelTooHigh(
                $node->level,
                $this->loopDepth,
                $node->getToken()
            );
        }

        // Generate PHP break statement
        $this->generatedCode .= 'break';
        if ($node->level > 1) {
            $this->generatedCode .= ' ' . $node->level;
        }
    }
}
```

**Rationale**:
- Simple integer counter is efficient (O(1) increment/decrement)
- No memory overhead from maintaining stack structures
- Easy to validate break/continue level against current depth
- Integrates naturally with existing traversal pattern
- Thread-safe (traverser is instantiated per execution)
- Automatic cleanup via decrement ensures correctness even if traversal errors occur mid-loop

**Alternatives Considered**:

1. **Stack of Loop Node References**:
   ```php
   private array $loopStack = [];  // Stack of ForStatement/ForeachStatement nodes
   ```
   - Rejected: Unnecessary complexity; we only need depth, not node references
   - Memory overhead of storing node objects
   - Could be useful if we needed to modify loop nodes during traversal (we don't)

2. **Boolean Flag** (`$inLoop`):
   ```php
   private bool $inLoop = false;
   ```
   - Rejected: Can't handle nested loops or level validation
   - Would need to track depth separately anyway

3. **Return Values for Control Flow**:
   ```php
   private function doTraverse(Node $node): ?ControlFlowSignal
   ```
   - Rejected: Changes entire traversal contract
   - Forces all traverse methods to handle return values
   - Breaks existing code structure significantly

**Implementation Notes**:
- Initialize `$loopDepth = 0` in the constructor (not just in traverse) for safety
- Increment BEFORE traversing loop body, decrement AFTER
- Validate break/continue level in their traverse methods, not in loop methods
- Add new exception methods to `AstTraverserException`:
  - `breakOutsideLoop(Token $token): self`
  - `continueOutsideLoop(Token $token): self`
  - `breakLevelTooHigh(int $requested, int $available, Token $token): self`
  - `continueLevelTooHigh(int $requested, int $available, Token $token): self`

---

## 4. Exception/Signal Strategy

### Research Findings

**Existing Exception Architecture:**

From `/Users/rok/workspace/php-script/php-script/src/Exceptions/AstTraverserException.php`:
```php
class AstTraverserException extends RuntimeException
{
    public static function unknownNodeType(string $nodeClass): self
    {
        return new self(sprintf('Unknown node type: %s', $nodeClass));
    }

    public static function unknownOperator(string $operator): self
    {
        return new self(sprintf('Unknown operator: %s', $operator));
    }

    public static function unknownLiteralType(string $type): self
    {
        return new self(sprintf('Unknown literal type: %s', $type));
    }
}
```

From `/Users/rok/workspace/php-script/php-script/src/Exceptions/EngineException.php`:
```php
class EngineException extends RuntimeException
{
    public function __construct(
        string $message,
        public int $line = 1,
        public readonly int $column = 0,
        public readonly int $offset = 0,
        public readonly int $length = 1,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function runtimeError(string $message, int $line, ...): self
    {
        $message = sprintf('Runtime error: %s in line: %d, column: %d...', ...);
        return new self($message, $line, $column, $offset, $length, $previous);
    }

    public static function invalidFunctionCall(string $functionName, ...): self
    {
        // Similar pattern with location info
    }
}
```

**Current Exception Usage in AstTraverser:**
- `AstTraverserException`: Used for structural errors (unknown node type, unknown operator)
- `EngineException`: Used for runtime validation errors (invalid function calls)
- Both extend `RuntimeException` (not control flow mechanisms)
- Exceptions include location info (line, column, offset, length) from Token

**Key Distinction:**
- Existing exceptions represent ERROR conditions (something went wrong)
- Break/continue are VALID control flow (something is working correctly)

### Decision: No Exceptions - Direct PHP Code Generation

**Approach**: Generate PHP `break` and `continue` statements directly. PHP's native control flow handles the actual break/continue behavior.

**Implementation**:
```php
private function traverseBreakStatement(BreakStatement $node): void
{
    // Validate context (this IS an error condition, so exception is appropriate)
    if ($this->loopDepth === 0) {
        $token = $node->getToken();
        throw EngineException::runtimeError(
            'Cannot use "break" outside of a loop',
            $token?->line ?? 1,
            $token?->column ?? 0,
            $token?->offset ?? 0,
            strlen('break')
        );
    }

    if ($node->level > $this->loopDepth) {
        $token = $node->getToken();
        throw EngineException::runtimeError(
            sprintf('Cannot break %d levels, only %d level(s) available',
                    $node->level, $this->loopDepth),
            $token?->line ?? 1,
            $token?->column ?? 0,
            $token?->offset ?? 0,
            strlen('break ' . $node->level)
        );
    }

    // Generate PHP break statement
    $this->generatedCode .= 'break';
    if ($node->level > 1) {
        $this->generatedCode .= ' ' . $node->level;
    }
}

private function traverseContinueStatement(ContinueStatement $node): void
{
    // Similar validation and code generation...
}
```

**Rationale**:
- **AstTraverser is a transpiler**: It converts PHP Script AST to PHP code
- PHP's native `break` and `continue` do the actual control flow
- When generated PHP code runs, PHP's interpreter handles the break/continue
- Validation errors (wrong context, invalid level) ARE exceptions - they're error conditions
- The actual break/continue behavior is NOT an exception - it's valid code
- This approach is consistent with how all other statements are handled

**Why NOT Use Exceptions for Control Flow:**
1. **Anti-Pattern**: Using exceptions for control flow is widely considered bad practice
2. **Performance**: Exception throwing/catching is slow (irrelevant here since we're not using them for control flow)
3. **Semantic Clarity**: Exceptions mean "something went wrong", not "do this control flow"
4. **Architecture Fit**: AstTraverser generates PHP code; PHP handles execution
5. **Simplicity**: No need to catch/handle special exceptions during traversal

**Alternatives Considered**:

1. **Option A: Control Flow Exceptions** (BreakException, ContinueException):
   ```php
   class BreakException extends Exception {
       public function __construct(public int $level) { ... }
   }

   // In traverseBreakStatement:
   throw new BreakException($node->level);

   // In traverseForStatement:
   try {
       $this->doTraverse($node->body);
   } catch (BreakException $e) {
       if ($e->level > 1) {
           throw new BreakException($e->level - 1);
       }
       // Break current loop
   }
   ```
   - **Rejected**: Massive refactoring of entire traversal system
   - Would need try-catch in every loop traversal method
   - Would need to modify `doTraverse` signature or wrap every call
   - Violates "exceptions for errors, not control flow" principle
   - Doesn't make sense since we're generating code, not executing it

2. **Option B: Return Value Signaling**:
   ```php
   private function doTraverse(Node $node): ?ControlFlowSignal { ... }
   ```
   - **Rejected**: Changes return type of all traverse methods
   - Forces all callers to handle potential control flow returns
   - Breaks existing architecture
   - Unnecessary since we generate PHP code that handles control flow

3. **Option C: Callback/Event System**:
   ```php
   $this->onBreak = fn(int $level) => ...;
   ```
   - **Rejected**: Over-engineered for the problem
   - Adds unnecessary complexity
   - No clear benefit over direct code generation

**Implementation Notes**:
- Break/continue validation happens during traversal (code generation time)
- The generated PHP code's break/continue execute when the PHP code runs
- Use `EngineException::runtimeError()` for validation errors (consistent with invalidFunctionCall pattern)
- Include token location info in all error messages for good developer experience
- Add comprehensive error messages:
  - "Cannot use 'break' outside of a loop"
  - "Cannot use 'continue' outside of a loop"
  - "Cannot break N levels, only M level(s) available"
  - "Cannot continue N levels, only M level(s) available"

---

## 5. Monaco Editor Integration

### Research Findings

**Current Keyword Definition:**

From `/Users/rok/workspace/php-script/php-script/src/Monarch/MonarchLanguageDefinitionService.php`:

```php
private const array KEYWORDS = [
    'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
];
```

**Current Control Flow Snippets:**

```php
// Line 161-218: Control Flow snippets in getCompletionItems()
$model['controlFlows'][] = [
    'label' => 'for',
    'kind' => 'Snippet',
    'snippet' => implode("\n", [
        'for (${1:i} = ${2:0}; ${1} < ${3:count}(${4}); ${1}++) {',
        '    $0',
        '}',
    ]),
    'doc' => 'For-Loop',
    'detail' => 'for (int $i = 0; $i < count($items); $i++) {',
];

$model['controlFlows'][] = [
    'label' => 'foreach',
    'kind' => 'Snippet',
    'snippet' => implode("\n", [
        'foreach (${1:items} as ${2:item}) {',
        '    $0',
        '}',
    ]),
    'doc' => 'Foreach-Loop',
    'detail' => 'foreach ($items as $item) {',
];

$model['controlFlows'][] = [
    'label' => 'if',
    'kind' => 'Snippet',
    'snippet' => implode("\n", [
        'if (${1:condition}) {',
        '    $0',
        '}',
    ]),
    'doc' => 'If-Condition',
    'detail' => 'if ($condition) {',
];

$model['controlFlows'][] = [
    'label' => 'ifelse',
    'kind' => 'Snippet',
    'snippet' => implode("\n", [
        'if (${1:condition}) {',
        '    $2',
        '} else {',
        '    $0',
        '}',
    ]),
    'doc' => 'If-Else-Condition',
    'detail' => 'if ($condition) { ... } else { ... }',
];
```

**Snippet Syntax**:
- `${N:placeholder}` - Tab stop N with default text "placeholder"
- `${N}` - Tab stop N without placeholder text
- `$0` - Final cursor position after all tab stops
- Placeholders with same number (e.g., multiple `${1}`) mirror each other

### Decision: Add Keywords and Context-Aware Snippets

**Approach**: Add `break` and `continue` keywords, plus completion snippets with level examples.

**Changes Required**:

1. **Add Keywords** (line 21-22):
```php
private const array KEYWORDS = [
    'if', 'else', 'foreach', 'as', 'echo', 'return', 'true', 'false', 'null', 'LINEBREAK',
    'break', 'continue',  // <-- Add these
];
```

2. **Add Control Flow Snippets** (after existing control flows, around line 218):
```php
// Break snippets
$model['controlFlows'][] = [
    'label' => 'break',
    'kind' => 'Snippet',
    'snippet' => 'break;',
    'doc' => 'Break - Exit current loop',
    'detail' => 'break;',
];

$model['controlFlows'][] = [
    'label' => 'break (nested)',
    'kind' => 'Snippet',
    'snippet' => 'break ${1:2};',
    'doc' => 'Break - Exit multiple nested loops',
    'detail' => 'break 2;',
];

// Continue snippets
$model['controlFlows'][] = [
    'label' => 'continue',
    'kind' => 'Snippet',
    'snippet' => 'continue;',
    'doc' => 'Continue - Skip to next iteration',
    'detail' => 'continue;',
];

$model['controlFlows'][] = [
    'label' => 'continue (nested)',
    'kind' => 'Snippet',
    'snippet' => 'continue ${1:2};',
    'doc' => 'Continue - Skip to next iteration of outer loop',
    'detail' => 'continue 2;',
];
```

**Rationale**:
- Adding to `KEYWORDS` array enables syntax highlighting automatically
- Separate snippets for basic and nested usage improves discoverability
- Nested variants use `${1:2}` placeholder so users can easily change level
- Documentation strings explain the purpose clearly
- Detail strings show concrete examples
- Follows exact same pattern as existing control flow snippets (for, foreach, if, ifelse)

**Alternatives Considered**:

1. **Single snippet with optional level**:
   ```php
   'snippet' => 'break${1: ${2:1}};',
   ```
   - Rejected: Confusing syntax, hard to use
   - Most users want simple `break;` not `break 1;`

2. **Only basic snippets, no nested variants**:
   - Rejected: Nested loops are common, and level syntax is non-obvious
   - Having examples helps users discover the feature

3. **Context-aware snippets** (only suggest break/continue inside loops):
   - Rejected: Would require AST parsing in completion provider
   - Monaco Editor integration is frontend-facing, not analyzing actual code context
   - Better to show all snippets and let linter catch misuse

**Implementation Notes**:
- Keywords will automatically get tokenizer rule via `'@keywords' => 'keyword'` (line 71)
- No changes needed to tokenizer patterns
- Snippets should be added AFTER existing control flow snippets for logical grouping
- Both basic and nested variants help users learn the level syntax
- Consider adding these to documentation with examples

---

## 6. Documentation Structure

### Research Findings

**Existing Documentation Files:**

From `/Users/rok/workspace/php-script/php-script/docs/`:
- `docs/language/control-flow.md` - Documents if/else, for, foreach loops
- `docs/language/statements.md` - Documents echo, variable assignment, literals

**Control Flow Documentation Structure:**

From `/Users/rok/workspace/php-script/php-script/docs/language/control-flow.md`:
```markdown
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

Executes a block of code if a condition is true...

```javascript
// Basic if statement
if (user.hasPermission('admin')) {
    echo 'Access granted!';
}
```

## For Loop

Executes a block of code a specified number of times...

```javascript
// Standard for loop
for (i = 0; i < 5; i++) {
    echo 'Iteration: ' + i + LINEBREAK;
}
```

## Foreach Loop

Iterates over elements of an array...
```

**Note on Line 53-55**:
```markdown
for (;;) { // Infinite loop (use with caution, requires a break condition inside the body)
    // Note: Break statement is not yet supported.
}
```

**Documentation Pattern Observed**:
- Jekyll front matter with title, parent, nav_order, layout
- H1 heading matches title
- Table of contents with `{:toc}` directive
- H2 sections for each feature
- Brief description followed by code examples
- Examples use JavaScript code fences (since PHP Script syntax is JS-like)
- Practical, real-world examples
- Comments explain key concepts

### Decision: Extend control-flow.md with Break/Continue Section

**Approach**: Add a new section to `docs/language/control-flow.md` documenting break and continue with examples.

**Proposed Documentation Addition** (after Foreach Loop section, around line 73):

```markdown
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
```

**Rationale**:
- Break and continue are control flow features, so they belong in `control-flow.md`
- Following the existing H2 section pattern maintains consistency
- Covering both basic usage and level parameters in separate subsections improves readability
- Including error handling section helps developers understand edge cases
- Examples are practical and progressively more complex
- Comments explain what the code does and what output to expect

**Alternatives Considered**:

1. **Create new file** `docs/language/loop-control.md`:
   - Rejected: Creates unnecessary file proliferation
   - Break/continue are control flow features, logically grouped with loops
   - Users expect to find them in the control flow documentation

2. **Add to statements.md instead**:
   - Rejected: `statements.md` is for basic statements (echo, assignments)
   - Control flow is the natural place for loop control features

3. **Minimal documentation** (just syntax, no examples):
   - Rejected: Examples are valuable for learning
   - Existing docs have rich examples, should maintain consistency

**Implementation Notes**:
- Update the comment on line 54 that says "Break statement is not yet supported"
- Remove or modify the cautionary note about infinite loops
- Consider adding a note that this matches PHP's break/continue syntax
- Ensure code examples use consistent formatting with rest of docs
- Add to table of contents automatically via `{:toc}`

---

## 7. Summary and Recommendations

### Implementation Checklist

**High Priority (P1 - MVP):**

1. **Lexer** (`src/Core/Lexer.php`):
   - [ ] Add `TokenType::T_BREAK` and `TokenType::T_CONTINUE` to `TokenType.php` enum
   - [ ] Add keyword patterns to Lexer: `\bbreak\b` and `\bcontinue\b`

2. **AST Nodes**:
   - [ ] Create `src/Ast/BreakStatement.php` with `int $level` property
   - [ ] Create `src/Ast/ContinueStatement.php` with `int $level` property
   - [ ] Follow existing node patterns: `final readonly class`, extend `BaseNode`, implement `toArray()`

3. **Parser** (`src/Core/Parser.php`):
   - [ ] Add cases for `TokenType::T_BREAK` and `TokenType::T_CONTINUE` in `parseStatement()`
   - [ ] Implement `parseBreakStatement()` method
   - [ ] Implement `parseContinueStatement()` method
   - [ ] Parse optional numeric level (default to 1 if not present)
   - [ ] Validate level is positive integer literal at parse time

4. **AstTraverser** (`src/Core/AstTraverser.php`):
   - [ ] Add `private int $loopDepth = 0` property
   - [ ] Reset `$loopDepth` in `traverse()` method
   - [ ] Increment/decrement `$loopDepth` in `traverseForStatement()` and `traverseForeachStatement()`
   - [ ] Add cases for `BreakStatement` and `ContinueStatement` in `doTraverse()` match statement
   - [ ] Implement `traverseBreakStatement()` with validation and code generation
   - [ ] Implement `traverseContinueStatement()` with validation and code generation

5. **Exception Messages** (`src/Exceptions/`):
   - [ ] Use `EngineException::runtimeError()` for validation errors
   - [ ] Error messages: "Cannot use 'break' outside of a loop"
   - [ ] Error messages: "Cannot break N levels, only M level(s) available"
   - [ ] Include token location info (line, column, offset, length)

6. **Tests**:
   - [ ] Unit tests for `BreakStatement` and `ContinueStatement` nodes
   - [ ] Parser tests for parsing break/continue with and without levels
   - [ ] AstTraverser tests for code generation
   - [ ] AstTraverser tests for validation (outside loop, invalid level)
   - [ ] Integration tests for break/continue in various loop types
   - [ ] Edge case tests (nested loops, level boundaries)

**Medium Priority (P2 - Enhanced UX):**

7. **Monaco Editor** (`src/Monarch/MonarchLanguageDefinitionService.php`):
   - [ ] Add `'break'` and `'continue'` to `KEYWORDS` array
   - [ ] Add 4 completion snippets: break, break (nested), continue, continue (nested)
   - [ ] Follow existing snippet pattern with label, kind, snippet, doc, detail

8. **Documentation** (`docs/language/control-flow.md`):
   - [ ] Add "Break Statement" section with basic usage examples
   - [ ] Add "Breaking Multiple Nested Loops" subsection
   - [ ] Add "Continue Statement" section with basic usage examples
   - [ ] Add "Continuing Outer Loops" subsection
   - [ ] Add "Error Handling" section with error examples
   - [ ] Update/remove note about break not being supported (line 54)

**Low Priority (P3 - Nice to Have):**

9. **Linter** (if applicable):
   - [ ] Validate break/continue usage during linting phase (before execution)
   - [ ] Detect usage outside loop context
   - [ ] Detect invalid levels (if determinable at lint time)

### Key Technical Decisions Summary

| Aspect | Decision | Rationale |
|--------|----------|-----------|
| **Semantics** | PHP-style with numeric levels | Consistency with PHP; powerful for nested loops |
| **AST Nodes** | Separate BreakStatement/ContinueStatement with int $level | Simple, type-safe, follows existing patterns |
| **Loop Tracking** | Integer depth counter in AstTraverser | Efficient O(1), minimal memory, easy validation |
| **Control Flow** | Direct PHP code generation (no exceptions) | AstTraverser is a transpiler; PHP handles execution |
| **Monaco Integration** | Add keywords + 4 snippets (basic + nested) | Syntax highlighting + discoverability |
| **Documentation** | Extend control-flow.md with new sections | Logical grouping with existing control flow docs |
| **Error Messages** | Use EngineException with token location | Consistent with existing error handling |

### Risks and Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Breaking existing tests | High | Run full test suite after each change; 100% coverage ensures detection |
| Incorrect loop depth tracking | High | Comprehensive unit tests for nested loops; test boundary conditions |
| Parser ambiguity with level parameter | Medium | Use explicit token lookahead; validate level is integer literal |
| Inconsistent error messages | Low | Follow existing error message patterns; review all error cases |
| Documentation drift | Low | Update docs as part of implementation; include in PR checklist |

### Open Questions

1. **Future loop types**: If `while` or `do-while` loops are added later, will they automatically work with break/continue?
   - **Answer**: Yes, as long as they increment/decrement `$loopDepth` in their traverse methods

2. **Function scope isolation**: Should break/continue in a function called from a loop affect that loop?
   - **Answer**: No. PHP Script generates PHP code, and PHP isolates function scope. Generated PHP code will naturally prevent this.

3. **Switch statement**: PHP treats `switch` as a loop for `continue`. Should PHP Script implement this?
   - **Answer**: Not in scope for initial implementation (PHP Script doesn't have switch yet). Document as future consideration.

4. **Linting integration**: Can the linter detect break/continue outside loops before execution?
   - **Answer**: Yes, potentially by traversing the AST without generating code. Consider as enhancement after core feature.

### Next Steps

1. Review this research document with stakeholders
2. Create AST node classes (simplest, no dependencies)
3. Extend Lexer and TokenType (minimal change)
4. Implement Parser methods (depends on Lexer and AST nodes)
5. Extend AstTraverser (depends on all above)
6. Write comprehensive tests (alongside implementation)
7. Update Monaco Editor integration (independent of core)
8. Update documentation (after feature is working)

---

**Research completed**: 2025-11-18
**Next artifact**: data-model.md (Phase 1) or proceed directly to tasks.md (Phase 2)
