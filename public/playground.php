<?php
use PhpScript\Core\Engine;

require_once __DIR__.'/../vendor/autoload.php';

$hasErrors = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    ob_start();
    try {
        $engine = new Engine;
        echo $engine->execute($_POST['code']);
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
</head>
<body class="bg-gray-100 text-gray-800">
<div class="container mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">PHP Playground</h1>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <form method="post">
                <label for="code" class="block text-xl font-bold mb-2">PHP Script</label>
                <textarea name="code" id="code" rows="20" class="w-full p-2 border rounded-md <?php echo $hasErrors ? 'border-red-300' : 'border-gray-300' ?>"><?php echo htmlspecialchars($_POST['code'] ?? ''); ?></textarea>
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
</body>
</html>
