<?php

/**
 * Checks for required PHP extensions for the Mistral PHP Tokenizer
 */

$required = [
    'json' => 'Required for parsing tokenizer model files',
    'mbstring' => 'Required for UTF-8 handling',
];

$recommended = [
    'intl' => 'Recommended for proper text normalization',
];

// Check PHP version
$phpVersion = phpversion();
$phpVersionOk = version_compare($phpVersion, '8.0.0', '>=');

echo "PHP Version Check\n";
echo "----------------\n";
echo "Required: PHP 8.0.0 or higher\n";
echo "Installed: PHP {$phpVersion}\n";
echo "Status: " . ($phpVersionOk ? "OK" : "NOT COMPATIBLE") . "\n\n";

// Check required extensions
echo "Required Extensions\n";
echo "------------------\n";
$allRequiredOk = true;

foreach ($required as $ext => $reason) {
    $loaded = extension_loaded($ext);
    echo "{$ext}: " . ($loaded ? "OK" : "MISSING") . " - {$reason}\n";
    if (!$loaded) {
        $allRequiredOk = false;
    }
}

echo "\n";

// Check recommended extensions
echo "Recommended Extensions\n";
echo "---------------------\n";
$allRecommendedOk = true;

foreach ($recommended as $ext => $reason) {
    $loaded = extension_loaded($ext);
    echo "{$ext}: " . ($loaded ? "OK" : "NOT INSTALLED") . " - {$reason}\n";
    if (!$loaded) {
        $allRecommendedOk = false;
    }
}

echo "\n";

// Overall status
echo "Overall Status\n";
echo "--------------\n";
if (!$phpVersionOk) {
    echo "ERROR: PHP version is not compatible. Please upgrade to PHP 8.0.0 or higher.\n";
    exit(1);
} elseif (!$allRequiredOk) {
    echo "ERROR: Some required extensions are missing. Please install them before using this package.\n";
    exit(1);
} elseif (!$allRecommendedOk) {
    echo "WARNING: Some recommended extensions are not installed. The package will work, but with limited functionality.\n";
    exit(0);
} else {
    echo "All requirements and recommendations are met. The package should work correctly.\n";
    exit(0);
}