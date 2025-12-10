#!/usr/bin/env php
<?php
/**
 * Calculate Diff Coverage
 * 
 * Compares coverage between current branch and base branch to calculate
 * coverage percentage for only new/changed lines.
 * 
 * Usage:
 *   php tools/calculate-diff-coverage.php <coverage.xml> <base-branch> <threshold>
 * 
 * Arguments:
 *   coverage.xml - Path to current Clover XML coverage report
 *   base-branch  - Base branch to compare against (e.g., main, origin/main)
 *   threshold    - Minimum coverage percentage required (0-100)
 * 
 * Exit codes:
 *   0 - Diff coverage meets or exceeds threshold
 *   1 - Diff coverage below threshold or error
 */

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
    if (count($argv) < 4) {
        echo COLOR_RED . "❌ Error: Missing required arguments" . COLOR_RESET . "\n\n";
        echo "Usage: php calculate-diff-coverage.php <coverage.xml> <base-branch> <threshold>\n";
        echo "\nArguments:\n";
        echo "  coverage.xml  Path to Clover XML coverage report\n";
        echo "  base-branch   Base branch to compare (e.g., origin/main)\n";
        echo "  threshold     Minimum coverage percentage (0-100)\n";
        echo "\nExample:\n";
        echo "  php calculate-diff-coverage.php coverage/clover.xml origin/main 90\n";
        exit(1);
    }

    return [$argv[1], $argv[2], (float) $argv[3]];
}

/**
 * Get changed lines from git diff
 */
function getChangedLines($baseBranch) {
    echo COLOR_BLUE . "📊 Analyzing git diff..." . COLOR_RESET . "\n";
    
    // Get diff with line numbers
    $diffCommand = "git diff {$baseBranch}...HEAD --unified=0";
    exec($diffCommand, $diffOutput, $returnCode);
    
    if ($returnCode !== 0) {
        echo COLOR_RED . "❌ Error: Failed to get git diff" . COLOR_RESET . "\n";
        return [];
    }
    
    $changedLines = [];
    $currentFile = null;
    
    foreach ($diffOutput as $line) {
        // Match file path: +++ b/path/to/file.php
        if (preg_match('/^\+\+\+ b\/(.+)$/', $line, $matches)) {
            $currentFile = $matches[1];
            // Only track PHP files AND EXCLUDE FILES in 'tools/' directory
            if (!preg_match('/\.php$/', $currentFile) || strpos($currentFile, 'tools/') === 0) {
                $currentFile = null;
            }
            continue;
        }
        
        // Match line range: @@ -10,5 +12,7 @@
        if ($currentFile && preg_match('/^@@ -\d+(?:,\d+)? \+(\d+)(?:,(\d+))? @@/', $line, $matches)) {
            $startLine = (int) $matches[1];
            $lineCount = isset($matches[2]) ? (int) $matches[2] : 1;
            
            if (!isset($changedLines[$currentFile])) {
                $changedLines[$currentFile] = [];
            }
            
            // Add all changed lines in this range
            for ($i = 0; $i < $lineCount; $i++) {
                $changedLines[$currentFile][] = $startLine + $i;
            }
        }
    }
    
    return $changedLines;
}

/**
 * Parse coverage XML and get covered lines per file
 */
function getCoveredLines($coverageFile) {
    if (!file_exists($coverageFile)) {
        echo COLOR_RED . "❌ Error: Coverage file not found: $coverageFile" . COLOR_RESET . "\n";
        exit(1);
    }
    
    $xml = @simplexml_load_file($coverageFile);
    if ($xml === false) {
        echo COLOR_RED . "❌ Error: Failed to parse coverage XML" . COLOR_RESET . "\n";
        exit(1);
    }
    
    $coveredLines = [];
    
    // Iterate through all files in coverage report
    foreach ($xml->xpath('//file') as $file) {
        $filename = (string) $file['name'];
        
        // Normalize path (remove leading /)
        $filename = ltrim($filename, '/');
        
        // Also try to match relative to project root
        $relativeFilename = basename(dirname(dirname($filename))) . '/' . 
                           basename(dirname($filename)) . '/' . 
                           basename($filename);
        
        if (!isset($coveredLines[$filename])) {
            $coveredLines[$filename] = [];
        }
        
        // Get all lines with coverage information
        foreach ($file->line as $line) {
            $lineNum = (int) $line['num'];
            $count = (int) $line['count'];
            
            if ($count > 0) {
                $coveredLines[$filename][] = $lineNum;
            }
        }
    }
    
    return $coveredLines;
}

/**
 * Calculate diff coverage percentage
 */
function calculateDiffCoverage($changedLines, $coveredLines) {
    $totalChangedLines = 0;
    $coveredChangedLines = 0;
    $fileBreakdown = [];
    
    foreach ($changedLines as $file => $lines) {
        $fileCovered = 0;
        $fileTotal = count($lines);
        
        // Try to find coverage for this file (handle different path formats)
        $coverageKey = null;
        foreach (array_keys($coveredLines) as $coveredFile) {
            if (strpos($coveredFile, $file) !== false || strpos($file, basename($coveredFile)) !== false) {
                $coverageKey = $coveredFile;
                break;
            }
        }
        
        if ($coverageKey) {
            foreach ($lines as $lineNum) {
                if (in_array($lineNum, $coveredLines[$coverageKey])) {
                    $fileCovered++;
                    $coveredChangedLines++;
                }
                $totalChangedLines++;
            }
        } else {
            // No coverage data for this file
            $totalChangedLines += $fileTotal;
        }
        
        $fileBreakdown[$file] = [
            'total' => $fileTotal,
            'covered' => $fileCovered,
            'percentage' => $fileTotal > 0 ? ($fileCovered / $fileTotal) * 100 : 0
        ];
    }
    
    $diffCoverage = $totalChangedLines > 0 ? ($coveredChangedLines / $totalChangedLines) * 100 : 0;
    
    return [
        'totalLines' => $totalChangedLines,
        'coveredLines' => $coveredChangedLines,
        'percentage' => $diffCoverage,
        'files' => $fileBreakdown
    ];
}

