---
title: Integrate Editor
parent: PHP
nav_order: 7
layout: default
---

# Integrating the Editor
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Monaco Editor

We support out-of-the-box the [Monaco Editor](https://microsoft.github.io/monaco-editor/).

Therefore we can create the whole language definition and code completion suggestion in the Monarch standard for the
Monaco Editor.

Our [Playground](https://github.com/php-script/php-script/blob/main/public/playground.php) provides a base 
implementation of a pre-configured editor with dynamic code completion and the full language definition syntax.

But we also provide a npm package for a pre-configured Monaco editor written in Typescript soon. You can head over to 
the [repository](https://github.com/php-script/php-script-editor) for a first look.

The base of the server-side rendering is built by the `MonarchLanguageDefinitionService` which can create all necessary
dynamic stuff for the editor.

## Syntax Highlighting

The Monaco Editor integration includes syntax highlighting for all PHP Script keywords, including:

- Control flow keywords: `if`, `else`, `for`, `foreach`, `break`, `continue`
- Operators and literals: `true`, `false`, `null`, `LINEBREAK`
- Statement keywords: `echo`, `return`, `as`

The `break` and `continue` keywords are automatically highlighted as keywords, making them visually distinct in the editor.

## Code Completion

The editor provides intelligent code completion suggestions based on context:

### Loop Control Snippets

When writing code inside loops, the editor suggests loop control snippets:

- **`break`** - Exit the current loop
  ```javascript
  break;
  ```

- **`break (nested)`** - Exit multiple nested loops
  ```javascript
  break 2;
  ```

- **`continue`** - Skip to the next iteration
  ```javascript
  continue;
  ```

- **`continue (nested)`** - Continue to the next iteration of an outer loop
  ```javascript
  continue 2;
  ```

### Control Flow Snippets

The editor also provides snippets for other control flow structures:

- **`for`** - For loop template
- **`foreach`** - Foreach loop template
- **`if`** - If statement template
- **`ifelse`** - If-else statement template

All snippets include tab stops for easy navigation and placeholder text to guide developers.

## Linting and Validation

The `LinterService` validates PHP Script code in real-time, catching errors before execution:

### Break/Continue Validation

The linter detects invalid usage of `break` and `continue`:

- ⚠️ **Outside loop context**: Using `break` or `continue` outside of a loop produces a runtime error
- ⚠️ **Invalid level**: Using a level that exceeds the available loop nesting depth
- ⚠️ **Invalid parameters**: Using `break 0`, negative levels, or non-integer levels

These validations help developers catch errors early in the development process, improving the overall development experience.
