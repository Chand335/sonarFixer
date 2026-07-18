# SonarFixer Rule Implementation Guide

This guide walks you through the complete process of implementing a new SonarCube rule in SonarFixer.

## Table of Contents

1. [Understanding the Rule](#understanding-the-rule)
2. [Analyzing the Pattern](#analyzing-the-pattern)
3. [Creating the Visitor](#creating-the-visitor)
4. [Registering the Rule](#registering-the-rule)
5. [Writing Tests](#writing-tests)
6. [Documentation](#documentation)
7. [Submission](#submission)

## Understanding the Rule

Before implementing, understand:

1. **What is the rule?** - Read SonarCube documentation
2. **Why does it matter?** - Code quality/security implications
3. **What code patterns does it target?** - Examples
4. **What's the fix?** - How should it be transformed?

### Example: S1125 - Boolean Literals

```
What: Boolean literals should not be used in conditions directly
Why: Redundant comparisons reduce readability
Pattern: $var == true, $var === false, etc.
Fix: Replace with $var or !$var
```

## Analyzing the Pattern

### Identify Node Types

Use `nikic/php-parser` to understand the AST structure:

```php
<?php
use PhpParser\ParserFactory;
use PhpParser\NodeDumper;

$code = <<<'PHP'
<?php
if ($condition == true) {
    echo "true";
}
PHP;

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$ast = $parser->parse($code);

$dumper = new NodeDumper();
echo $dumper->dump($ast);
```

Output reveals the structure:
```
Array
  0: Stmt_If(
    condition: Expr_BinaryOp_Equal(
      left: Expr_Variable(name: condition)
      right: Expr_ConstFetch(name: Name(true))
    )
    stmts: Array(...)
  )
```

### Create Test Files

**File: `tests/fixtures/before/s1125.php`**
```php
<?php
// Test case 1: Simple boolean comparison
if ($condition == true) {
    echo "true";
}

// Test case 2: False comparison
if ($isActive === false) {
    echo "not active";
}

// Test case 3: Complex expression
if (($a && $b) == true) {
    echo "both true";
}
```

**File: `tests/fixtures/after/s1125.php`**
```php
<?php
// Test case 1: Simple boolean comparison
if ($condition) {
    echo "true";
}

// Test case 2: False comparison
if (!$isActive) {
    echo "not active";
}

// Test case 3: Complex expression
if ($a && $b) {
    echo "both true";
}
```

## Creating the Visitor

### Step 1: Extend BaseVisitor

**File: `src/Visitors/BooleanLiteralVisitor.php`**

```php
<?php

namespace SonarFixer\Visitors;

use PhpParser\Node;

class BooleanLiteralVisitor extends BaseVisitor
{
    /**
     * Called when leaving (exiting) a node during AST traversal
     * This is where we detect and fix issues
     */
    public function leaveNode(Node $node)
    {
        // Check if this node matches our pattern
        if ($this->isBooleanComparison($node)) {
            // Transform the node
            $fixed = $this->fixBooleanComparison($node);
            
            // Record that we made a change
            $this->recordChange(
                $node,
                'S1125',
                'Removed redundant boolean comparison'
            );
            
            // Return the fixed node
            return $fixed;
        }
        
        // Return null if no changes made
        return null;
    }
    
    /**
     * Detect if a node is a redundant boolean comparison
     */
    private function isBooleanComparison(Node $node): bool
    {
        // Check if it's a comparison operation
        if (!$node instanceof Node\Expr\BinaryOp\Equal &&
            !$node instanceof Node\Expr\BinaryOp\Identical) {
            return false;
        }
        
        // Check if right side is boolean literal
        if ($node->right instanceof Node\Expr\ConstFetch) {
            $name = $node->right->name->toLowerString();
            if ($name === 'true' || $name === 'false') {
                return true;
            }
        }
        
        // Check if left side is boolean literal
        if ($node->left instanceof Node\Expr\ConstFetch) {
            $name = $node->left->name->toLowerString();
            if ($name === 'true' || $name === 'false') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Transform the boolean comparison
     */
    private function fixBooleanComparison(Node $node): Node
    {
        $isComparisonWithTrue = $this->isComparisonWithValue($node, 'true');
        
        if ($isComparisonWithTrue) {
            // For "== true" or "=== true", just use the variable
            return $this->getLeftSide($node);
        } else {
            // For "== false" or "=== false", negate the variable
            return new Node\Expr\BooleanNot($this->getLeftSide($node));
        }
    }
    
    private function isComparisonWithValue(Node $node, string $value): bool
    {
        if ($node->right instanceof Node\Expr\ConstFetch) {
            return $node->right->name->toLowerString() === $value;
        }
        return $node->left instanceof Node\Expr\ConstFetch &&
               $node->left->name->toLowerString() === $value;
    }
    
    private function getLeftSide(Node $node): Node
    {
        // Return left side if right is boolean, otherwise return right
        if ($node->right instanceof Node\Expr\ConstFetch) {
            return $node->left;
        }
        return $node->right;
    }
}
```

### Step 2: Key Concepts

**Node Types**: Different syntax elements
- `Node\Expr\Variable` - `$var`
- `Node\Expr\BinaryOp\Equal` - `==`
- `Node\Expr\ConstFetch` - Constants like `true`, `false`
- `Node\Expr\BooleanNot` - `!`

**Visitor Methods**:
- `enterNode(Node $node)`: Called when entering a node
- `leaveNode(Node $node)`: Called when leaving a node (usually better for transformations)
- Return `null`: No change
- Return a `Node`: Replace with this node
- Return `NodeTraverser::REMOVE_NODE`: Remove the node
- Return `NodeTraverser::SKIP_CHILDREN`: Don't visit children

**Recording Changes**:
```php
$this->recordChange($node, 'S1234', 'Description of fix');
```

## Registering the Rule

### Update RuleRegistry

**File: `src/Rules/RuleRegistry.php`**

```php
<?php

namespace SonarFixer\Rules;

class RuleRegistry
{
    private array $rules = [];
    
    public function __construct()
    {
        $this->registerDefaultRules();
    }
    
    private function registerDefaultRules(): void
    {
        $this->register('S1125', [
            'name' => 'Boolean literals should not be used directly',
            'severity' => 'minor',
            'visitor' => 'SonarFixer\\Visitors\\BooleanLiteralVisitor',
            'description' => 'Removes redundant boolean literal comparisons'
        ]);
        
        $this->register('S1481', [
            'name' => 'Local variables should not be declared and then unused',
            'severity' => 'major',
            'visitor' => 'SonarFixer\\Visitors\\UnusedVariableVisitor',
            'description' => 'Detects and reports unused local variables'
        ]);
        
        // Add more rules here...
    }
    
    public function register(string $ruleId, array $ruleData): void
    {
        $this->rules[$ruleId] = $ruleData;
    }
    
    public function getRule(string $ruleId): ?array
    {
        return $this->rules[$ruleId] ?? null;
    }
    
    public function getAllRules(): array
    {
        return $this->rules;
    }
}
```

## Writing Tests

### Test File Structure

**File: `tests/Visitors/BooleanLiteralVisitorTest.php`**

```php
<?php

namespace SonarFixer\Tests\Visitors;

use PHPUnit\Framework\TestCase;
use SonarFixer\CodeFixer;

class BooleanLiteralVisitorTest extends TestCase
{
    private CodeFixer $fixer;
    
    protected function setUp(): void
    {
        $this->fixer = new CodeFixer();
    }
    
    /**
     * Test: Detects == true comparison
     */
    public function testDetectsEqualTrueComparison(): void
    {
        $code = <<<'PHP'
<?php
if ($condition == true) {
    echo "yes";
}
PHP;
        
        $result = $this->fixer->fixCode($code, 'test.php', ['S1125']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['changes_count']);
    }
    
    /**
     * Test: Fixes == true to just variable
     */
    public function testFixesEqualTrueComparison(): void
    {
        $code = <<<'PHP'
<?php
if ($condition == true) {
    echo "yes";
}
PHP;
        
        $expected = <<<'PHP'
<?php
if ($condition) {
    echo "yes";
}
PHP;
        
        $result = $this->fixer->fixCode($code, 'test.php', ['S1125']);
        
        $this->assertEquals($expected, $result['fixed_code']);
    }
    
    /**
     * Test: Fixes === false to negated variable
     */
    public function testFixesIdenticalFalseComparison(): void
    {
        $code = <<<'PHP'
<?php
if ($isActive === false) {
    echo "inactive";
}
PHP;
        
        $expected = <<<'PHP'
<?php
if (!$isActive) {
    echo "inactive";
}
PHP;
        
        $result = $this->fixer->fixCode($code, 'test.php', ['S1125']);
        
        $this->assertEquals($expected, $result['fixed_code']);
    }
    
    /**
     * Test: Handles complex expressions
     */
    public function testHandlesComplexExpressions(): void
    {
        $code = <<<'PHP'
<?php
if (($a && $b) == true) {
    echo "both";
}
PHP;
        
        $result = $this->fixer->fixCode($code, 'test.php', ['S1125']);
        
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['changes_count']);
    }
    
    /**
     * Test: Ignores normal comparisons
     */
    public function testIgnoresNormalComparisons(): void
    {
        $code = <<<'PHP'
<?php
if ($a == $b) {
    echo "equal";
}
PHP;
        
        $result = $this->fixer->fixCode($code, 'test.php', ['S1125']);
        
        $this->assertEquals(0, $result['changes_count']);
    }
    
    /**
     * Test: Handles multiple occurrences
     */
    public function testHandlesMultipleOccurrences(): void
    {
        $code = <<<'PHP'
<?php
if ($a == true) { }
if ($b === false) { }
if ($c == true) { }
PHP;
        
        $result = $this->fixer->fixCode($code, 'test.php', ['S1125']);
        
        $this->assertEquals(3, $result['changes_count']);
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test
composer test tests/Visitors/BooleanLiteralVisitorTest.php

# Run with coverage
phpunit --coverage-html coverage/ tests/
```

## Documentation

### Rule Documentation

**File: `docs/rules/S1125.md`**

```markdown
# S1125 - Boolean Literals Should Not Be Used Directly

## Overview
This rule detects redundant boolean literal comparisons in conditional statements.

## Problem
Directly comparing variables to `true` or `false` is verbose and reduces code readability. PHP idioms prefer direct boolean evaluation.

## Examples

### Before
```php
<?php
if ($condition == true) {
    doSomething();
}

if ($isValid === false) {
    handleError();
}

while ($keepGoing == true) {
    process();
}
```

### After
```php
<?php
if ($condition) {
    doSomething();
}

if (!$isValid) {
    handleError();
}

while ($keepGoing) {
    process();
}
```

## Implementation Details

### Detected Patterns
- `$var == true` → `$var`
- `$var === true` → `$var`
- `true == $var` → `$var`
- `true === $var` → `$var`
- `$var == false` → `!$var`
- `$var === false` → `!$var`
- `false == $var` → `!$var`
- `false === $var` → `!$var`

### Node Types
- `Node\Expr\BinaryOp\Equal`
- `Node\Expr\BinaryOp\Identical`
- `Node\Expr\ConstFetch` (for boolean values)

### Edge Cases Handled
- Complex expressions: `($a && $b) == true`
- Nested conditions
- Function return values: `strlen($str) == true`

### Edge Cases NOT Fixed
- Type juggling comparisons (intentional in some cases)
- Return value checks (considered intentional)

## SonarCube Reference
- Rule ID: S1125
- Severity: MINOR
- Type: Code Smell
- Category: Best Practices
- Link: https://rules.sonarsource.com/php/RSPEC-1125

## Composer Command
```bash
php bin/sonar-fixer.php fix src --rules=S1125
```
```

## Submission

### Checklist

Before submitting, verify:

- [ ] Visitor class created and working
- [ ] Rule registered in RuleRegistry
- [ ] Comprehensive tests written (>80% coverage)
- [ ] All tests passing
- [ ] Rule documentation created
- [ ] Examples in documentation clear
- [ ] Code follows PSR-12 standards
- [ ] Commit message is descriptive
- [ ] No breaking changes

### Pull Request

Create a PR with:

1. **Visitor implementation**
2. **Rule registration**
3. **Tests with fixtures**
4. **Documentation**
5. **Clear PR description**

### PR Description Template

```markdown
## Description
Implements SonarCube rule S1XXX: [Rule Name]

## Changes
- Added NewRuleVisitor.php
- Registered rule in RuleRegistry
- Added comprehensive tests
- Added rule documentation

## Testing
- Tests: 8 test cases added
- Coverage: 95%
- All tests passing

## Examples
```php
// Before
if ($var == true) { }

// After
if ($var) { }
```
```

## Common Issues and Solutions

### Issue: Visitor not detecting pattern
**Solution**: Use `NodeDumper` to examine AST structure, ensure node type checks are correct

### Issue: Tests failing
**Solution**: Verify expected output PHP syntax is valid, check for extra/missing whitespace

### Issue: Wrong nodes being modified
**Solution**: Add more specific node type checks, consider using `enterNode()` for filtering

### Issue: Code generation has wrong formatting
**Solution**: Ensure nodes preserve original formatting attributes, or use PrettyPrinter correctly

## Resources

- [nikic/php-parser Documentation](https://github.com/nikic/PHP-Parser/blob/master/doc/1_Introduction.md)
- [SonarCube Rules](https://rules.sonarsource.com/php/)
- [ARCHITECTURE.md](../ARCHITECTURE.md)
- [Existing Rules](../src/Visitors/)
