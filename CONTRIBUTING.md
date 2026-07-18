# Contributing to SonarFixer

Thank you for your interest in contributing to SonarFixer! This document provides guidelines and instructions for contributing.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [Making Changes](#making-changes)
- [Adding New Rules](#adding-new-rules)
- [Testing](#testing)
- [Submitting Changes](#submitting-changes)
- [Coding Standards](#coding-standards)
- [Commit Messages](#commit-messages)

## Code of Conduct

Please be respectful and inclusive. We welcome contributions from everyone regardless of experience level.

## Getting Started

### Prerequisites

- PHP 7.4 or higher
- Composer
- Git

### Fork and Clone

```bash
# Fork the repository on GitHub

# Clone your fork
git clone https://github.com/YOUR_USERNAME/sonarFixer.git
cd sonarFixer

# Add upstream remote
git remote add upstream https://github.com/Chand335/sonarFixer.git
```

## Development Setup

### 1. Install Dependencies

```bash
composer install
```

### 2. Verify Installation

```bash
# Run tests
composer test

# Run code style check
composer run-script lint
```

### 3. Create Feature Branch

```bash
# Keep main in sync
git fetch upstream
git checkout main
git merge upstream/main

# Create feature branch
git checkout -b feature/your-feature-name
```

## Making Changes

### Repository Structure

```
sonarFixer/
├── bin/                          # CLI scripts
│   └── sonar-fixer.php          # Main entry point
├── src/                          # Main source code
│   ├── CodeFixer.php            # Primary orchestrator
│   ├── SymbolTable.php          # Variable scope tracking
│   ├── Rules/
│   │   └── RuleRegistry.php     # Rule management
│   ├── Scanner/
│   │   └── IssueScanner.php     # Detection without fixes
│   └── Visitors/
│       ├── BaseVisitor.php      # Abstract visitor base
│       ├── BooleanLiteralVisitor.php
│       ├── UnusedVariableVisitor.php
│       ├── DeadCodeVisitor.php
│       └── UtilityClassVisitor.php
├── tests/                        # Test suite
│   ├── fixtures/                # Test data
│   ├── CodeFixerTest.php
│   └── Visitors/
├── docs/                         # Documentation
│   ├── rules/                   # Rule documentation
│   ├── implementation-guide.md  # How to add rules
│   └── api.md                   # API reference
├── composer.json
├── phpunit.xml
├── ARCHITECTURE.md
├── CHANGELOG.md
└── README.md
```

### Code Style

Follow PSR-12 coding standards:

```bash
# Check code style
./vendor/bin/phpcs --standard=PSR12 src/ tests/

# Auto-fix style issues
./vendor/bin/phpcbf --standard=PSR12 src/ tests/
```

## Adding New Rules

### Step 1: Understand the Rule

- Read the SonarCube documentation for the rule
- Identify what code patterns it targets
- Document before/after examples

### Step 2: Create the Visitor Class

**File**: `src/Visitors/NewRuleVisitor.php`

```php
<?php

namespace SonarFixer\Visitors;

use PhpParser\Node;

class MyRuleVisitor extends BaseVisitor
{
    /**
     * Called when leaving a node during AST traversal
     */
    public function leaveNode(Node $node)
    {
        // Your rule logic here
        if ($node instanceof Node\Stmt\If_) {
            // Analyze and transform
            if ($this->shouldFix($node)) {
                $this->recordChange($node, 'SXXXX', 'Description');
                return $this->fixNode($node);
            }
        }
        return null;
    }

    private function shouldFix(Node $node): bool
    {
        // Check if node matches the rule pattern
        return true;
    }

    private function fixNode(Node $node): Node
    {
        // Transform the node
        return $node;
    }
}
```

### Step 3: Register the Rule

Update `src/Rules/RuleRegistry.php`:

```php
public function registerDefaultRules(): void
{
    $this->register('SXXXX', [
        'name' => 'Rule Name',
        'severity' => 'minor', // or 'major', 'critical'
        'visitor' => 'SonarFixer\\Visitors\\MyRuleVisitor',
        'description' => 'Description of what the rule does'
    ]);
}
```

### Step 4: Write Tests

**File**: `tests/Visitors/MyRuleVisitorTest.php`

```php
<?php

namespace SonarFixer\Tests\Visitors;

use PHPUnit\Framework\TestCase;
use SonarFixer\CodeFixer;

class MyRuleVisitorTest extends TestCase
{
    private CodeFixer $fixer;

    protected function setUp(): void
    {
        $this->fixer = new CodeFixer();
    }

    public function testDetectsIssue(): void
    {
        $code = <<<'PHP'
<?php
// Code that triggers the rule
PHP;
        $result = $this->fixer->fixCode($code, 'test.php', ['SXXXX']);
        $this->assertTrue($result['success']);
        $this->assertGreaterThan(0, $result['changes_count']);
    }

    public function testFixesIssue(): void
    {
        $before = <<<'PHP'
<?php
// Before code
PHP;
        $expected = <<<'PHP'
<?php
// Expected after code
PHP;
        $result = $this->fixer->fixCode($before, 'test.php', ['SXXXX']);
        $this->assertEquals($expected, $result['fixed_code']);
    }
}
```

### Step 5: Add Documentation

**File**: `docs/rules/SXXXX.md`

```markdown
# SXXXX - Rule Name

## Description
Clear explanation of the rule

## Problem
Why this is an issue

## Before
```php
// Code that violates the rule
```

## After
```php
// Fixed code
```

## Implementation Details
- Node types involved: ...
- Edge cases: ...
- Limitations: ...
```

## Testing

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
phpunit tests/Visitors/MyRuleVisitorTest.php

# Run with coverage
phpunit --coverage-html coverage/
```

### Test Guidelines

1. **Write tests before code** (TDD preferred)
2. **Test both positive and negative cases**
3. **Test edge cases** (empty files, syntax edge cases)
4. **Maintain >80% code coverage**
5. **Use descriptive test names**

### Example Test Structure

```php
public function testRuleFixesSimpleCase(): void
{
    // Simple, clear case
}

public function testRuleHandlesComplexScenario(): void
{
    // Edge case or complex scenario
}

public function testRuleIgnoresAlreadyFixedCode(): void
{
    // Verify idempotency
}
```

## Submitting Changes

### 1. Update Your Branch

```bash
git fetch upstream
git rebase upstream/main
```

### 2. Push to Your Fork

```bash
git push origin feature/your-feature-name
```

### 3. Create a Pull Request

- Go to https://github.com/Chand335/sonarFixer
- Click "New Pull Request"
- Select your branch
- Fill in the PR description

### PR Description Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] New rule implementation
- [ ] Bug fix
- [ ] Documentation
- [ ] Performance improvement

## Related Issues
Closes #123

## Testing
Describe testing performed

## Checklist
- [ ] Code follows style guidelines
- [ ] Tests pass (`composer test`)
- [ ] New tests added
- [ ] Documentation updated
- [ ] No breaking changes
```

### 4. Respond to Feedback

- Address review comments
- Push additional commits
- Rebase if requested

## Coding Standards

### PHP Style

- **PSR-12**: Follow PSR-12 coding standards
- **Naming**: Use clear, descriptive names
- **Functions**: Keep methods small and focused
- **Comments**: Add meaningful comments for complex logic

### Example

```php
<?php

namespace SonarFixer\Visitors;

use PhpParser\Node;

class ExampleVisitor extends BaseVisitor
{
    /**
     * Checks if a boolean comparison is redundant
     *
     * @param Node $node The AST node to check
     * @return bool True if redundant
     */
    private function isRedundantComparison(Node $node): bool
    {
        // Implementation
        return false;
    }
}
```

### Code Documentation

- Use PHPDoc for all public methods
- Include parameter types and return types
- Document exceptions that can be thrown
- Explain non-obvious logic

## Commit Messages

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New rule or feature
- `fix`: Bug fix
- `docs`: Documentation only
- `test`: Add or update tests
- `refactor`: Code refactoring
- `perf`: Performance improvement
- `ci`: CI/CD changes

### Examples

```
feat(visitors): add S1234 rule for code quality

Implement visitor to detect and fix pattern X.
Adds comprehensive tests and documentation.

Closes #456

fix(codefixer): handle nested scopes correctly

The symbol table was not properly tracking nested function scopes.
This caused false positives for unused variables.

docs: update architecture documentation

Add new component diagram and clarify data flow.
```

## Questions?

- Check the [ARCHITECTURE.md](ARCHITECTURE.md) for system design
- Review [existing PRs](https://github.com/Chand335/sonarFixer/pulls) for examples
- Open an issue to discuss before starting large changes

## License

By contributing, you agree your code will be licensed under the MIT License.
