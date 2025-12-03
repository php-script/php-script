# PHP Script Development Guidelines

Auto-generated from project constitution. Last updated: 2025-11-18

## Project Identity

**PHP Script** is a JavaScript-inspired scripting language that runs entirely in PHP, enabling end-users to customize and extend PHP-powered backends without requiring a separate Node.js service. It provides a secure, sandboxed execution environment with full control over exposed functions and data.

## Active Technologies
- PHP 8.4+ + None (PHP-native, following Zero External Dependencies principle) (001-break-continue)
- N/A (language feature, no data persistence) (001-break-continue)

### Core Stack
- **Language**: PHP 8.4+
- **Testing**: PEST (PHP Testing Framework)
- **Static Analysis**: PHPStan (maximum strictness level)
- **Code Style**: Laravel Pint (PSR-12 compliant)
- **Refactoring**: Rector
- **Editor Integration**: Monaco Editor
- **Documentation**: Jekyll (GitHub Pages)

### Key Dependencies
- No external runtime dependencies (PHP-native)
- Composer for development tools only
- Monaco Editor for playground/integration

## Project Structure

```text
php-script/
├── src/
│   ├── Core/                      # Core engine components
│   │   ├── Engine.php             # Main orchestrator
│   │   ├── Lexer.php              # Tokenization
│   │   ├── Parser.php             # AST generation
│   │   ├── AstTraverser.php       # Execution engine
│   │   └── PhpScriptRenderer.php  # Code generation
│   ├── Ast/                       # AST node definitions
│   │   ├── BaseNode.php
│   │   ├── Program.php
│   │   ├── Statements/            # Statement nodes
│   │   ├── Expressions/           # Expression nodes
│   │   └── Terminals/             # Leaf nodes
│   ├── Contracts/                 # Interfaces
│   │   ├── LexerInterface.php
│   │   ├── ParserInterface.php
│   │   ├── AstTraverserInterface.php
│   │   └── Node.php
│   ├── Exceptions/                # Custom exceptions
│   │   ├── LexerException.php
│   │   ├── ParseException.php
│   │   ├── AstTraverserException.php
│   │   └── EngineException.php
│   ├── Linter/                    # Code validation
│   │   └── LinterService.php
│   └── Monarch/                   # Monaco Editor integration
│       └── MonarchLanguageDefinitionService.php
├── tests/                         # Mirror of src/ structure
├── docs/                          # Jekyll documentation site
│   ├── language/                  # Language reference
│   └── php/                       # Integration guides
├── public/                        # Playground demo
│   └── playground.php
├── .specify/                      # Speckit framework integration
│   ├── commands/                  # Slash commands (in .claude/commands/)
│   ├── memory/
│   │   └── constitution.md        # Project constitution
│   ├── scripts/                   # Workflow automation
│   │   ├── check-prerequisites.sh
│   │   ├── create-new-feature.sh
│   │   ├── setup-plan.sh
│   │   └── update-agent-context.sh
│   └── templates/                 # Document templates
│       ├── spec-template.md
│       ├── plan-template.md
│       ├── tasks-template.md
│       ├── checklist-template.md
│       └── agent-file-template.md
└── specs/                         # Feature specifications (created per-feature)
    └── NNN-feature-name/          # Numbered feature directories
        ├── spec.md                # Feature specification
        ├── plan.md                # Implementation plan
        ├── tasks.md               # Task breakdown
        └── research.md            # Research notes
```

## Development Commands

### Quality Assurance (NON-NEGOTIABLE)

```bash
# ALWAYS run in this order before committing:

# 1. Fix code style
composer lint

# 2. Apply automated refactorings
composer refactor

# 3. Re-lint (refactoring can introduce style issues)
composer lint

# 4. Run full test suite (includes all checks below)
composer test
```

### Individual Test Commands

```bash
# Test coverage (must be 100%)
composer test:unit

# Type coverage (must be 100%)
composer test:type-coverage

# Static analysis (zero errors)
composer test:types

# Code style check (zero errors)
composer test:lint

# Refactoring check (zero violations)
composer test:refactor
```

### Development Tools

```bash
# Start playground demo
make playground  # → http://localhost:8080/playground.php

# Watch mode for development
composer test:watch
```

## Speckit Workflow Commands

The project uses the Speckit framework for structured feature development:

