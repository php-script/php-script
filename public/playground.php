<?php
use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;

require_once __DIR__ . '/../vendor/autoload.php';

class LoginStats
{
    public int $countLogins = 42;

    public function count(): int
    {
        return $this->countLogins;
    }

    public function increment(): void
    {
        $this->countLogins++;
    }
}

class User
{
    public string $name = 'Administrator';

    public LoginStats $logins;

    public function __construct()
    {
        $this->logins = new LoginStats;
    }

    public function login()
    {
        $this->logins->increment();
    }

    public function hasPermission(string $perm): bool
    {
        return $perm === 'admin';
    }
}

$engine = new Engine;
$engine->allow('count')
    ->set('user', new User, 'User instance')
    ->set('app_version', '1.0.0', 'Application version')
    ->set('users_list', ['Alice', 'Bob', 'Charlie'], 'List of users');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = $input['code'] ?? '';

    header('Content-Type: application/json');

    try {
        $output = $engine->execute($code);
        $linted = $engine->linter($code)->linted();
        echo json_encode(['success' => true, 'output' => $output, 'linted' => $linted]);
    } catch (EngineException $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => $e->getMessage(),
                'line' => $e->line,
                'column' => $e->column,
                'length' => $e->length,
                'offset' => $e->offset,
            ],
        ]);
    } catch (Throwable $e) {
        http_response_code(500); // Internal Server Error
        echo json_encode([
            'success' => false,
            'error' => [
                'message' => 'An unexpected error was thrown: ' . $e->getMessage(),
                'line' => 0,
                'column' => 0,
                'length' => 1,
            ],
        ]);
    }

    exit;
}

