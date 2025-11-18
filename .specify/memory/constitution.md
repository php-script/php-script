# PHP Script - Project Constitution & Memory

**Last Updated:** 2025-11-18
**Version:** 1.0.0

---

## Project Identity

### Purpose
PHP Script is a JavaScript-inspired scripting language that runs entirely in PHP, enabling end-users to customize and extend PHP-powered backends without requiring a separate Node.js service. It provides a secure, sandboxed execution environment with full control over exposed functions and data.

### Core Philosophy
1. **Security First** - Sandboxed execution with explicit whitelisting of functions and context
2. **Zero Compromise Quality** - 100% test coverage, 100% type coverage, zero tolerance for errors
3. **JavaScript Familiarity** - Syntax designed to be immediately recognizable to JS developers
4. **PHP-Native** - No external dependencies beyond PHP 8.4+, fully self-contained
5. **Developer Experience** - Rich editor integration with Monaco, robust error messages with source pointers

---

## Architecture Principles

### Core Components (in src/Core/)

1. **Engine** - The orchestrator that binds everything together
   - Manages execution context and variable scope
   - Enforces function whitelisting via `allow()` method
   - Provides execution time limits to prevent infinite loops
   - Coordinates Lexer → Parser → AST Traverser pipeline

2. **Lexer** (Tokenization)
   - Converts source code string into stream of tokens
   - Implements LexerInterface
   - Throws LexerException for syntax errors

3. **Parser** (AST Generation)
   - Converts token stream into Abstract Syntax Tree
   - Implements ParserInterface
   - Throws ParseException for parsing errors

4. **AstTraverser** (Execution)
   - Walks the AST and executes the script
   - Implements AstTraverserInterface
   - Evaluates expressions, executes statements
   - Throws AstTraverserException for runtime errors

5. **PhpScriptRenderer** (Code Generation)
   - Converts AST back to PHP Script source code
   - Used for linting and code formatting
   - Enables round-trip conversion (code → AST → code)

### AST Node Hierarchy (in src/Ast/)

All nodes extend `BaseNode` which implements the `Node` contract:
- **Program** - Root node containing all statements
- **Statements**: `EchoStatement`, `IfStatement`, `ForStatement`, `ForeachStatement`
- **Expressions**: `BinaryOperation`, `UnaryOperation`, `FunctionCall`, `ArrayAccess`
- **Terminals**: `Variable`, `Identifier`, `Literal`

### Contract-Driven Design (in src/Contracts/)

All core services are defined by interfaces:
- `LexerInterface` - Tokenization contract
- `ParserInterface` - Parsing contract
- `AstTraverserInterface` - Execution contract
- `Node` - AST node contract

This enables:
- Dependency injection
- Easy mocking in tests
- Alternative implementations
- Clear separation of concerns

### Supporting Services

1. **LinterService** (src/Linter/)
   - Validates PHP Script code
   - Uses PhpScriptRenderer to check syntax validity
   - Provides detailed error messages

2. **MonarchLanguageDefinitionService** (src/Monarch/)
   - Generates Monaco Editor language definitions
   - Provides keyword highlighting
   - Generates dynamic code completion based on context
   - Includes control flow snippets (for, foreach, if, ifelse)

### Exception Hierarchy (in src/Exceptions/)

Granular exceptions for precise error handling:
- `LexerException` - Tokenization errors
- `ParseException` - Parsing errors
- `AstTraverserException` - Runtime execution errors
- `EngineException` - High-level engine errors

All exceptions provide source code pointers to help users identify the exact location of errors.

---

## Code Quality Standards

### Non-Negotiable Requirements

1. **100% Test Coverage** - Every line must be covered by tests
   - Command: `composer test:unit`
   - Uses PEST testing framework
   - Coverage verification enforced in CI/CD

2. **100% Type Coverage** - Every property and method must be fully typed
   - Command: `composer test:type-coverage`
   - Uses PEST type-coverage plugin
   - Strict types declaration required in all files

3. **Zero Linting Errors** - Code must pass Laravel Pint standards
   - Command: `composer lint` (auto-fix)
   - Command: `composer test:lint` (check only)
   - PSR-12 compliant with custom rules from pint.json

4. **Zero Static Analysis Errors** - Must pass PHPStan at maximum level
   - Command: `composer test:types`
   - Configuration in phpstan.neon.dist
   - No baseline files, no ignored errors

5. **Zero Refactoring Violations** - Must pass Rector checks
   - Command: `composer refactor` (auto-refactor)
   - Command: `composer test:refactor` (check only)
   - Configuration in rector.php

### Development Workflow

**ALWAYS follow this sequence:**

```bash
# 1. Make your changes
# 2. Fix code style
composer lint

# 3. Apply automated refactorings
composer refactor

# 4. Re-lint (refactoring can introduce style issues)
composer lint

# 5. Run full test suite
composer test
```