```bash
# Create a new feature specification
/speckit.specify <feature description>

# Generate implementation plan
/speckit.plan

# Generate task breakdown
/speckit.tasks

# Analyze consistency across artifacts
/speckit.analyze

# Execute implementation
/speckit.implement

# Update project constitution
/speckit.constitution

# Generate custom checklist
/speckit.checklist

# Clarify underspecified areas
/speckit.clarify

# Convert tasks to GitHub issues
/speckit.taskstoissues
```

## Code Quality Standards (NON-NEGOTIABLE)

### The Five Pillars

1. **100% Test Coverage** - Every line must be tested
   - Enforced via PEST with coverage verification
   - No exceptions, no baselines

2. **100% Type Coverage** - Every property and method fully typed
   - Enforced via PEST type-coverage plugin
   - `declare(strict_types=1);` in all files

3. **Zero Linting Errors** - PSR-12 compliance via Laravel Pint
   - Custom rules in pint.json
   - Auto-fix available but must verify

4. **Zero Static Analysis Errors** - PHPStan at maximum level
   - Configuration in phpstan.neon.dist
   - No baseline files allowed

5. **Zero Refactoring Violations** - Rector rules enforcement
   - Configuration in rector.php
   - Auto-refactor available but must verify

### Consequence

**Never commit until ALL five pillars pass.** No exceptions.

## Code Style Conventions

### Naming

- **Classes**: PascalCase, singular nouns (`Engine`, `Parser`, `BinaryOperation`)
- **Methods**: camelCase, verb-based (`execute()`, `allowFunction()`, `setContext()`)
- **Variables**: camelCase, descriptive (`$executionTimeLimit`, `$allowedFunctions`)
- **Constants**: SCREAMING_SNAKE_CASE (`TOKEN_TYPE_IDENTIFIER`)

### Code Organization

- **One Class Per File** - Filename matches class name
- **Strict Types** - Always `declare(strict_types=1);` at top
- **Final by Default** - Classes are final unless designed for extension
- **Readonly Properties** - Use readonly when possible
- **Constructor Property Promotion** - Preferred for simple properties

### Error Handling

- **Fail Fast** - Throw exceptions early
- **Specific Exceptions** - Use appropriate exception types from src/Exceptions/
- **Helpful Messages** - Include context and suggestions
- **Source Pointers** - Always include line/column information for user-facing errors

## Core Architecture Principles

### Security First

PHP Script is designed as a **sandboxed execution environment**:

- **No Eval** - No dynamic code execution
- **No File System** - No file operations
- **No Network** - No HTTP/socket access
- **Explicit Whitelisting** - Functions must be explicitly allowed via `Engine->allow()`
- **Context Control** - Variables must be explicitly set via `Engine->set()`
- **Time Limits** - Execution timeouts available via `setExecutionTimeLimit()`

### Contract-Driven Design

All core services are defined by interfaces in `src/Contracts/`:
- Enables dependency injection
- Facilitates testing with mocks
- Allows alternative implementations
- Enforces clear separation of concerns

### Pipeline Architecture

Execution follows a clear pipeline:
```
Source Code → Lexer → Tokens → Parser → AST → AstTraverser → Result
```

Each stage:
- Has its own dedicated class
- Implements a specific contract
- Throws specific exception types
- Is independently testable

## Language Design Philosophy

### JavaScript Familiarity

The language syntax is designed to be immediately recognizable to JavaScript developers:

```javascript
// Variable assignment (no var/let/const)
x = 10
name = 'John'

// String concatenation
message = 'Hello ' + name

// Comments (single-line only)
// This is a comment

// Control flow
if (x > 5) {
    echo 'Large'
} else {
    echo 'Small'
}

for (i = 0; i < 10; i = i + 1) {
    echo i
}

foreach (items as item) {
    echo item
}
```

### PHP Data Types

While syntax is JS-like, data types are PHP-native:
- Object property access: `user.name`
- Method calls: `user.hasPermission('admin')`
- Array access: `users_list[0]`
- All PHP types supported: strings, integers, floats, booleans, arrays, objects

## Editor Integration

### Monaco Editor Support

The project provides comprehensive Monaco Editor integration via `MonarchLanguageDefinitionService`:

1. **Syntax Highlighting** - Token classification for keywords, operators, strings, numbers, comments
2. **Code Completion** - Dynamic suggestions based on:
   - Variables from `Engine->set()`
   - Allowed functions from `Engine->allow()`
   - Object properties and methods (introspected from context)
   - Control flow snippets (for, foreach, if, ifelse)
3. **Linting** - Real-time validation via `LinterService`
4. **Error Messages** - Precise source locations for errors

