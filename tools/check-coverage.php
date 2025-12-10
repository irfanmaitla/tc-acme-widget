#!/usr/bin/env php
<?php
/**
 * Coverage Threshold Checker
 * 
 * Parses PHPUnit's Clover XML coverage report and validates against a minimum threshold.
 * 
 * Usage:
 *   php tools/check-coverage.php <clover.xml> <threshold> [--display-only]
 * 
 * Arguments:
 *   clover.xml  - Path to the Clover XML coverage report
 *   threshold   - Minimum coverage percentage required (0-100)
 *   --display-only - Only display coverage, don't check threshold (optional)
 * 
 * Exit codes:
 *   0 - Coverage meets or exceeds threshold (success)
 *   1 - Coverage below threshold or error (failure)
 * 
 * Examples:
 *   php tools/check-coverage.php coverage/clover.xml 80
 *   php tools/check-coverage.php coverage/clover.xml 0 --display-only
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
        echo "Usage: php check-coverage.php <clover.xml> <threshold> [--display-only]\n";
        echo "\nArguments:\n";
        echo "  clover.xml     Path to Clover XML coverage report\n";
        echo "  threshold      Minimum coverage percentage (0-100)\n";
        echo "  --display-only Only display coverage, don't check threshold\n";
        echo "\nExample:\n";
        echo "  php check-coverage.php coverage/clover.xml 80\n";
        exit(1);
    }

    $cloverFile = $argv[1];
    $threshold = (float) $argv[2];
    $displayOnly = isset($argv[3]) && $argv[3] === '--display-only';

    return [$cloverFile, $threshold, $displayOnly];
}

/**
 * Parse Clover XML and extract coverage metrics
 */
function parseCoverageReport($cloverFile) {
    if (!file_exists($cloverFile)) {
        echo COLOR_RED . "❌ Error: Coverage file not found: $cloverFile" . COLOR_RESET . "\n";
        exit(1);
    }

    $xml = @simplexml_load_file($cloverFile);
    if ($xml === false) {
        echo COLOR_RED . "❌ Error: Failed to parse XML file: $cloverFile" . COLOR_RESET . "\n";
        exit(1);
    }

    // Extract metrics from the project-level metrics
    $metrics = $xml->project->metrics;
    
    if (!isset($metrics)) {
        echo COLOR_RED . "❌ Error: No metrics found in coverage report" . COLOR_RESET . "\n";
        exit(1);
    }

    $coveredElements = (int) $metrics['coveredelements'];
    $totalElements = (int) $metrics['elements'];
    
    $coveredStatements = (int) $metrics['coveredstatements'];
    $totalStatements = (int) $metrics['statements'];
    
    $coveredMethods = (int) $metrics['coveredmethods'];
    $totalMethods = (int) $metrics['methods'];
    
    $coveredClasses = (int) $metrics['coveredclasses'];
    $totalClasses = (int) $metrics['classes'];

    // Calculate percentages
    $elementCoverage = $totalElements > 0 ? ($coveredElements / $totalElements) * 100 : 0;
    $statementCoverage = $totalStatements > 0 ? ($coveredStatements / $totalStatements) * 100 : 0;
    $methodCoverage = $totalMethods > 0 ? ($coveredMethods / $totalMethods) * 100 : 0;
    $classCoverage = $totalClasses > 0 ? ($coveredClasses / $totalClasses) * 100 : 0;

    return [
        'element' => [
            'covered' => $coveredElements,
            'total' => $totalElements,
            'percentage' => $elementCoverage
        ],
        'statement' => [
            'covered' => $coveredStatements,
            'total' => $totalStatements,
            'percentage' => $statementCoverage
        ],
        'method' => [
            'covered' => $coveredMethods,
            'total' => $totalMethods,
            'percentage' => $methodCoverage
        ],
        'class' => [
            'covered' => $coveredClasses,
            'total' => $totalClasses,
            'percentage' => $classCoverage
        ]
    ];
}

/**
 * Display coverage report in a formatted table
 */
function displayCoverageReport($coverage) {
    echo "\n";
    echo COLOR_BLUE . BOLD . "╔════════════════════════════════════════════════╗" . COLOR_RESET . "\n";
    echo COLOR_BLUE . BOLD . "║          CODE COVERAGE REPORT                  ║" . COLOR_RESET . "\n";
    echo COLOR_BLUE . BOLD . "╚════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
    echo "\n";

    $data = [
        ['Metric', 'Covered', 'Total', 'Coverage'],
        ['─────────────', '────────', '───────', '─────────'],
        ['Lines/Stmts', $coverage['statement']['covered'], $coverage['statement']['total'], number_format($coverage['statement']['percentage'], 2) . '%'],
        ['Methods', $coverage['method']['covered'], $coverage['method']['total'], number_format($coverage['method']['percentage'], 2) . '%'],
        ['Classes', $coverage['class']['covered'], $coverage['class']['total'], number_format($coverage['class']['percentage'], 2) . '%'],
    ];

    foreach ($data as $row) {
        printf(
            "  %-15s %10s %10s %12s\n",
            $row[0],
            $row[1],
            $row[2],
            $row[3]
        );
    }

    echo "\n";
    
    // Primary coverage (statement/line coverage)
    $primaryCoverage = $coverage['statement']['percentage'];
    $color = $primaryCoverage >= 80 ? COLOR_GREEN : ($primaryCoverage >= 60 ? COLOR_YELLOW : COLOR_RED);
    
    echo $color . BOLD . "  PRIMARY COVERAGE (Lines): " . number_format($primaryCoverage, 2) . "%" . COLOR_RESET . "\n";
    echo "\n";

    return $primaryCoverage;
}

/**
 * Check if coverage meets threshold and display result
 */
function checkThreshold($coverage, $threshold) {
    $primaryCoverage = $coverage['statement']['percentage'];
    
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
        echo "     • Test error handling and edge cases\n";
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
    [$cloverFile, $threshold, $displayOnly] = parseArguments($argv);
    
    // Parse coverage report
    $coverage = parseCoverageReport($cloverFile);
    
    // Display coverage report
    $primaryCoverage = displayCoverageReport($coverage);
    
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
    $passed = checkThreshold($coverage, $threshold);
    
    // Output the coverage percentage for GitHub Actions (last line)
    echo "\n" . number_format($primaryCoverage, 2) . "\n";
    
    // Exit with appropriate code
    exit($passed ? 0 : 1);
}

// Run the script
main($argv);