The `composer test` command runs ALL checks:
- `test:lint` - Verify code style
- `test:type-coverage` - Verify 100% type coverage
- `test:unit` - Verify 100% test coverage
- `test:types` - Verify PHPStan static analysis
- `test:refactor` - Verify Rector rules

**Never commit until all checks pass.**

---

## Language Design Principles

### Syntax Philosophy

1. **JavaScript Familiarity** - Use JS syntax where possible
   - Variable assignment: `x = 10` (no var/let/const required)
   - String concatenation: `'Hello ' + name`
   - Comments: `// single line` only
   - Control flow: `if`, `for`, `foreach`

2. **PHP Data Types** - Support PHP's type system
   - Strings, integers, floats, booleans, arrays, objects
   - Object property access: `user.name`
   - Method calls: `user.hasPermission('admin')`
   - Array access: `users_list[0]`

3. **Security by Default**
   - No eval() or exec()
   - No file system access
   - No network access
   - Explicit function whitelisting required

4. **Sandboxed Execution**
   - Scripts can only access what's explicitly provided via `Engine->set()`
   - Functions must be explicitly allowed via `Engine->allow()`
   - Execution time limits available via `setExecutionTimeLimit()`

### Supported Operations

**Operators:**
- Arithmetic: `+`, `-`, `*`, `/`, `%`
- Comparison: `==`, `!=`, `<`, `>`, `<=`, `>=`
- Logical: `&&`, `||`, `!`
- Unary: `+`, `-`, `!`

**Control Flow:**
- `if (condition) { ... }` with optional `else`
- `for (init; condition; increment) { ... }`
- `foreach (array as item) { ... }`

**Statements:**
- `echo expression` - Output to result buffer
- Variable assignment
- Function calls

---

## Editor Integration

### Monaco Editor Support

The project provides comprehensive Monaco Editor integration:

1. **Language Definition** - Via MonarchLanguageDefinitionService
   - Syntax highlighting for keywords
   - Token classification (keyword, operator, string, number, comment)
   - Bracket matching

2. **Code Completion** - Dynamic based on provided context
   - Variables from `Engine->set()`
   - Allowed functions from `Engine->allow()`
   - Object properties and methods (introspected from context)
   - Control flow snippets (for, foreach, if, ifelse)

3. **Linting** - Real-time syntax validation
   - Via LinterService using PhpScriptRenderer
   - Error messages with precise source locations

### Integration Locations
- `public/` directory contains playground implementation
- Documentation: `docs/php/editor.md`
- Makefile target: `make playground` → http://localhost:8080/playground.php

---

## Security Considerations

### Threat Model

PHP Script is designed for scenarios where:
- End-users need limited scripting capabilities
- Full PHP access would be dangerous
- A separate Node.js service is not desired
- Fine-grained control over available functions is required

### Security Mechanisms

1. **Sandboxing** - No access to PHP's global functions or filesystem
2. **Whitelisting** - Functions must be explicitly allowed
3. **Context Control** - Only explicitly set variables are accessible
4. **Time Limits** - Execution timeouts prevent infinite loops
5. **No Dynamic Code Execution** - No eval(), no dynamic includes

### Security Best Practices

When using PHP Script:
- Never expose dangerous functions (file operations, exec, eval, etc.)
- Validate and sanitize all data before passing to context
- Use execution time limits for untrusted scripts
- Review all exposed object methods for security implications
- Consider the blast radius of each exposed function

---

## Testing Strategy

### Test Organization (tests/ directory)

Tests mirror the src/ structure:
- `tests/Core/` - Tests for core components
- `tests/Ast/` - Tests for AST nodes
- `tests/Linter/` - Tests for linting service
- `tests/Monarch/` - Tests for Monaco integration

### Testing Principles

1. **Comprehensive Coverage** - 100% line and branch coverage required
2. **Behavior Testing** - Test functionality, not implementation
3. **Edge Cases** - Explicitly test error conditions and boundaries
4. **Clear Naming** - Test names describe what they verify
5. **Isolated Tests** - No dependencies between tests

### PEST Framework Usage

The project uses PEST for elegant, expressive tests:

```php
it('executes a simple echo statement', function () {
    $engine = new Engine();
    $result = $engine->execute("echo 'Hello World'");
    expect($result)->toBe('Hello World');
});
```

---

## Documentation Standards

### Inline Documentation

1. **DocBlocks Required** for:
   - All public methods
   - Complex private methods
   - Classes and interfaces

2. **Type Hints** - Always use PHP 8.4 type hints
   - Prefer native types over docblock types
   - Use docblock only for complex generics

3. **Comments** - Explain "why", not "what"
   - Code should be self-documenting
   - Comments for business logic, not syntax

