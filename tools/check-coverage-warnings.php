#!/usr/bin/env php
<?php
/**
 * Check Coverage Warnings
 * 
 * Analyzes coverage and code changes to generate warnings for:
 * 1. Critical files under 70% coverage
 * 2. Public methods added without tests
 * 3. Large PRs (500+ lines) with minimal test additions
 * 
 * Usage:
 *   php tools/check-coverage-warnings.php <coverage.xml> <base-branch>
 */

const COLOR_YELLOW = "\033[33m";
const COLOR_RESET = "\033[0m";
const BOLD = "\033[1m";

// Define critical paths
const CRITICAL_PATHS = [
    'src/Auth',
    'src/Payment',
    'src/Security',
    'src/Api',
    'src/Database',
    'Controller',
    'Service',
    'Repository'
];

/**
 * Parse Clover XML and get file-level coverage
 */
function getFileCoverage($coverageFile) {
    if (!file_exists($coverageFile)) {
        return [];
    }
    
    $xml = @simplexml_load_file($coverageFile);
    if ($xml === false) {
        return [];
    }
    
    $fileCoverage = [];
    
    foreach ($xml->xpath('//file') as $file) {
        $filename = (string) $file['name'];
        $metrics = $file->metrics;
        
        $statements = (int) $metrics['statements'];
        $coveredStatements = (int) $metrics['coveredstatements'];
        $methods = (int) $metrics['methods'];
        $coveredMethods = (int) $metrics['coveredmethods'];
        
        $coverage = $statements > 0 ? ($coveredStatements / $statements) * 100 : 0;
        
        $fileCoverage[$filename] = [
            'coverage' => $coverage,
            'statements' => $statements,
            'covered_statements' => $coveredStatements,
            'methods' => $methods,
            'covered_methods' => $coveredMethods
        ];
    }
    
    return $fileCoverage;
}

/**
 * Check if file is in critical path
 */
