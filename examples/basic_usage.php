<?php

/**
 * Basic Usage Examples for SonarFixer
 */

require_once __DIR__ . '/../vendor/autoload.php';

use SonarFixer\CodeFixer;
use SonarFixer\Scanner\IssueScanner;
use SonarFixer\Rules\RuleRegistry;

// Example 1: Fix code with all available rules
echo "=== Example 1: Fix Code with All Rules ===\n\n";

$phpCode = <<<'PHP'
<?php
class Utils {
    public static function process($data) {
        if ($data == true) {
            return true;
        }
        if ($data == false) {
            return false;
        }
    }
    
    public function unused() {
        $notUsed = 42;
        return 1;
    }
}
PHP;

$fixer = new CodeFixer();
$result = $fixer->fixCode($phpCode, 'example.php');

echo "Original:\n" . $phpCode . "\n\n";
echo "Fixed:\n" . $result['fixed_code'] . "\n\n";
echo "Changes (" . $result['changes_count'] . "):\n";
foreach ($result['changes'] as $change) {
    echo "  • [{$change['rule']}] {$change['description']}\n";
}

// Example 2: Fix specific rules only
echo "\n=== Example 2: Fix Specific Rules ===\n\n";

$result = $fixer->fixCode($phpCode, 'example.php', ['S1125']);
echo "Fixed (S1125 only):\n" . $result['fixed_code'] . "\n";

// Example 3: Scan code without fixing
echo "\n=== Example 3: Scan Code Without Fixing ===\n\n";

$scanner = new IssueScanner();
$scanResult = $scanner->scanCode($phpCode, 'example.php');

echo "Issues Found: " . $scanResult['issue_count'] . "\n";
echo "By Severity: " . json_encode($scanResult['by_severity']) . "\n";
echo "By Rule: " . json_encode($scanResult['by_rule']) . "\n\n";

echo "Issues:\n";
foreach ($scanResult['issues'] as $issue) {
    echo "  • [{$issue['rule']}] Line {$issue['line']}: {$issue['description']}\n";
}

// Example 4: Custom rule registry
echo "\n=== Example 4: Using Rule Registry ===\n\n";

$registry = new RuleRegistry();
$allRules = $registry->getAllRules();

echo "Available Rules:\n";
foreach ($allRules as $ruleId => $config) {
    echo "  • [$ruleId] {$config['name']} ({$config['severity']})\n";
}

// Example 5: Fix with rule filter
echo "\n=== Example 5: Fix with Severity Filter ===\n\n";

$minorRules = $registry->getRulesBySeverity('minor');
$minorRuleIds = array_keys($minorRules);

echo "Minor severity rules: " . implode(', ', $minorRuleIds) . "\n";
$result = $fixer->fixCode($phpCode, 'example.php', $minorRuleIds);
echo "Fixed " . $result['changes_count'] . " issues with minor rules\n";
