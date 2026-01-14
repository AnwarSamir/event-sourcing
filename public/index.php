<?php

use App\Kernel;

// Suppress PHP 8.4 deprecation warnings for Symfony 6.1 compatibility
// Turn off error display completely
@ini_set('display_errors', '0');
@ini_set('log_errors', '0');
@ini_set('assert.warning', '0');

// Set error handler to completely suppress deprecation warnings
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Suppress all deprecation-related warnings
    if (in_array($errno, [E_DEPRECATED, E_USER_DEPRECATED], true)) {
        return true;
    }
    // Suppress assert.warning ini_set deprecation
    if (strpos($errstr, 'assert.warning') !== false || strpos($errstr, 'E_STRICT') !== false) {
        return true;
    }
    return false;
}, E_ALL);

// Set error reporting to exclude deprecations
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Start output buffering to filter deprecation warnings from output
ob_start(function ($buffer) {
    if (empty($buffer)) {
        return $buffer;
    }
    // Remove all deprecation warning patterns from HTML output
    // Pattern 1: <br /><b>Deprecated</b>: ... <br />
    $buffer = preg_replace('/<br\s*\/?>\s*<b>Deprecated<\/b>.*?<br\s*\/?>/is', '', $buffer);
    // Pattern 2: Deprecated: ... on line X<br />
    $buffer = preg_replace('/Deprecated:.*?on line \d+.*?<br\s*\/?>/is', '', $buffer);
    // Pattern 3: Multiple line deprecation warnings
    $buffer = preg_replace('/(<br\s*\/?>[\s\n]*)*Deprecated:.*?(<br\s*\/?>[\s\n]*)*/is', '', $buffer);
    // Pattern 4: Standalone deprecation messages
    $buffer = preg_replace('/Deprecated:.*?(?=<|$)/is', '', $buffer);
    return $buffer;
});

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
