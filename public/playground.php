<?php
use PhpScript\Core\Engine;
use PhpScript\Core\MonarchLanguageDefinitionService;

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
    public string $name = "Administrator";
    public LoginStats $logins;

    public function __construct()
    {
        $this->logins = new LoginStats();
    }

    public function hasPermission(string $perm): bool
    {
        return $perm === 'admin';
    }
}

$code = '';
$hasErrors = false;
$engine = new Engine;
$engine->allow('count')
    ->set('user', new User)
    ->set('app_version', '1.0.0')
    ->set('users_list', ['Alice', 'Bob', 'Charlie'])
;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    ob_start();
    try {
        echo $engine->execute($code);
    } catch (Throwable $e) {
        $hasErrors = true;
        echo $e->getMessage();
    }
    $output = ob_get_clean();
}
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
                <label for="code" class="block text-xl font-bold mb-2">PHP Script</label>
                <div id="editor" style="height: 400px; border: 1px solid #d1d5db; border-radius: 0.375rem;" class="<?php echo $hasErrors ? 'border-red-300' : 'border-gray-300' ?>"></div>
                <input type="hidden" name="code" id="code">
                <button type="submit" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Run &gt;&gt;</button>
            </form>
        </div>
        <div>
            <h2 class="text-xl font-bold mb-2">Result</h2>
            <div class="bg-white p-4 border border-gray-300 rounded-md h-full">
                <?php echo nl2br(htmlspecialchars($output ?? '')); ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/monaco-editor@0.54.0/min/vs/loader.min.js"></script>
<script>
    require.config({ paths: { 'vs': 'https://cdn.jsdelivr.net/npm/monaco-editor@0.54.0/min/vs' }});
    require(['vs/editor/editor.main'], function() {
        // Register a new language
        monaco.languages.register({ id: 'php-script' });

        // Register a tokens provider for the language
        monaco.languages.setMonarchTokensProvider('php-script', <?php echo json_encode($engine->monarchLanguageDefinition()->getDefinition()); ?>);

        const editor = monaco.editor.create(document.getElementById('editor'), {
            value: `<?php echo $code; ?>`,
            language: 'php-script'
        });

        document.getElementById('playground-form').addEventListener('submit', function() {
            document.getElementById('code').value = editor.getValue();
        });
    });
</script>
</body>
</html>
