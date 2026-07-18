# SonarFixer

🔧 **Automatic SonarCube PHP Issue Fixer** - A PHP tool that automatically detects and fixes SonarCube code quality issues using AST transformation with nikic/php-parser.

## Features

✨ **Automatic Issue Detection & Fixing**
- Scans PHP code for SonarCube issues
- Automatically applies fixes without manual intervention
- Supports both file and directory processing

📋 **Multiple Rules Supported**
- **S1125**: Boolean literals should not be used directly
- **S1481**: Local variables should not be declared and then unused
- **S1763**: All code should be reachable (dead code removal)
- **S1118**: Utility classes should not have public constructors

🎯 **Smart AST Transformation**
- Uses nikic/php-parser for reliable PHP parsing
- Implements visitor pattern for clean rule implementation
- Maintains code structure and formatting

🔍 **Dual Mode Operation**
- **Fix Mode**: Automatically fixes detected issues
- **Scan Mode**: Reports issues without modifying files

## Installation

```bash
git clone https://github.com/Chand335/sonarFixer.git
cd sonarFixer
composer install
```

## Usage

### CLI Interface

#### Fix Issues in a File
```bash
php bin/sonar-fixer.php fix src/MyClass.php
```

#### Fix Specific Rules
```bash
php bin/sonar-fixer.php fix src --rules=S1125,S1481
```

#### Dry Run (Preview Changes)
```bash
php bin/sonar-fixer.php fix src --dry-run
```

#### Scan for Issues (No Modifications)
```bash
php bin/sonar-fixer.php scan src
```

#### List Available Rules
```bash
php bin/sonar-fixer.php list-rules
```

### PHP API

#### Fix PHP Code
```php
<?php
use SonarFixer\CodeFixer;

$fixer = new CodeFixer();
$result = $fixer->fixCode($phpCode, 'example.php', ['S1125', 'S1481']);

if ($result['success']) {
    echo "Fixed code:\n" . $result['fixed_code'];
    echo "Changes: " . $result['changes_count'];
}
```

#### Fix a File
```php
<?php
use SonarFixer\CodeFixer;

$fixer = new CodeFixer();
$result = $fixer->fixFile('path/to/file.php');

if ($result['success']) {
    echo "File fixed! Changes: " . $result['changes_count'];
}
```

#### Fix an Entire Directory
```php
<?php
use SonarFixer\CodeFixer;

$fixer = new CodeFixer();
$results = $fixer->fixDirectory('src', ['S1125']);

foreach ($results as $file => $result) {
    if ($result['success']) {
        echo "$file: {$result['changes_count']} changes\n";
    }
}
```

#### Scan for Issues
```php
<?php
use SonarFixer\Scanner\IssueScanner;

$scanner = new IssueScanner();
$result = $scanner->scanFile('src/MyClass.php');

echo "Issues found: " . $result['issue_count'];
foreach ($result['issues'] as $issue) {
    echo "[{$issue['rule']}] Line {$issue['line']}: {$issue['description']}\n";
}
```

## Project Structure

```
SonarFixer/
├── bin/
│   └── sonar-fixer.php           # CLI entry point
├── src/
│   ├── CodeFixer.php             # Main fixer class
│   ├── SymbolTable.php           # Scope tracking
│   ├── Rules/
│   │   └── RuleRegistry.php      # Rule management
│   ├── Scanner/
│   │   └── IssueScanner.php      # Issue detection (no fixes)
│   └── Visitors/
│       ├── BaseVisitor.php       # Base class for all visitors
│       ├── BooleanLiteralVisitor.php  # S1125 fixer
│       ├── UnusedVariableVisitor.php  # S1481 fixer
│       ├── DeadCodeVisitor.php   # S1763 fixer
│       └── UtilityClassVisitor.php    # S1118 fixer
├── tests/
│   └── CodeFixerTest.php         # Unit tests
├── composer.json
└── README.md
```

## Supported Rules

### S1125 - Boolean Literals Should Not Be Used Directly

Removes redundant boolean literal comparisons.

**Before:**
```php
if ($condition == true) {
    // ...
}

if ($isActive === false) {
    // ...
}
```

**After:**
```php
if ($condition) {
    // ...
}

if (!$isActive) {
    // ...
}
```

### S1481 - Local Variables Should Not Be Unused

Detects and reports unused local variables (flags for future removal).

**Detected:**
```php
function calculate($x) {
    $unused = 5;        // ⚠️ Declared but never used
    return $x * 2;
}
```

### S1763 - All Code Should Be Reachable

Removes dead code after return, break, or continue statements.

**Before:**
```php
function test() {
    return 42;
    echo "This is dead code";  // Dead code
}
```

**After:**
```php
function test() {
    return 42;
}
```

### S1118 - Utility Classes Should Not Have Public Constructors

Adds or makes private the constructor of utility classes (only static members).

**Before:**
```php
class Utils {
    public static function doSomething() {
        return "done";
    }
}
```

**After:**
```php
class Utils {
    private function __construct() {
    }

    public static function doSomething() {
        return "done";
    }
}
```

## Testing

Run the test suite:

```bash
composer test
```

## Architecture

### Visitor Pattern
SonarFixer uses the Visitor pattern to traverse and transform the AST:

1. **Parser**: Converts PHP code into an Abstract Syntax Tree (AST)
2. **Visitors**: Specific visitors for each rule traverse the AST
3. **Transformers**: Apply fixes to nodes based on rule logic
4. **Printer**: Converts the modified AST back to PHP code

### Symbol Table
Tracks variable scopes and usage for accurate analysis:
- Manages nested scopes (functions, classes, blocks)
- Tracks variable definitions and usage counts
- Identifies unused symbols

### Rule Registry
Centralized management of all SonarCube rules:
- Registers available rules and their configurations
- Maps rules to visitor implementations
- Manages rule severity levels

## Adding New Rules

### 1. Create a Visitor

```php
<?php
namespace SonarFixer\Visitors;

use PhpParser\Node;

class MyRuleVisitor extends BaseVisitor
{
    public function leaveNode(Node $node)
    {
        // Implement your fix logic
        $this->recordChange($node, 'S9999', 'Description');
        return null;
    }
}
```

### 2. Register the Rule

```php
$registry = new RuleRegistry();
$registry->register('S9999', [
    'name' => 'Rule Name',
    'severity' => 'minor',
    'visitor' => 'SonarFixer\Visitors\MyRuleVisitor',
    'description' => 'Rule description'
]);
```

### 3. Test Your Visitor

```php
public function testMyRule(): void
{
    $code = <<<'PHP'
<?php
// Your test code
PHP;

    $result = $this->fixer->fixCode($code, 'test.php', ['S9999']);
    $this->assertTrue($result['success']);
}
```

## Dependencies

- **PHP**: ^7.4
- **nikic/php-parser**: ^4.14
- **PHPUnit**: ^9.0 (dev)

## Performance

- Single file processing: < 100ms typical
- Directory scanning: ~50-100ms per file
- Memory efficient AST transformation
- Optimized visitor traversal

## Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

MIT License - see LICENSE file for details

## Support

For issues, questions, or suggestions, please create an issue on GitHub.

## Roadmap

- [ ] More SonarCube rules (S1066, S1125, S1128, etc.)
- [ ] Configuration file support (.sonarfixer.json)
- [ ] GitHub Actions integration
- [ ] Performance optimization for large codebases
- [ ] Custom rule registration API
- [ ] JSON output format for CI/CD integration
- [ ] Pre-commit hook integration

---

**Made with ❤️ by Chand335**