/**
 * Display diff coverage report
 */
function displayDiffCoverage($result, $threshold) {
    echo "\n";
    echo COLOR_BLUE . BOLD . "╔════════════════════════════════════════════════╗" . COLOR_RESET . "\n";
    echo COLOR_BLUE . BOLD . "║       DIFF COVERAGE REPORT (NEW CODE)          ║" . COLOR_RESET . "\n";
    echo COLOR_BLUE . BOLD . "╚════════════════════════════════════════════════╝" . COLOR_RESET . "\n";
    echo "\n";
    
    $percentage = $result['percentage'];
    $color = $percentage >= 90 ? COLOR_GREEN : ($percentage >= 70 ? COLOR_YELLOW : COLOR_RED);
    
    echo "  Total changed lines:   " . $result['totalLines'] . "\n";
    echo "  Covered changed lines: " . $result['coveredLines'] . "\n";
    echo "  " . $color . BOLD . "Diff Coverage: " . number_format($percentage, 2) . "%" . COLOR_RESET . "\n";
    echo "\n";
    
    // File breakdown
    if (!empty($result['files'])) {
        echo "  " . COLOR_BLUE . "Coverage by file:" . COLOR_RESET . "\n";
        foreach ($result['files'] as $file => $data) {
            $fileColor = $data['percentage'] >= 90 ? COLOR_GREEN : COLOR_YELLOW;
            echo sprintf(
                "    %s%-50s %3d/%3d (%5.1f%%)%s\n",
                $fileColor,
                substr($file, -50),
                $data['covered'],
                $data['total'],
                $data['percentage'],
                COLOR_RESET
            );
        }
        echo "\n";
    }
    
    // Threshold check
    echo "  " . COLOR_BLUE . "Threshold: " . COLOR_RESET . number_format($threshold, 2) . "%\n";
    echo "  " . COLOR_BLUE . "Actual:    " . COLOR_RESET . number_format($percentage, 2) . "%\n";
    echo "\n";
    
    if ($percentage >= $threshold) {
        $diff = $percentage - $threshold;
        echo COLOR_GREEN . BOLD . "  ✓ PASSED" . COLOR_RESET . "\n";
        echo COLOR_GREEN . "  Diff coverage meets threshold (+" . number_format($diff, 2) . "%)" . COLOR_RESET . "\n";
        echo "\n";
        return true;
    } else {
        $diff = $threshold - $percentage;
        echo COLOR_RED . BOLD . "  ✗ FAILED" . COLOR_RESET . "\n";
        echo COLOR_RED . "  Diff coverage below threshold (-" . number_format($diff, 2) . "%)" . COLOR_RESET . "\n";
        echo "\n";
        echo COLOR_YELLOW . "  💡 Your new/changed code needs more tests:" . COLOR_RESET . "\n";
        echo "     • Add unit tests for new methods/functions\n";
        echo "     • Test edge cases and error handling\n";
        echo "     • Ensure all code paths are covered\n";
        echo "\n";
        return false;
    }
}

/**
 * Main execution
 */
function main($argv) {
    [$coverageFile, $baseBranch, $threshold] = parseArguments($argv);
    
    echo "\n";
    echo COLOR_BLUE . "Starting diff coverage calculation..." . COLOR_RESET . "\n";
    echo "  Coverage file: $coverageFile\n";
    echo "  Base branch:   $baseBranch\n";
    echo "  Threshold:     {$threshold}%\n";
    echo "\n";
    
    // Get changed lines from git diff
    $changedLines = getChangedLines($baseBranch);
    
    if (empty($changedLines)) {
        echo COLOR_YELLOW . "⚠️  No PHP files changed or no diff found" . COLOR_RESET . "\n";
        echo "N/A\n";
        exit(0);
    }
    
    echo COLOR_GREEN . "✓ Found changes in " . count($changedLines) . " PHP files" . COLOR_RESET . "\n\n";
    
    // Get covered lines from coverage report
    $coveredLines = getCoveredLines($coverageFile);
    echo COLOR_GREEN . "✓ Parsed coverage for " . count($coveredLines) . " files" . COLOR_RESET . "\n\n";
    
    // Calculate diff coverage
    $result = calculateDiffCoverage($changedLines, $coveredLines);
    
    // Display results
    $passed = displayDiffCoverage($result, $threshold);
    
    // Output JSON for GitHub Actions
    $jsonOutput = [
        'total_percent_covered' => $result['percentage'],
        'total_lines' => $result['totalLines'],
        'covered_lines' => $result['coveredLines'],
        'files' => $result['files']
    ];
    file_put_contents('coverage/diff-coverage.json', json_encode($jsonOutput, JSON_PRETTY_PRINT));
    
    // Output just the percentage for capture
    echo number_format($result['percentage'], 2) . "\n";
    
    exit($passed ? 0 : 1);
}

// Run the script
main($argv);