---
title: Integrate Editor
parent: PHP
nav_order: 9
layout: default
---

# Integrating the Editor
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Editor packages

**Full documentation: [php-script.github.io/php-script-editor](https://php-script.github.io/php-script-editor/)**

[`<php-script-editor>`](https://php-script.github.io/php-script-editor/packages/editor.html) is a
ready-made Monaco editor for PHP Script. It loads the language definition and completions from your
`Engine`, and has saving, lint markers, light and dark themes and form support built in. Version
0.1.0 ships these packages:

| Package | For | Install |
| --- | --- | --- |
| [`@php-script/editor`](https://php-script.github.io/php-script-editor/packages/editor.html) | The web component (vanilla JS, Angular, Solid, Alpine) | `npm i @php-script/editor monaco-editor` |
| [`@php-script/editor-react`](https://php-script.github.io/php-script-editor/packages/react.html) | React 18 and 19, Next.js | `npm i @php-script/editor-react monaco-editor` |
| [`@php-script/editor-vue`](https://php-script.github.io/php-script-editor/packages/vue.html) | Vue 3 and Nuxt | `npm i @php-script/editor-vue monaco-editor` |
| [`@php-script/editor-svelte`](https://php-script.github.io/php-script-editor/packages/svelte.html) | Svelte 5 and SvelteKit | `npm i @php-script/editor-svelte monaco-editor` |
| [`php-script/editor-laravel`](https://php-script.github.io/php-script-editor/packages/laravel.html) | Laravel: Blade component, published assets, lint responses, validation rule | `composer require php-script/editor-laravel` |
| [`php-script/editor-filament`](https://php-script.github.io/php-script-editor/packages/filament.html) | Filament 5 form field | `composer require php-script/editor-filament` |

### Minimal example

Serve the editor config from your engine:

```php
use PhpScript\Core\Engine;

$engine = new Engine()
    ->allow('strtoupper')
    ->set('user', $user, 'The current user');

$service = $engine->monarchLanguageDefinition();

header('Content-Type: application/json');
echo json_encode([
    'definition' => $service->getDefinition(),
    'completions' => $service->getCompletionItems(),
]);
```

Then use the element:

```js
import '@php-script/editor';
```

```html
<php-script-editor
  config-url="/php-script/config"
  save-url="/scripts"
  lint-url="/scripts/lint"
  value='echo strtoupper(user.name);'
></php-script-editor>
```

With Laravel, `composer require php-script/editor-laravel` gives you `EditorConfig::fromEngine($engine)`
and an `<x-php-script-editor :config="$engine" />` Blade component. See
[Getting started](https://php-script.github.io/php-script-editor/getting-started.html) and
[Server and language config](https://php-script.github.io/php-script-editor/server.html) for the
endpoints.

The rest of this page explains what the packages do under the hood, for a custom Monaco integration.

## Monaco Editor

PHP Script supports the [Monaco Editor](https://microsoft.github.io/monaco-editor/) out of the box.
It creates the whole language definition and the code completion suggestions in Monaco's Monarch
format.

Our [Playground](https://github.com/php-script/php-script/blob/main/public/playground.php) is a
minimal implementation of a pre-configured editor with dynamic code completion and the full
language definition. The [editor packages](#editor-packages) are the maintained, ready-to-use
version of it.

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

When writing code inside loops, the editor can suggest these loop control snippets for nested
loops:

- **`break 2`** - Exit two nested loops
- **`continue 2`** - Continue with the next iteration of the outer loop

Plain `break` and `continue` come as keywords in `loopKeywords`.

### Control Flow Snippets

The editor also provides snippets for other control flow structures:

- **`for`** - For loop template
- **`foreach`** - Foreach loop template
- **`foreach (key, value)`** - Foreach loop template with key and value
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
//     'controlFlows' => [...],      // Always show: for, foreach, foreach (key, value), if, ifelse
//     'loopControls' => [...],      // Only show inside loops: break 2, continue 2
//     'loopKeywords' => ['break', 'continue']  // Keywords to add dynamically inside loops
// ]
```

`getDefinition()` returns `break` and `continue` in a separate `loopKeywords` list, not in
`keywords`. The tokenizer highlights them as keywords everywhere; offer them as completion
suggestions only inside a loop.

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
            // Add loop control snippets (break 2, continue 2)
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

The `LinterService` finds errors before a script runs. `diagnostics()` returns `LintDiagnostic`
objects whose 1-based `line` and `column` map directly to Monaco markers. See
[Linting](./linting.html) for the rules.

Return the diagnostics from an endpoint:

```php
$diagnostics = array_map(fn (LintDiagnostic $diagnostic): array => [
    'rule' => $diagnostic->rule,
    'message' => $diagnostic->message,
    'line' => $diagnostic->line,
    'column' => $diagnostic->column,
    'length' => $diagnostic->length,
    'severity' => $diagnostic->severity,
], $engine->linter($script)->diagnostics());

echo json_encode($diagnostics);
```

Set them as markers in the editor:

```javascript
const markers = diagnostics.map(d => ({
    startLineNumber: d.line,
    startColumn: d.column,
    endLineNumber: d.line,
    endColumn: d.column + Math.max(d.length, 1),
    message: d.message,
    source: d.rule,
    severity: d.severity === 'error'
        ? monaco.MarkerSeverity.Error
        : monaco.MarkerSeverity.Warning,
}));

monaco.editor.setModelMarkers(editor.getModel(), 'php-script', markers);
```

The linter also reports `break` and `continue` outside a loop, levels deeper than the loop
nesting, and invalid levels such as `break 0`.
