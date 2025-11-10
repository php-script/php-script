<?php
use PhpScript\Core\Engine;
use PhpScript\Exceptions\EngineException;

require_once __DIR__ . '/../vendor/autoload.php';

class LoginStats
{
    public function count(): int
    {
        return 42;
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
        echo json_encode(['success' => true, 'output' => $output]);
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
    <h1 class="text-2xl font-bold mb-4">PHP Playground</h1>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <form method="post" id="playground-form">
                <label for="editor" class="block text-xl font-bold mb-2">PHP Script</label>
                <div id="editor" style="height: 400px;" class="border-gray-300"></div>
                <div id="messages" class="text-red-400"></div>
                <button type="button" id="run-button" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Run</button>
            </form>
        </div>
        <div>
            <h2 class="text-xl font-bold mb-2">Result</h2>
            <div class="bg-white p-4 border border-gray-300 rounded-md h-full" id="output-container"></div>
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

            // **********************************************************
            // * HIER IST DIE ÄNDERUNG FÜR SNIPPETS
            // **********************************************************
            if (item.snippet) {
                // Wir haben ein Snippet!
                suggestion.insertText = item.snippet;
                // WICHTIG: Sagen Sie Monaco, dass dies ein Snippet ist
                suggestion.insertTextRules = monaco.languages.CompletionItemInsertTextRule.InsertAsSnippet;
            } else {
                // Fallback auf normalen Text
                suggestion.insertText = item.insertText || item.label;
                suggestion.insertTextRules = monaco.languages.CompletionItemInsertTextRule.KeepWhitespace;
            }
            // **********************************************************

            return suggestion;
        }

        //const completionItems = <?php // echo $engine->monarchLanguageDefinition()->getCompletionItems();?>//;
        monaco.languages.registerCompletionItemProvider('php-script', {
            triggerCharacters: ['.'],

            provideCompletionItems: function(model, position, context) {
                const triggerKind = context.triggerKind;
                const triggerChar = context.triggerCharacter;

                // --- Fall 1: Benutzer hat explizit '.' getippt ---
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

                    // Kette auflösen
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
                                // Versuchen, den Rückgabetyp aus "detail" zu parsen
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

                    // Vorschläge generieren
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

                // --- Fall 2: Standard-Vorschläge (Globale Vars, Funktionen, Keywords) ---
                if (triggerKind === monaco.languages.CompletionTriggerKind.Invoke ||
                    triggerKind === monaco.languages.CompletionTriggerKind.TriggerForIncompleteCompletions) {

                    const word = model.getWordUntilPosition(position);
                    const replacementRange = {
                        startLineNumber: position.lineNumber,
                        endLineNumber: position.lineNumber,
                        startColumn: word.startColumn,
                        endColumn: word.endColumn
                    };

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

                    return { suggestions: [...globalVars, ...globalFuncs, ...staticKeywords] };
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
                enabled: true,
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

        // Button-Zustand
        runButton.disabled = true;
        runButton.textContent = 'Executing...';
        outputContainer.textContent = ''; // Ausgabe löschen

        // Alte Fehlermarkierungen löschen
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
                outputContainer.textContent = result.output;
                outputContainer.classList.remove('text-red-400');
            } else {
                // Das ist der Fehlerfall vom Backend
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
     * Setzt oder löscht Fehlermarkierungen im Monaco Editor.
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
