#!/usr/bin/env php
<?php
/**
 * Coverage Text Reporter (LCOV Workflow Compatibility)
 * * Extracts global line coverage from PHPUnit's console text output 
 * and validates it against a minimum threshold.
 * * Usage in CI:
 * php tools/check-coverage.php <phpunit_output.txt> <threshold> [--display-only]
 * * Arguments:
 * phpunit_output.txt - Path to the text file containing PHPUnit's --coverage-text output
 * threshold          - Minimum line coverage percentage required (0-100)
 * --display-only     - Only display coverage, don't check threshold (optional)
 * * Exit codes:
 * 0 - Coverage meets or exceeds threshold (success)
 * 1 - Coverage below threshold or error (failure)
 */

// ANSI color codes for terminal output
const COLOR_RED = "\033[31m";
const COLOR_GREEN = "\033[32m";
const COLOR_YELLOW = "\033[33m";
const COLOR_BLUE = "\033[34m";
const COLOR_RESET = "\033[0m";
const BOLD = "\033[1m";

/**
 * Parse command line arguments
 */
function parseArguments($argv) {
    if (count($argv) < 3) {
        echo COLOR_RED . "❌ Error: Missing required arguments" . COLOR_RESET . "\n\n";
        echo "Usage: php check-coverage.php <phpunit_output.txt> <threshold> [--display-only]\n";
        echo "\nArguments:\n";
        echo "  phpunit_output.txt Path to PHPUnit text report\n";
        echo "  threshold          Minimum coverage percentage (0-100)\n";
        echo "  --display-only     Only display coverage, don't check threshold\n";
        echo "\nExample:\n";
        echo "  php check-coverage.php coverage/phpunit_output.txt 80\n";
        exit(1);
    }

    $outputFile = $argv[1];
    $threshold = (float) $argv[2];
    $displayOnly = isset($argv[3]) && $argv[3] === '--display-only';

    return [$outputFile, $threshold, $displayOnly];
}

/**
 * Parse PHPUnit text output and extract Line coverage percentage
 */
function extractCoveragePercentage($outputFile) {
    if (!file_exists($outputFile)) {
        echo COLOR_RED . "❌ Error: PHPUnit output file not found: $outputFile" . COLOR_RESET . "\n";
        exit(1);
    }

    $content = file_get_contents($outputFile);
    if ($content === false) {
        echo COLOR_RED . "❌ Error: Failed to read file: $outputFile" . COLOR_RESET . "\n";
        exit(1);
    }

    // Use regex to find the global Line coverage percentage
    // Example: "Lines: 85.55%"
    if (preg_match('/Lines:\s*([0-9\.]+)\%/', $content, $matches)) {
        return (float) $matches[1];
    }
    
    // Fallback: search for Classes/Methods/Lines summary block
    if (preg_match('/(Lines:\s*([0-9\.]+)\%)/s', $content, $matches)) {
        // If the simple regex failed, try to capture the summary block,
        // though the first regex should be robust enough.
        return (float) $matches[2];
    }

    echo COLOR_RED . "❌ Error: Could not find Line Coverage percentage in the report." . COLOR_RESET . "\n";
    exit(1);
}

/**
 * Display coverage report summary and percentage
 */
function displayCoverageReport($primaryCoverage) {
    echo "\n";
    echo COLOR_BLUE . BOLD . "╔════════════════════════════════════════════════╗" . COLOR_RESET . "\n";
    echo COLOR_BLUE . BOLD . "║          GLOBAL COVERAGE SUMMARY               ║" . COLOR_RESET . "\n";
    echo COLOR_BLUE . BOLD . "╚════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
    echo "\n";
    
    $color = $primaryCoverage >= 80 ? COLOR_GREEN : ($primaryCoverage >= 60 ? COLOR_YELLOW : COLOR_RED);
    
    echo $color . BOLD . "  PRIMARY COVERAGE (Lines): " . number_format($primaryCoverage, 2) . "%" . COLOR_RESET . "\n";
    echo "\n";

    return $primaryCoverage;
}

/**
 * Check if coverage meets threshold and display result
 */
function checkThreshold($primaryCoverage, $threshold) {
    
    echo "  " . COLOR_BLUE . "Threshold: " . COLOR_RESET . number_format($threshold, 2) . "%\n";
    echo "  " . COLOR_BLUE . "Actual:    " . COLOR_RESET . number_format($primaryCoverage, 2) . "%\n";
    echo "\n";

    if ($primaryCoverage >= $threshold) {
        $diff = $primaryCoverage - $threshold;
        echo COLOR_GREEN . BOLD . "  ✓ PASSED" . COLOR_RESET . "\n";
        echo COLOR_GREEN . "  Coverage meets threshold (+" . number_format($diff, 2) . "%)" . COLOR_RESET . "\n";
        echo "\n";
        return true;
    } else {
        $diff = $threshold - $primaryCoverage;
        echo COLOR_RED . BOLD . "  ✗ FAILED" . COLOR_RESET . "\n";
        echo COLOR_RED . "  Coverage below threshold (-" . number_format($diff, 2) . "%)" . COLOR_RESET . "\n";
        echo "\n";
        echo COLOR_YELLOW . "  💡 Tips to improve coverage:" . COLOR_RESET . "\n";
        echo "     • Add unit tests for untested methods\n";
        echo "     • Review uncovered lines in HTML report\n";
        echo "     • Focus on critical business logic first\n";
        echo "\n";
        return false;
    }
}

/**
 * Display a progress bar for coverage
 */
function displayProgressBar($percentage, $width = 50) {
    $filled = (int) (($percentage / 100) * $width);
    $empty = $width - $filled;
    
    $color = $percentage >= 80 ? COLOR_GREEN : ($percentage >= 60 ? COLOR_YELLOW : COLOR_RED);
    
    echo "  [";
    echo $color . str_repeat("█", $filled) . COLOR_RESET;
    echo str_repeat("░", $empty);
    echo "] " . number_format($percentage, 1) . "%\n";
}

/**
 * Main execution
 */
function main($argv) {
    [$outputFile, $threshold, $displayOnly] = parseArguments($argv);
    
    // Extract coverage percentage
    $primaryCoverage = extractCoveragePercentage($outputFile);
    
    // Display coverage report
    displayCoverageReport($primaryCoverage);
    
    // Display progress bar
    displayProgressBar($primaryCoverage);
    echo "\n";
    
    // If display-only mode, output just the number and exit
    if ($displayOnly) {
        // Output only the numeric value for GitHub Actions
        echo number_format($primaryCoverage, 2);
        exit(0);
    }
    
    // Check threshold
    $passed = checkThreshold($primaryCoverage, $threshold);
    
    // Output the coverage percentage for GitHub Actions (last line)
    echo "\n" . number_format($primaryCoverage, 2) . "\n";
    
    // Exit with appropriate code
    exit($passed ? 0 : 1);
}

// Run the script
main($argv);