function isCriticalFile($filename) {
    foreach (CRITICAL_PATHS as $path) {
        if (strpos($filename, $path) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Get changed files from git diff
 */
function getChangedFiles($baseBranch) {
    $command = "git diff --name-only origin/" . escapeshellarg($baseBranch) . "...HEAD";
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0) {
        return [];
    }
    
    return array_filter($output, function($file) {
        return preg_match('/\.php$/', $file);
    });
}

/**
 * Get PR size (lines added + removed)
 */
function getPRSize($baseBranch) {
    $command = "git diff --shortstat origin/" . escapeshellarg($baseBranch) . "...HEAD";
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0 || empty($output)) {
        return 0;
    }
    
    // Parse: "5 files changed, 234 insertions(+), 12 deletions(-)"
    if (preg_match('/(\d+) insertion/', $output[0], $matches)) {
        $insertions = (int) $matches[1];
    } else {
        $insertions = 0;
    }
    
    if (preg_match('/(\d+) deletion/', $output[0], $matches)) {
        $deletions = (int) $matches[1];
    } else {
        $deletions = 0;
    }
    
    return $insertions + $deletions;
}

/**
 * Count test files changed
 */
function getTestFilesChanged($baseBranch) {
    $command = "git diff --name-only origin/" . escapeshellarg($baseBranch) . "...HEAD | grep -i test | wc -l";
    exec($command, $output, $returnCode);
    
    return $returnCode === 0 && !empty($output) ? (int) $output[0] : 0;
}

/**
 * Extract public methods from a PHP file
 */
function getPublicMethods($filename) {
    if (!file_exists($filename)) {
        return [];
    }
    
    $content = file_get_contents($filename);
    $methods = [];
    
    // Match: public function methodName(
    if (preg_match_all('/public\s+function\s+(\w+)\s*\(/i', $content, $matches)) {
        $methods = $matches[1];
    }
    
    return array_filter($methods, function($method) {
        // Exclude magic methods and getters/setters
        return !preg_match('/^(__construct|__destruct|get\w+|set\w+)$/i', $method);
    });
}

/**
 * Find public methods added in changed files
 */
function getNewPublicMethods($baseBranch, $changedFiles) {
    $newMethods = [];
    
    foreach ($changedFiles as $file) {
        if (!file_exists($file)) {
            continue;
        }
        
        // Get diff for this file
        $command = "git diff origin/" . escapeshellarg($baseBranch) . "...HEAD -- " . escapeshellarg($file);
        exec($command, $diffOutput);
        
        $diffContent = implode("\n", $diffOutput);
        
        // Find new public functions (lines starting with +)
        if (preg_match_all('/^\+\s*public\s+function\s+(\w+)\s*\(/im', $diffContent, $matches)) {
            foreach ($matches[1] as $method) {
                // Exclude magic methods and basic getters/setters
                if (!preg_match('/^(__construct|__destruct|get\w+|set\w+)$/i', $method)) {
                    $newMethods[] = [
                        'file' => $file,
                        'method' => $method
                    ];
                }
            }
        }
    }
    
    return $newMethods;
}

/**
 * Check if method is covered in tests
 */
function isMethodCovered($file, $method, $fileCoverage) {
    // Simple heuristic: if file has good coverage, assume method is covered
    foreach ($fileCoverage as $coveredFile => $data) {
        if (strpos($coveredFile, basename($file)) !== false) {
            return $data['coverage'] > 80;
        }
    }
    return false;
}

/**
 * Generate warnings
 */
function generateWarnings($coverageFile, $baseBranch) {
    $warnings = [
        'critical_files_low_coverage' => [],
        'untested_methods' => [],
        'large_pr_warning' => false
    ];
    
    // Get coverage data
    $fileCoverage = getFileCoverage($coverageFile);
    
    // WARNING 1: Critical files under 70% coverage
    foreach ($fileCoverage as $file => $data) {
        if (isCriticalFile($file) && $data['coverage'] < 70) {
            $warnings['critical_files_low_coverage'][] = [
                'file' => $file,
                'coverage' => $data['coverage']
            ];
        }
    }
    
    // Get changed files
    $changedFiles = getChangedFiles($baseBranch);
    
    // WARNING 2: Public methods added without tests
    $newMethods = getNewPublicMethods($baseBranch, $changedFiles);
    foreach ($newMethods as $methodInfo) {
        if (!isMethodCovered($methodInfo['file'], $methodInfo['method'], $fileCoverage)) {
            $warnings['untested_methods'][] = $methodInfo;
        }
    }
    
    // WARNING 3: Large PR with minimal test additions
    $prSize = getPRSize($baseBranch);
    $testFilesChanged = getTestFilesChanged($baseBranch);
    
    if ($prSize > 500 && $testFilesChanged < 2) {
        $warnings['large_pr_warning'] = [
            'lines_changed' => $prSize,
            'test_files' => $testFilesChanged
        ];
    }
    
    return $warnings;
}

/**
 * Display warnings
 */
function displayWarnings($warnings) {
    $hasWarnings = false;
    
    echo "\n";
    echo COLOR_YELLOW . BOLD . "⚠️  COVERAGE WARNINGS" . COLOR_RESET . "\n";
    echo COLOR_YELLOW . "═══════════════════════════════════════════════" . COLOR_RESET . "\n\n";
    
    // Critical files warning
    if (!empty($warnings['critical_files_low_coverage'])) {
        $hasWarnings = true;
        echo COLOR_YELLOW . "⚠️  Critical files with coverage < 70%:" . COLOR_RESET . "\n";
        foreach ($warnings['critical_files_low_coverage'] as $file) {
            $shortPath = substr($file['file'], -60);
            echo sprintf("   • %s (%.1f%%)\n", $shortPath, $file['coverage']);
        }
        echo "\n";
    }
    
    // Untested methods warning
    if (!empty($warnings['untested_methods'])) {
        $hasWarnings = true;
        echo COLOR_YELLOW . "⚠️  Public methods added without apparent test coverage:" . COLOR_RESET . "\n";
        foreach ($warnings['untested_methods'] as $method) {
            echo sprintf("   • %s::%s()\n", basename($method['file']), $method['method']);
        }
        echo "\n";
    }
    
    // Large PR warning
    if ($warnings['large_pr_warning']) {
        $hasWarnings = true;
        $data = $warnings['large_pr_warning'];
        echo COLOR_YELLOW . "⚠️  Large PR with minimal test additions:" . COLOR_RESET . "\n";
        echo sprintf("   • Lines changed: %d\n", $data['lines_changed']);
        echo sprintf("   • Test files modified: %d\n", $data['test_files']);
        echo "   Consider adding more unit tests for this large change.\n";
        echo "\n";
    }
    
    if (!$hasWarnings) {
        echo "   ✓ No warnings detected\n\n";
    }
    
    return $warnings;
}

/**
 * Main execution
 */
function main($argv) {
    if (count($argv) < 3) {
        echo "Usage: php check-coverage-warnings.php <coverage.xml> <base-branch>\n";
        exit(1);
    }
    
    $coverageFile = $argv[1];
    $baseBranch = $argv[2];
    
    $warnings = generateWarnings($coverageFile, $baseBranch);
    $displayedWarnings = displayWarnings($warnings);
    
    // Output JSON for GitHub Actions
    file_put_contents('/tmp/coverage-warnings.json', json_encode($displayedWarnings, JSON_PRETTY_PRINT));
    
    exit(0);
}

main($argv);