// render frontend code
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Script Playground</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/monaco-editor@0.54.0/min/vs/editor/editor.main.min.css">
</head>
<body class="bg-gray-100 text-gray-800">
<div class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-bold">PHP Script Playground</h1>
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 1024 1024"><path d="M271.5 190c-21.4 4.3-41.1 22.2-47.8 43.5l-2.2 7v266c0 291.2-.4 270.4 6.1 284 5.9 12.5 16.4 22.9 29.6 29.6 12.3 6.2 1.2 5.9 198.8 5.9 196.8 0 180.7.4 197.5-4.7 1.3-.4-2.4-2.1-11.5-5.2-23.7-8.2-40.4-17.7-48.6-27.5l-3.8-4.6H434.2c-151.7 0-155.4 0-159.1-1.9-5-2.6-9.8-8.2-11.1-13-.7-2.7-.9-84.7-.8-263.8l.3-260 3-4.8c2-3.2 4.5-5.6 7.4-7.2l4.4-2.3H577v35.9c0 32.4.2 36.5 1.9 42 4.7 15.2 16.7 26.6 33 31.5 4.6 1.3 11.3 1.6 42.3 1.6H691v75h41v-43.3c-.1-42.1-.1-43.4-2.3-49.8-4.7-13.7-6.2-15.4-62.7-72-48.1-48.1-54.2-53.9-60-56.7-13.5-6.6-2.6-6.2-175-6.1-86.1.1-158.3.5-160.5.9z"/><path d="M498.1 341.2c-1.9 1.2-4.2 3.4-5.2 4.8s-16.6 40.3-34.6 86.5c-30 76.9-32.7 84.5-32.8 90.1 0 5.1.4 6.6 2.7 9.6 3.8 5 8.8 7.1 15.4 6.6 7.6-.7 11.4-3.9 15.3-13.2 12.2-29.3 65.1-167.5 65.1-170-.1-4.6-3.3-11-7.1-13.7-5-3.5-14.2-3.9-18.8-.7zM558.3 368.9c-9.9 2.5-15.7 12.1-13.9 22.8.6 3.3 3.6 6.6 23.7 26.4l23.1 22.6-21.6 21.3c-23.6 23.3-26.2 26.9-25.3 34.6.6 5.8 4.7 11.8 9.7 14.4 4.6 2.4 12.9 2.7 16.8.6 1.5-.8 16.5-15.3 33.4-32.3 26.5-26.7 30.7-31.3 31.3-34.6 1.7-8.9 1.7-8.8-30.8-41.5-16.6-16.7-31.5-31.2-33.2-32.2-3.7-2.3-9-3.1-13.2-2.1zM383.4 370.3c-1.2.7-16.4 15.1-33.7 32.2-27.6 27.1-31.6 31.5-32.2 34.7-1.7 9-1.7 8.9 30.3 41.1 29.1 29.2 34.4 33.8 40.9 35.2 7.2 1.5 16-3.5 19.4-11 4.6-10.1 3-12.9-23.4-39.2L362.5 441l23.2-23.2c19.8-19.9 23.2-23.8 23.8-27.1 2.2-11.7-6.3-21.7-18.5-21.6-3 0-6.5.5-7.6 1.2zM708.5 466.1c-18.2 2.6-38.5 12-53.3 24.7-12.2 10.5-22.9 27.3-27.4 42.8-2 6.9-2.3 10.1-2.3 25.4 0 16.6.2 17.9 2.8 25.5 10.6 30.1 29.7 45.6 79.2 64 26.2 9.8 39.1 16.2 45.9 22.9 12.4 12.1 14.8 28.9 6.4 44.9-13.6 25.6-55.3 29.9-103.6 10.6-10.1-4-13.4-4.9-18.5-4.9-21.4 0-31 25.5-15.5 41.1 6.4 6.4 31.7 16.4 52.9 21.1 9.1 1.9 13.3 2.2 33.4 2.2 20.1.1 24.2-.2 32.1-2.1 32.2-7.6 56-25.8 68-51.9 10.9-23.5 9.7-57.4-2.8-78.1-13.6-22.6-32.6-35.3-79.3-52.8-20.8-7.8-33.5-14-39.7-19.5-15.2-13.3-14.6-39.3 1.1-55 5.6-5.6 11.5-8.8 22.1-12.1 14-4.4 38.1-2.6 61.1 4.6 6.1 1.9 12.5 3.5 14.2 3.5 20.2 0 30.7-27.3 16-41.2-5.7-5.4-28.9-12.5-49.9-15.3-12.3-1.6-33.1-1.8-42.9-.4zM329 594.2c-4.9 1.4-9.4 5-11.4 9.1-2 4.3-2.1 12.9-.2 16.6 2.2 4.1 8.1 8.9 11.8 9.6 1.8.3 56.2.4 120.9.3l117.6-.3 3.6-2.8c10.3-7.9 10-23.5-.6-30.5l-4.1-2.7-117-.2c-76-.1-118.3.2-120.6.9zM328.7 672c-7.5 2.3-12.7 9.3-12.7 17.3 0 8.2 3.3 13.5 10.5 16.8 3.8 1.8 8.7 1.9 96.3 1.9 75.3 0 92.9-.2 95.5-1.4 5.7-2.3 11.7-11.1 11.7-17.1 0-6.2-5.3-13.8-11.5-16.6-3.8-1.8-8.8-1.9-95.5-1.8-50.3 0-92.8.5-94.3.9z"/></svg>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <form method="post" id="playground-form">
                <label for="editor" class="block text-xl font-bold mb-2">PHP Script</label>
                <div id="editor" style="height: 400px;" class="border-gray-300"></div>
                <div id="messages" class="text-red-400"></div>
                <div class="flex items-center mt-4 gap-4">
                    <button type="button" id="run-button" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Run</button>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" id="apply-linted-code" class="size-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                        Use the linted code from engine
                    </label>
                </div>
            </form>
        </div>
        <div>
            <h2 class="text-xl font-bold mb-2">Result</h2>
            <div class="bg-white p-4 border border-gray-300 rounded-md h-[400px]" id="output-container"></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.54.0/min/vs/loader.min.js"></script>
