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

### Context-Aware Loop Control Snippets

Break and continue statements are **context-aware** - they are provided in a separate `loopControls` category that frontend implementations should only display when the user is inside a `for` or `foreach` loop. This prevents suggesting invalid code and improves the developer experience.

When writing code inside loops, the editor can suggest these loop control snippets:

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

### Implementing Context-Aware Completion

When integrating the Monaco Editor with PHP Script, the completion items are organized into these categories:

```php
$completionItems = $service->getCompletionItems();
// Returns:
// [
//     'globalFunctions' => [...],
//     'globalVariables' => [...],
//     'classes' => [...],
//     'controlFlows' => [...],      // Always show: for, foreach, if, ifelse
//     'loopControls' => [...],      // Only show inside loops: break, continue (4 snippets)
//     'loopKeywords' => ['break', 'continue']  // Keywords to add dynamically inside loops
// ]
```

**Important**: The `break` and `continue` keywords are **NOT** in the static `keywords` array returned by `getDefinition()`. They must be added dynamically by the frontend when inside a loop.

#### Step 1: Detect Loop Context

Implement a function to detect if the cursor is inside a `for` or `foreach` loop:

```javascript
function isInsideLoop(model, position) {
    const code = model.getValueInRange({
        startLineNumber: 1,
        startColumn: 1,
        endLineNumber: position.lineNumber,
        endColumn: position.column
    });

    let braceDepth = 0;
    let loopDepth = 0;
    const tokens = code.split(/(\bfor\b|\bforeach\b|\{|\})/);

    for (let i = 0; i < tokens.length; i++) {
        const token = tokens[i];
        if (token === 'for' || token === 'foreach') {
            const remainingText = tokens.slice(i).join('');
            const nextBrace = remainingText.indexOf('{');
            if (nextBrace !== -1) {
                loopDepth++;
            }
        } else if (token === '{') {
            braceDepth++;
        } else if (token === '}') {
            braceDepth--;
            if (loopDepth > 0 && braceDepth < loopDepth) {
                loopDepth--;
            }
        }
    }

    return loopDepth > 0;
}
```

#### Step 2: Conditionally Add Loop Suggestions

In your completion provider, check the loop context before adding loop-related suggestions:

```javascript
monaco.languages.registerCompletionItemProvider('php-script', {
    provideCompletionItems: function(model, position, context) {
        const suggestions = [
            ...globalVariables,
            ...globalFunctions,
            ...controlFlows,
            ...staticKeywords
        ];

        // Check if we're inside a loop
        const isInLoop = isInsideLoop(model, position);

        if (isInLoop) {
            // Add loop control snippets (break, break 2, continue, continue 2)
            suggestions.push(...loopControls);

            // Add loop keywords (break, continue) as keyword suggestions
            const loopKeywords = completionItems.loopKeywords.map(k => ({
                label: k,
                kind: monaco.languages.CompletionItemKind.Keyword,
                insertText: k,
                detail: 'Loop Control Keyword'
            }));
            suggestions.push(...loopKeywords);
        }

        return { suggestions };
    }
});
```

#### Complete Implementation Example

See `public/playground.php` for a complete working implementation that demonstrates:
- Dynamic keyword registration based on loop context
- Loop depth tracking with nested loop support
- Integration with Monaco's completion provider API
- Proper syntax highlighting and IntelliSense for loop control statements

This ensures that `break` and `continue` suggestions only appear in valid contexts, reducing errors and improving code quality.

## Linting and Validation

The `LinterService` validates PHP Script code in real-time, catching errors before execution:

### Break/Continue Validation

The linter detects invalid usage of `break` and `continue`:

- ⚠️ **Outside loop context**: Using `break` or `continue` outside of a loop produces a runtime error
- ⚠️ **Invalid level**: Using a level that exceeds the available loop nesting depth
- ⚠️ **Invalid parameters**: Using `break 0`, negative levels, or non-integer levels

These validations help developers catch errors early in the development process, improving the overall development experience.
