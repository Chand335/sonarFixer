# SonarFixer API Reference

Complete API documentation for using SonarFixer programmatically.

## Table of Contents

- [CodeFixer Class](#codefixer-class)
- [IssueScanner Class](#issuescanner-class)
- [RuleRegistry Class](#ruleregistry-class)
- [SymbolTable Class](#symboltable-class)
- [Result Format](#result-format)
- [Exception Types](#exception-types)
- [Usage Examples](#usage-examples)

## CodeFixer Class

Main class for applying fixes to PHP code.

**Namespace**: `SonarFixer\CodeFixer`

### Methods

#### fixCode()

Fix PHP code provided as a string.

```php
public function fixCode(
    string $code,
    string $filename = 'unknown.php',
    array $rules = []
): array
```

**Parameters**:
- `$code` (string): PHP code to fix
- `$filename` (string): Original filename for reference
- `$rules` (array): Rule IDs to apply (empty = all rules)

**Returns**: Result array with fixes applied

**Example**:
```php
$fixer = new CodeFixer();
$result = $fixer->fixCode(
    '<?php if ($x == true) { }',
    'test.php',
    ['S1125']
);

if ($result['success']) {
    echo $result['fixed_code'];
}
```

#### fixFile()

Fix a single PHP file.

```php
public function fixFile(
    string $filePath,
    array $rules = []
): array
```

**Parameters**:
- `$filePath` (string): Path to PHP file
- `$rules` (array): Rule IDs to apply

**Returns**: Result array

**Throws**: `FileNotFoundException`, `PermissionException`

**Example**:
```php
$fixer = new CodeFixer();
$result = $fixer->fixFile('src/MyClass.php', ['S1125', 'S1481']);

echo "Fixed " . $result['changes_count'] . " issues";
```

#### fixDirectory()

Fix all PHP files in a directory (recursive).

```php
public function fixDirectory(
    string $dirPath,
    array $rules = [],
    bool $recursive = true
): array
```

**Parameters**:
- `$dirPath` (string): Directory path
- `$rules` (array): Rule IDs to apply
- `$recursive` (bool): Include subdirectories

**Returns**: Array of results indexed by filename

**Example**:
```php
$fixer = new CodeFixer();
$results = $fixer->fixDirectory('src', ['S1125']);

foreach ($results as $file => $result) {
    if ($result['success']) {
        echo "$file: {$result['changes_count']} changes\n";
    }
}
```

#### fixCodeDryRun()

Preview fixes without modifying files.

```php
public function fixCodeDryRun(
    string $code,
    string $filename = 'unknown.php',
    array $rules = []
): array
```

**Returns**: Result array (same as fixCode)

## IssueScanner Class

Scans code for issues without applying fixes.

**Namespace**: `SonarFixer\Scanner\IssueScanner`

### Methods

#### scanCode()

Scan PHP code string for issues.

```php
public function scanCode(
    string $code,
    string $filename = 'unknown.php',
    array $rules = []
): array
```

**Parameters**:
- `$code` (string): PHP code to scan
- `$filename` (string): Filename reference
- `$rules` (array): Rule IDs to check

**Returns**: Array with detected issues

**Example**:
```php
$scanner = new IssueScanner();
$result = $scanner->scanCode(
    '<?php if ($x == true) { }',
    'test.php'
);

echo "Found " . $result['issue_count'] . " issues";
foreach ($result['issues'] as $issue) {
    printf(
        "[%s] Line %d: %s\n",
        $issue['rule'],
        $issue['line'],
        $issue['description']
    );
}
```

#### scanFile()

Scan a PHP file for issues.

```php
public function scanFile(
    string $filePath,
    array $rules = []
): array
```

**Example**:
```php
$scanner = new IssueScanner();
$result = $scanner->scanFile('src/MyClass.php');

echo json_encode($result['issues'], JSON_PRETTY_PRINT);
```

#### scanDirectory()

Scan all PHP files in directory.

```php
public function scanDirectory(
    string $dirPath,
    array $rules = [],
    bool $recursive = true
): array
```

**Returns**: Issues grouped by file

## RuleRegistry Class

Manages available rules.

**Namespace**: `SonarFixer\Rules\RuleRegistry`

### Methods

#### getRule()

Get rule configuration by ID.

```php
public function getRule(string $ruleId): ?array
```

**Example**:
```php
$registry = new RuleRegistry();
$rule = $registry->getRule('S1125');

echo $rule['name'];        // "Boolean literals..."
echo $rule['severity'];    // "minor"
echo $rule['description']; // "Removes redundant..."
```

#### getAllRules()

Get all registered rules.

```php
public function getAllRules(): array
```

**Returns**: Array of all rules indexed by ID

**Example**:
```php
$registry = new RuleRegistry();

foreach ($registry->getAllRules() as $id => $rule) {
    printf("%s: %s [%s]\n", $id, $rule['name'], $rule['severity']);
}
```

#### register()

Register a new rule.

```php
public function register(string $ruleId, array $ruleData): void
```

**Parameters**:
- `$ruleId` (string): Rule identifier (e.g., 'S1125')
- `$ruleData` (array): Rule metadata

**Rule Data Structure**:
```php
[
    'name' => 'Rule Name',
    'severity' => 'minor',  // or 'major', 'critical'
    'visitor' => 'Full\\Namespace\\VisitorClass',
    'description' => 'What the rule does'
]
```

**Example**:
```php
$registry = new RuleRegistry();
$registry->register('S9999', [
    'name' => 'My Custom Rule',
    'severity' => 'major',
    'visitor' => 'MyApp\\Visitors\\CustomVisitor',
    'description' => 'Detects and fixes custom pattern'
]);
```

## SymbolTable Class

Tracks variable scope and usage.

**Namespace**: `SonarFixer\SymbolTable`

### Methods

#### enterScope()

Enter a new scope.

```php
public function enterScope(string $type, ?string $name = null): void
```

**Parameters**:
- `$type` (string): 'function', 'class', 'block', etc.
- `$name` (string): Optional scope name

**Example**:
```php
$table = new SymbolTable();
$table->enterScope('function', 'myFunction');
```

#### exitScope()

Exit current scope.

```php
public function exitScope(): void
```

#### declareVariable()

Declare a variable in current scope.

```php
public function declareVariable(string $name, Node $node): void
```

#### useVariable()

Record variable usage.

```php
public function useVariable(string $name): void
```

#### isUnused()

Check if variable is unused.

```php
public function isUnused(string $name): bool
```

**Example**:
```php
$table = new SymbolTable();
$table->enterScope('function', 'test');
$table->declareVariable('unused', $node);
$table->exitScope();

if ($table->isUnused('unused')) {
    echo "Variable 'unused' was never used";
}
```

## Result Format

### Fix Result

Returned by `fixCode()`, `fixFile()`, etc.

```php
[
    'success' => true,                    // Was fixing successful?
    'fixed_code' => '<?php ...',          // Fixed PHP code
    'changes_count' => 3,                 // Number of changes
    'changes' => [                        // Details of changes
        [
            'rule' => 'S1125',            // Rule that made change
            'line' => 10,                 // Line number
            'description' => 'Removed...' // What was fixed
        ],
        // ... more changes
    ],
    'file' => 'src/MyClass.php'           // Original file
]
```

### Scan Result

Returned by `scanCode()`, `scanFile()`, etc.

```php
[
    'success' => true,
    'file' => 'src/MyClass.php',
    'issue_count' => 5,
    'issues' => [
        [
            'rule' => 'S1481',
            'line' => 15,
            'column' => 9,
            'description' => 'Unused variable: $unused',
            'severity' => 'major'
        ],
        // ... more issues
    ]
]
```

### Directory Result

Returned by `fixDirectory()`, `scanDirectory()`

```php
[
    'src/File1.php' => [
        'success' => true,
        'changes_count' => 2,
        // ... individual result
    ],
    'src/File2.php' => [
        'success' => true,
        'changes_count' => 1,
        // ... individual result
    ]
]
```

## Exception Types

### FileNotFoundException

Thrown when file doesn't exist.

```php
try {
    $fixer->fixFile('/nonexistent/file.php');
} catch (FileNotFoundException $e) {
    echo "File not found: " . $e->getMessage();
}
```

### PermissionException

Thrown when no write permission.

```php
try {
    $fixer->fixFile('/readonly/file.php');
} catch (PermissionException $e) {
    echo "Permission denied: " . $e->getMessage();
}
```

### ParseException

Thrown when PHP code has syntax errors.

```php
try {
    $fixer->fixCode('<?php this is invalid');
} catch (ParseException $e) {
    echo "Parse error: " . $e->getMessage();
}
```

### RuleNotFoundException

Thrown when rule doesn't exist.

```php
try {
    $fixer->fixCode($code, 'test.php', ['S9999']);
} catch (RuleNotFoundException $e) {
    echo "Rule not found: " . $e->getMessage();
}
```

## Usage Examples

### Example 1: Simple Fix

```php
<?php
use SonarFixer\CodeFixer;

$code = <<<'PHP'
<?php
if ($condition == true) {
    echo "yes";
}
PHP;

$fixer = new CodeFixer();
$result = $fixer->fixCode($code, 'example.php', ['S1125']);

if ($result['success']) {
    file_put_contents('fixed.php', $result['fixed_code']);
    echo "Fixed " . $result['changes_count'] . " issues";
}
```

### Example 2: Scan and Report

```php
<?php
use SonarFixer\Scanner\IssueScanner;

$scanner = new IssueScanner();
$result = $scanner->scanDirectory('src');

foreach ($result as $file => $issues) {
    if ($issues['issue_count'] > 0) {
        echo "\n$file:\n";
        foreach ($issues['issues'] as $issue) {
            printf(
                "  Line %d: [%s] %s\n",
                $issue['line'],
                $issue['rule'],
                $issue['description']
            );
        }
    }
}
```

### Example 3: Multiple Rules

```php
<?php
use SonarFixer\CodeFixer;

$fixer = new CodeFixer();
$results = $fixer->fixDirectory('src', [
    'S1125', // Boolean literals
    'S1481', // Unused variables
    'S1763'  // Dead code
]);

$totalChanges = 0;
foreach ($results as $file => $result) {
    if ($result['success']) {
        $totalChanges += $result['changes_count'];
        echo "$file: {$result['changes_count']} changes\n";
    }
}

echo "\nTotal changes: $totalChanges\n";
```

### Example 4: Dry Run

```php
<?php
use SonarFixer\CodeFixer;

$code = file_get_contents('src/MyClass.php');
$fixer = new CodeFixer();

// Preview changes
$preview = $fixer->fixCodeDryRun($code, 'src/MyClass.php');

echo "Preview of changes:\n";
foreach ($preview['changes'] as $change) {
    printf(
        "Line %d [%s]: %s\n",
        $change['line'],
        $change['rule'],
        $change['description']
    );
}

// If satisfied, apply fixes
if ($preview['changes_count'] > 0) {
    $result = $fixer->fixCode($code, 'src/MyClass.php');
    file_put_contents('src/MyClass.php', $result['fixed_code']);
}
```

### Example 5: Custom Rule Integration

```php
<?php
use SonarFixer\CodeFixer;
use SonarFixer\Rules\RuleRegistry;

// Register custom rule
$registry = new RuleRegistry();
$registry->register('CUSTOM001', [
    'name' => 'My Custom Rule',
    'severity' => 'minor',
    'visitor' => 'MyApp\\Visitors\\CustomVisitor',
    'description' => 'Fixes custom pattern'
]);

// Use the custom rule
$fixer = new CodeFixer($registry);
$result = $fixer->fixFile('src/MyFile.php', ['CUSTOM001']);
```
