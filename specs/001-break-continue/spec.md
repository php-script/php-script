# Feature Specification: Break and Continue Keywords

**Feature Branch**: `001-break-continue`
**Created**: 2025-11-18
**Status**: Draft
**Input**: User description: "I want to add the possibility to support loops within php-script better by adding break and continue keywords like the PHP code syntax has."

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Early Exit from Loop (Priority: P1)

A developer writing PHP Script needs to exit a loop early when a specific condition is met, without having to use complex flag variables or conditional logic to skip remaining iterations.

**Why this priority**: This is the most fundamental loop control requirement. Without `break`, developers must use workarounds like boolean flags or nested conditionals, making code harder to read and maintain. This represents the minimum viable feature.

**Independent Test**: Can be fully tested by writing a simple for/foreach loop with a break statement inside a conditional, executing it, and verifying that loop execution stops at the correct iteration.

**Acceptance Scenarios**:

1. **Given** a for loop iterating from 0 to 10, **When** a break statement is encountered at iteration 5, **Then** the loop terminates immediately and code execution continues after the loop
2. **Given** a foreach loop iterating over an array of items, **When** a break statement is encountered after finding a target item, **Then** the loop terminates and returns the found item
3. **Given** a while loop with a break inside a conditional, **When** the condition becomes true, **Then** the loop exits regardless of the while condition

---

### User Story 2 - Skip Current Iteration (Priority: P2)

A developer writing PHP Script needs to skip the rest of the current loop iteration and move to the next iteration when certain conditions are met, without exiting the entire loop.

**Why this priority**: While less critical than `break`, `continue` is essential for clean loop logic. It allows filtering or skipping invalid items without nesting the entire loop body in conditionals. Common in data processing scenarios.

**Independent Test**: Can be tested independently by writing a loop with a continue statement inside a conditional, verifying that only specific iterations are skipped while the loop continues executing.

**Acceptance Scenarios**:

1. **Given** a for loop iterating from 0 to 10, **When** a continue statement is encountered at even numbers, **Then** only odd numbers are processed
2. **Given** a foreach loop processing an array of user records, **When** a continue statement is encountered for invalid records, **Then** only valid records are processed and invalid ones are skipped
3. **Given** a while loop reading items, **When** a continue statement is encountered for empty items, **Then** empty items are skipped but the loop continues processing

---

### User Story 3 - Nested Loop Control (Priority: P3)

A developer working with nested loops needs to break out of multiple loop levels or continue outer loops from within inner loops, using optional level parameters like `break 2` or `continue 2`.

**Why this priority**: This is an advanced feature that enhances developer experience but is not essential for basic loop control. Most use cases can be handled with P1 and P2, though nested loop control provides cleaner code for complex scenarios.

**Independent Test**: Can be tested by creating nested for/foreach loops and using `break 2` to exit both loops, or `continue 2` to skip to the next iteration of the outer loop, verifying the control flow behaves as expected.

**Acceptance Scenarios**:

1. **Given** two nested for loops, **When** `break 2` is encountered in the inner loop, **Then** both loops terminate and execution continues after the outer loop
2. **Given** a foreach loop inside a for loop, **When** `continue 2` is encountered in the inner loop, **Then** the outer loop advances to its next iteration
3. **Given** three levels of nested loops, **When** `break 3` is encountered, **Then** all three loops terminate

---

### Edge Cases

- What happens when `break` is used outside of a loop context? (Must produce clear error message)
- What happens when `continue` is used outside of a loop context? (Must produce clear error message)
- What happens when `break 5` is used but only 2 nested loops exist? (Must produce clear error message indicating invalid level)
- What happens when `break 0` or `break -1` is used? (Must produce clear error message indicating invalid level)
- How does `break` behave in a loop that contains a function call that also has loops? (Should only break the immediate loop context, not loops in called functions)
- How does the language handle `break` or `continue` followed by other statements on the same line? (Must be treated as the end of the current statement)

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Language MUST support `break` keyword to terminate loop execution immediately
- **FR-002**: Language MUST support `continue` keyword to skip current iteration and proceed to next iteration
- **FR-003**: `break` and `continue` MUST work in all loop types: `for`, `foreach`, and `while` loops
- **FR-004**: Language MUST support optional numeric level parameter (e.g., `break 2`, `continue 3`) to control nested loops
- **FR-005**: Default behavior when no level is specified MUST be level 1 (innermost loop)
- **FR-006**: Language MUST validate that `break` and `continue` are only used within loop contexts
- **FR-007**: Language MUST validate that numeric level parameters are positive integers within valid range
- **FR-008**: Language MUST provide clear, helpful error messages when `break` or `continue` is misused
- **FR-009**: Language MUST prevent `break` and `continue` in function calls from affecting loops in calling context
- **FR-010**: Syntax highlighting MUST recognize `break` and `continue` as keywords
- **FR-011**: Code completion MUST suggest `break` and `continue` when inside loop contexts
- **FR-012**: Linter MUST detect and report `break`/`continue` usage outside loop contexts before execution

### Key Entities

- **BreakStatement**: Represents a break statement in the AST, with optional level parameter indicating how many loop levels to exit (default: 1)
- **ContinueStatement**: Represents a continue statement in the AST, with optional level parameter indicating which loop level to continue (default: 1)
- **LoopContext**: Runtime context tracking that maintains the current nesting level of loops during execution, used to validate break/continue level parameters

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Developers can write loops with early exit conditions using `break` keyword, reducing code complexity by eliminating boolean flag workarounds
- **SC-002**: Developers can write filtering loops using `continue` keyword, reducing nested conditional depth by at least one level
- **SC-003**: Misuse of `break` or `continue` outside loops produces clear error messages indicating the issue and suggesting valid usage
- **SC-004**: All existing loop-related tests continue to pass with 100% test coverage maintained
- **SC-005**: Editor integration correctly highlights `break` and `continue` as keywords with proper syntax coloring
- **SC-006**: Code completion suggests `break` and `continue` with contextual snippets when cursor is inside a loop
- **SC-007**: Linter catches invalid break/continue usage (wrong context or invalid level) before code execution, with clear diagnostic messages

## Assumptions

- The language already supports `for`, `foreach`, and `while` loops (as evidenced by existing control flow support mentioned in the constitution)
- Current AST structure can be extended to support new statement types
- The Lexer can be extended to recognize new keywords without breaking existing functionality
- The Parser can be updated to handle new statement types with optional numeric parameters
- The AstTraverser can be modified to track loop context and handle loop control statements
- Monaco Editor integration supports dynamic keyword additions
- Break/continue behavior should match PHP semantics (since PHP Script is inspired by PHP)
- Default level of 1 for break/continue (affecting only immediate loop) aligns with developer expectations from PHP
