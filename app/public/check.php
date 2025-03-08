<?php
// Turn on all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Function to check if a file exists and is readable
function checkFile($path) {
    $fullPath = BASE_PATH . $path;
    $exists = file_exists($fullPath);
    $readable = is_readable($fullPath);
    $case_sensitive_path = strtolower($fullPath) === strtolower(realpath($fullPath)) ? 'Correct case' : 'Case mismatch';
    
    return [
        'path' => $path,
        'exists' => $exists ? 'Yes' : 'No',
        'readable' => $readable ? 'Yes' : 'No',
        'case_check' => $exists ? $case_sensitive_path : 'N/A',
        'real_path' => $exists ? realpath($fullPath) : 'N/A'
    ];
}

// Files to check
$files = [
    '/src/includes/include-all.php',
    '/src/includes/head.php',
    '/src/includes/scripts.php',
    '/src/Includes/scripts.php', // Note the uppercase I to check case sensitivity
    '/config/config.php'
];

// Check each file
$results = [];
foreach ($files as $file) {
    $results[] = checkFile($file);
}

// Check PHP settings
$phpSettings = [
    'PHP Version' => phpversion(),
    'output_buffering' => ini_get('output_buffering'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
    'include_path' => ini_get('include_path'),
    'Case Sensitive Filesystem' => (DIRECTORY_SEPARATOR === '\\') ? 'No (Windows)' : 'Yes (Unix/Linux)'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Environment Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h1, h2 { color: #333; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; text-align: left; }
        tr:nth-child(even) { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>PHP Environment Check</h1>
    
    <h2>PHP Settings</h2>
    <table>
        <tr>
            <th>Setting</th>
            <th>Value</th>
        </tr>
        <?php foreach ($phpSettings as $key => $value): ?>
        <tr>
            <td><?= htmlspecialchars($key) ?></td>
            <td><?= htmlspecialchars($value) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>File Checks</h2>
    <table>
        <tr>
            <th>File Path</th>
            <th>Exists</th>
            <th>Readable</th>
            <th>Case Check</th>
            <th>Real Path</th>
        </tr>
        <?php foreach ($results as $result): ?>
        <tr>
            <td><?= htmlspecialchars($result['path']) ?></td>
            <td><?= $result['exists'] ?></td>
            <td><?= $result['readable'] ?></td>
            <td><?= $result['case_check'] ?></td>
            <td style="word-break: break-all;"><?= htmlspecialchars($result['real_path']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