### Project Documentation (docs/ directory)

Jekyll-based documentation site:
- `docs/language/` - PHP Script language reference
- `docs/php/` - PHP integration guides
- Hosted via GitHub Pages

**When adding features:**
1. Update relevant docs/language/ files
2. Add examples to docs/php/ guides
3. Update README.md if user-facing
4. Consider adding playground examples

---

## Git Workflow

### Branching Strategy

- `main` branch - Production-ready code
- Feature branches - Created from main
- PR required for all changes

### Commit Standards

**Good commit messages:**
- Clear, descriptive summaries
- Explain "why" in body if needed
- Reference issues where applicable

**Commit workflow:**
1. Create branch from main
2. Make changes and test thoroughly
3. Run full test suite (`composer test`)
4. Write clear commit message
5. Push and open PR

### PR Requirements

Before merging:
- All tests must pass (GitHub Actions)
- 100% code coverage maintained
- Code review approved
- Docs updated if applicable

---

## Common Patterns & Conventions

### Naming Conventions

1. **Classes** - PascalCase, singular nouns
   - Good: `Engine`, `Parser`, `BinaryOperation`
   - Bad: `engine`, `Parsers`, `binary_operation`

2. **Methods** - camelCase, verb-based
   - Good: `execute()`, `allowFunction()`, `setContext()`
   - Bad: `Execution()`, `allow_function()`, `context()`

3. **Variables** - camelCase, descriptive
   - Good: `$executionTimeLimit`, `$allowedFunctions`
   - Bad: `$etl`, `$af`, `$data`

4. **Constants** - SCREAMING_SNAKE_CASE
   - Good: `TOKEN_TYPE_IDENTIFIER`
   - Bad: `tokenTypeIdentifier`

### Code Organization

1. **One Class Per File** - Class name matches filename
2. **Strict Types** - Always: `declare(strict_types=1);`
3. **Final by Default** - Classes are final unless designed for extension
4. **Readonly Properties** - Use readonly when possible
5. **Constructor Property Promotion** - Preferred for simple properties

### Error Handling

1. **Fail Fast** - Throw exceptions early
2. **Specific Exceptions** - Use appropriate exception types
3. **Helpful Messages** - Include context and suggestions
4. **Source Pointers** - Always include line/column information

---

## Future Directions

### Planned Features (from README TODO)

- [ ] Render Mermaid.js Flowchart from AST
- [ ] Provide Monaco editor component for vanilla JavaScript
- [ ] Provide Monaco editor component for Vue.js
- [ ] Provide Monaco editor component for React.js

### Design Considerations for New Features

When adding features, consider:
1. **Security** - Does this compromise the sandbox?
2. **Complexity** - Can users understand it easily?
3. **Performance** - Impact on execution speed?
4. **Testing** - Can we maintain 100% coverage?
5. **Documentation** - How will we explain this?

---

## Key Files Reference

### Core Implementation
- `src/Core/Engine.php` - Main entry point
- `src/Core/Lexer.php` - Tokenization
- `src/Core/Parser.php` - AST generation
- `src/Core/AstTraverser.php` - Execution
- `src/Core/PhpScriptRenderer.php` - Code generation

### Configuration
- `composer.json` - Dependencies and scripts
- `phpstan.neon.dist` - Static analysis config
- `pint.json` - Code style rules
- `rector.php` - Refactoring rules
- `phpunit.xml.dist` - Test configuration

### Documentation
- `README.md` - Quick start guide
- `docs/` - Full language reference
- `PROJECT_CONSTITUTION.md` - This file

### Development
- `Makefile` - Common development tasks
- `public/playground.php` - Interactive demo
- `tests/` - Test suite

---

## Communication Standards

### When Working on This Project

1. **Ask Questions** - Better to clarify than assume
2. **Document Decisions** - Update this file for architectural changes
3. **Share Knowledge** - Comment non-obvious code
4. **Respect Standards** - Follow the established patterns
5. **Think Security** - Always consider sandbox implications

### PR Description Template

```markdown
## Summary
Brief description of what this PR does

## Changes
- Bullet list of specific changes

## Testing
How this was tested, what scenarios were covered

## Documentation
What docs were updated

## Checklist
- [ ] All tests pass (`composer test`)
- [ ] 100% coverage maintained
- [ ] Docs updated
- [ ] No security implications
```

---

## Attribution

**PHP Script** was created by **Robert Kummer** (post@robert-kummer.de)
Licensed under the **MIT License**
Repository: https://github.com/php-script/php-script

---

## Constitution Maintenance

This document should be updated when:
- Architectural decisions are made
- New patterns are established
- Quality standards change
- Major features are added
- Security policies evolve

**Maintainer Responsibility:** Keep this document accurate and useful as a reference for all contributors and AI assistants working on the project.
