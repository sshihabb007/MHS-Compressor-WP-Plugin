<?php
header('Content-Type: text/plain');
$dir = 'D:/xampp/htdocs/WPTEST/wp-content/plugins/compressor';
chdir($dir);

echo "Current Directory: " . getcwd() . "\n\n";

$commands = [
    'git add .',
    'git commit -m "feat: Pro Features — Dynamic Pipeline, Smart Quality, Bulk Async, Backup-Restore, SVG Dir Smush"',
    'git push'
];

foreach ($commands as $cmd) {
    echo "Running: $cmd\n";
    $output = shell_exec($cmd . " 2>&1");
    echo $output . "\n";
    echo "------------------\n";
}
