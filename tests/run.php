<?php

declare(strict_types=1);

require __DIR__ . '/TestCase.php';

ob_start();

$tests = [
    'ConfigTest',
    'RouterTest',
    'CsrfTest',
    'PasswordTest',
    'WorkspaceServiceTest',
];

$failures = 0;
$totalAssertions = 0;

foreach ($tests as $testClass) {
    require __DIR__ . '/' . $testClass . '.php';
    $test = new $testClass();

    try {
        $test->run();
        $totalAssertions += $test->assertions();
        echo "[PASS] {$testClass}\n";
    } catch (Throwable $e) {
        $failures++;
        echo "[FAIL] {$testClass}: {$e->getMessage()}\n";
    }
}

echo "Assertions: {$totalAssertions}\n";

echo $failures === 0 ? "All tests passed.\n" : "Failures: {$failures}\n";

ob_end_flush();

exit($failures === 0 ? 0 : 1);