<script>
    const suggestionModel = <?php echo json_encode($engine->monarchLanguageDefinition()->getCompletionItems()); ?>;

    require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.54.0/min/vs' }});

    require(['vs/editor/editor.main'], function() {
        // Register a new language
        monaco.languages.register({ id: 'php-script' });

        const phpScriptLanguageDef = <?php echo json_encode($engine->monarchLanguageDefinition()->getDefinition()); ?>;

        // Register a tokens provider for the language
        monaco.languages.setMonarchTokensProvider('php-script', phpScriptLanguageDef);

        // suggestion provider
        const kinds = {
            'Function': monaco.languages.CompletionItemKind.Function,
            'Method': monaco.languages.CompletionItemKind.Method,
            'Variable': monaco.languages.CompletionItemKind.Variable,
            'Property': monaco.languages.CompletionItemKind.Property,
            'Keyword': monaco.languages.CompletionItemKind.Keyword,
        };

        function createSuggestion(item, range) {
            const suggestion = {
                label: item.label,
                kind: kinds[item.kind] || monaco.languages.CompletionItemKind.Text,
                detail: item.detail || '',
                documentation: item.doc || '',
                range: range
            };

            if (item.snippet) {
                suggestion.insertText = item.snippet;
                suggestion.insertTextRules = monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet;
            } else {
                suggestion.insertText = item.insertText || item.label;
                suggestion.insertTextRules = monaco.languages.CompletionItemInsertTextRule.KeepWhitespace;
            }

            return suggestion;
        }

        monaco.languages.registerCompletionItemProvider('php-script', {
            triggerCharacters: ['.'],

            provideCompletionItems: function(model, position, context) {
                const triggerKind = context.triggerKind;
                const triggerChar = context.triggerCharacter;

                // --- 1: user hits '.' ---
                if (triggerKind === monaco.languages.CompletionTriggerKind.TriggerCharacter && triggerChar === '.') {

                    const lineContent = model.getLineContent(position.lineNumber);
                    const textBeforeDot = lineContent.substring(0, position.column - 1);

                    const chainMatch = textBeforeDot.match(/([a-zA-Z_][\w\.]*)$/);
                    if (!chainMatch) return { suggestions: [] };

                    let chainStr = chainMatch[1];

                    if (chainStr.endsWith('.')) {
                        chainStr = chainStr.substring(0, chainStr.length - 1);
                    }

                    const chain = chainStr.split('.');

                    // solve chain
                    let currentClassName = null;
                    const startVarName = chain[0];
                    const variable = suggestionModel.globalVariables.find(v => v.label === startVarName);

                    if (!variable) return { suggestions: [] };
                    currentClassName = variable.detail;

                    for (let i = 1; i < chain.length; i++) {
                        const propName = chain[i];
                        if (!currentClassName) return { suggestions: [] };

                        const classDef = suggestionModel.classes[currentClassName];
                        if (!classDef) return { suggestions: [] };

                        const prop = classDef.properties.find(p => p.label === propName);
                        if (prop) {
                            currentClassName = prop.detail;
                        } else {
                            const method = classDef.methods.find(m => m.label === propName);
                            if (method && method.detail) {
                                const returnTypeMatch = method.detail.match(/:\s*(\w+)$/);
                                if (returnTypeMatch && suggestionModel.classes[returnTypeMatch[1]]) {
                                    currentClassName = returnTypeMatch[1];
                                } else {
                                    return { suggestions: [] };
                                }
                            } else {
                                return { suggestions: [] };
                            }
                        }
                    }

                    const finalClassDef = suggestionModel.classes[currentClassName];
                    if (finalClassDef) {
                        const replacementRange = {
                            startLineNumber: position.lineNumber,
                            endLineNumber: position.lineNumber,
                            startColumn: position.column,
                            endColumn: position.column
                        };

                        const suggestions = [
                            ...finalClassDef.properties.map(p => createSuggestion(p, replacementRange)),
                            ...finalClassDef.methods.map(m => createSuggestion(m, replacementRange))
                        ];
                        return { suggestions: suggestions, incomplete: false };
                    }

                    return { suggestions: [] };
                }

                // --- 2: suggestions (local and global vars, functions, keywords) ---
                if (triggerKind === monaco.languages.CompletionTriggerKind.Invoke ||
                    triggerKind === monaco.languages.CompletionTriggerKind.TriggerForIncompleteCompletions) {

                    const word = model.getWordUntilPosition(position);
                    const replacementRange = {
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: word.startColumn,
                        endColumn: word.endColumn
                    };

                    // Find local variables
                    const code = model.getValue();
                    const localVars = [];
                    const regex = /(?:^|[^.\w])([a-zA-Z_]\w*)\s*=/g;
                    let match;
                    while ((match = regex.exec(code)) !== null) {
                        const varName = match[1];
                        if (!localVars.some(v => v.label === varName)) {
                            localVars.push(createSuggestion({
                                label: varName,
                                kind: 'Variable',
                                detail: 'Local Variable'
                            }, replacementRange));
                        }
                    }

                    const globalVars = suggestionModel.globalVariables.map(v => createSuggestion(v, replacementRange));
                    const globalFuncs = suggestionModel.globalFunctions.map(f => createSuggestion(f, replacementRange));

                    const staticKeywords = phpScriptLanguageDef.keywords.map(k => ({
                        label: k,
                        kind: kinds.Keyword,
                        insertText: k,
                        range: replacementRange,
                        detail: 'Keyword',
                        insertTextRules: monaco.languages.CompletionItemInsertTextRule.KeepWhitespace
                    }));

                    return { suggestions: [...localVars, ...globalVars, ...globalFuncs, ...staticKeywords] };
                }

                return { suggestions: [] };
            }
        });

        const editor = monaco.editor.create(document.getElementById('editor'), {
            value: '',
            language: 'php-script',
            theme: "vs-light",
            automaticLayout: true,
            roundedSelection: true,
            scrollBeyondLastLine: false,
            minimap: {
                enabled: false,
            },
            snippetSuggestions: 'inline',
        });

        document.getElementById('run-button').addEventListener('click', function () {
            executeCode(editor);
        });
    });

    async function executeCode(editor) {
        const code = editor.getValue();
        const outputContainer = document.getElementById('output-container');
        const runButton = document.getElementById('run-button');
        const applyLintedCode = document.getElementById('apply-linted-code');

        // Button
        runButton.disabled = true;
        runButton.textContent = 'Executing...';
        outputContainer.textContent = '';

        setErrorMarkers(editor.getModel(), null);

        try {
            const response = await fetch('/playground.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            });

            const result = await response.json();

            if (result.success) {
                outputContainer.innerHTML = result.output.replace(/\n/g, '<br>');
                outputContainer.classList.remove('text-red-400');
                if (applyLintedCode.checked) {
                    editor.getModel().setValue(result.linted);
                }
            } else {
                outputContainer.textContent = result.error.message;
                outputContainer.classList.add('text-red-400');

                setErrorMarkers(editor.getModel(), result.error);
            }

        } catch (networkError) {
            outputContainer.textContent = 'Network error: ' + networkError.message;
            outputContainer.classList.add('text-red-400');
        } finally {
            runButton.disabled = false;
            runButton.textContent = 'Run';
        }
    }

    /**
     * set or delete error mark
     * @param {object} model - Das Fehlerobjekt vom Backend oder null zum Löschen.
     * @param {object|null} error - Das Fehlerobjekt vom Backend oder null zum Löschen.
     * @param {string} error.message - Die Fehlermeldung.
     * @param {number} error.line - Die Startzeile (1-basiert).
     * @param {number} error.column - Die Startspalte (1-basiert).
     * @param {number} error.length - Die Länge des Fehlers.
     */
    function setErrorMarkers(model, error) {
        if (!model) return;

        const messagesContainer = document.getElementById('messages');

        if (error && error.line > 0) {
            const marker = {
                message: error.message,
                severity: monaco.MarkerSeverity.Error,
                startLineNumber: error.line,
                startColumn: error.column,
                endLineNumber: error.line,
                // Die Endspalte ist Startspalte + Länge
                // Wir stellen sicher, dass die Länge mindestens 1 ist,
                // falls 0 zurückkommt, damit Monaco etwas markiert.
                endColumn: error.column + (error.length || 1)
            };
            // Setze die neue Markierung
            monaco.editor.setModelMarkers(model, 'php-script-owner', [marker]);

            messagesContainer.textContent = error.message;
        } else {
            // Lösche alle alten Markierungen
            monaco.editor.setModelMarkers(model, 'php-script-owner', []);

            messagesContainer.textContent = '';
        }
    }
</script>
</body>
</html>