### Playground Demo

- Located in `public/playground.php`
- Run with `make playground`
- Access at http://localhost:8080/playground.php
- Full working example of Monaco integration

## Recent Changes
- 001-break-continue: Added PHP 8.4+ + None (PHP-native, following Zero External Dependencies principle)

### Integration: Speckit Framework (2025-11-18)
- Added 9 slash commands for structured feature development workflow
- Created `.specify/` directory structure with scripts, templates, and memory

### Feature: Control Flow Snippets (Recent)

### Documentation: Comprehensive Guides (Recent)

## Testing Strategy

### Test Organization

Tests mirror the `src/` structure:
- `tests/Core/` - Core component tests
- `tests/Ast/` - AST node tests
- `tests/Linter/` - Linting service tests
- `tests/Monarch/` - Monaco integration tests

### Testing Principles

1. **Comprehensive Coverage** - 100% line and branch coverage required
2. **Behavior Testing** - Test functionality, not implementation
3. **Edge Cases** - Explicitly test error conditions and boundaries
4. **Clear Naming** - Test names describe what they verify (PEST's `it()` style)
5. **Isolated Tests** - No dependencies between tests

### Example Test Pattern

```php
it('executes a simple echo statement', function () {
    $engine = new Engine();
    $result = $engine->execute("echo 'Hello World'");
    expect($result)->toBe('Hello World');
});

it('throws exception for undefined variable', function () {
    $engine = new Engine();
    $engine->execute("echo undefined_var");
})->throws(AstTraverserException::class);
```

## Git Workflow

### Branching Strategy

- **main** - Production-ready code
- **Feature branches** - For Speckit workflow, use format: `NNN-feature-name` (e.g., `001-add-while-loops`)
- **PR Required** - All changes must go through pull request review

### Commit Standards

- Clear, descriptive summaries
- Explain "why" in body if non-obvious
- Reference issues where applicable
- Conventional commits encouraged

### PR Requirements

Before merging:
- ✓ All tests pass (GitHub Actions)
- ✓ 100% code coverage maintained
- ✓ 100% type coverage maintained
- ✓ Code review approved
- ✓ Documentation updated if applicable

## Security Considerations

### Threat Model

PHP Script is designed for scenarios where:
- End-users need **limited** scripting capabilities
- Full PHP access would be **dangerous**
- A separate Node.js service is **not desired**
- Fine-grained control over available functions is **required**

### Security Mechanisms

1. **Sandboxing** - No access to PHP's global functions or filesystem
2. **Whitelisting** - Functions must be explicitly allowed via `Engine->allow()`
3. **Context Control** - Only explicitly set variables are accessible via `Engine->set()`
4. **Time Limits** - Execution timeouts prevent infinite loops
5. **No Dynamic Execution** - No eval(), no dynamic includes, no code generation

### Security Best Practices When Using PHP Script

- ⚠️ **Never expose dangerous functions** (file operations, exec, eval, etc.)
- ⚠️ **Validate and sanitize** all data before passing to context
- ⚠️ **Use execution time limits** for untrusted scripts
- ⚠️ **Review all exposed object methods** for security implications
- ⚠️ **Consider the blast radius** of each exposed function

## Documentation Standards

### Inline Documentation

- **DocBlocks required** for all public methods, complex private methods, classes, and interfaces
- **Type hints** - Always use PHP 8.4 native type hints (prefer over docblock types)
- **Comments** - Explain "why", not "what" (code should be self-documenting)

### Project Documentation

Jekyll-based site in `docs/`:
- `docs/language/` - PHP Script language reference
- `docs/php/` - PHP integration guides
- Hosted via GitHub Pages

**When adding features, update:**
1. Relevant `docs/language/` files
2. Examples in `docs/php/` guides
3. README.md if user-facing
4. Consider adding playground examples

## Attribution

**PHP Script** was created by **Robert Kummer** (post@robert-kummer.de)

- **License**: MIT License
- **Repository**: https://github.com/php-script/php-script
- **Maintainer**: Robert Kummer

## Constitution Authority

This file is generated from `.specify/memory/constitution.md`, which is the **authoritative source** for project principles and standards.

When in doubt, consult the constitution. When the constitution conflicts with code, **the constitution wins** (fix the code, not the constitution—unless the principle itself needs to change via explicit constitutional update).

---

**Last Updated**: 2025-11-18
**Constitution Version**: 1.0.0
**Generated By**: Speckit Framework Integration
