# SonarFixer Architecture

## Overview

SonarFixer is built on a modular architecture that leverages the **Visitor Pattern** combined with PHP's Abstract Syntax Tree (AST) capabilities. This design enables clean, maintainable code transformations while supporting multiple SonarCube rules.

## Core Design Principles

1. **Separation of Concerns**: Each rule is isolated in its own visitor class
2. **Extensibility**: New rules can be added without modifying existing code
3. **Maintainability**: Clear abstractions and interfaces make the codebase easy to understand
4. **Performance**: Efficient AST traversal and minimal memory overhead
5. **Testability**: Each component can be tested independently

## System Architecture

### Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    CLI Entry Point                          │
│                  (bin/sonar-fixer.php)                      │
└──────────────────────┬──────────────────────────────────────┘
                       │
        ┌──────────────┴──────────────┐
        │                             │
   ┌────▼─────┐              ┌───────▼────┐
   │CodeFixer │              │IssueScanner│
   └────┬─────┘              └────────────┘
        │
   ┌────▼────────────────────────────┐
   │    Core Processing Pipeline     │
   ├────────────────────────────────┤
   │ 1. Parser (nikic/php-parser)   │
   │ 2. Visitor Traversal           │
   │ 3. Rule Application            │
   │ 4. AST Modification            │
   │ 5. Code Generation             │
   └────┬────────────────────────────┘
        │
   ┌────┴──────────────────────────────────┐
   │      Visitor Pattern Classes          │
   ├──────────────────────────────────────┤
   │ - BaseVisitor (abstract)             │
   │ - BooleanLiteralVisitor (S1125)      │
   │ - UnusedVariableVisitor (S1481)      │
   │ - DeadCodeVisitor (S1763)            │
   │ - UtilityClassVisitor (S1118)        │
   └───────────────────────────────────────┘
        │
   ┌────▼──────────────────────┐
   │  Supporting Classes       │
   ├──────────────────────────┤
   │ - SymbolTable            │
   │ - RuleRegistry           │
   │ - Result Handler         │
   └──────────────────────────┘
```

## Core Components

### 1. CodeFixer (Main Orchestrator)

**File**: `src/CodeFixer.php`

Responsibilities:
- Parses PHP code into an AST
- Applies selected rules via visitor pattern
- Tracks and aggregates changes
- Generates fixed code output
- Handles file I/O operations

**Key Methods**:
```php
public function fixCode(string $code, string $filename, array $rules = []): array
public function fixFile(string $filePath, array $rules = []): array
public function fixDirectory(string $dirPath, array $rules = []): array
```

**Flow**:
1. Accept raw PHP code or file path
2. Parse code to AST using `nikic/php-parser`
3. Create visitor instances for specified rules
4. Traverse AST with each visitor
5. Collect changes from all visitors
6. Convert modified AST back to PHP code
7. Return results with change count and success status

### 2. BaseVisitor (Abstract Foundation)

**File**: `src/Visitors/BaseVisitor.php`

Abstract class that all rule-specific visitors extend.

**Key Features**:
- Implements `PhpParser\NodeVisitor` interface
- Provides `recordChange()` method for tracking modifications
- Manages node replacement and removal
- Maintains change history for reporting

**Template Methods**:
```php
public function enterNode(Node $node): ?int
public function leaveNode(Node $node): ?Node
public function recordChange(Node $node, string $rule, string $description)
```

### 3. SymbolTable (Scope Tracking)

**File**: `src/SymbolTable.php`

Maintains variable and symbol scoping information needed for analysis.

**Responsibilities**:
- Track variable declarations and usage
- Manage nested scopes (functions, classes, blocks)
- Count variable usages
- Identify unused symbols
- Provide scope-aware variable queries

**Key Methods**:
```php
public function enterScope(string $type, ?string $name = null): void
public function exitScope(): void
public function declareVariable(string $name, Node $node): void
public function useVariable(string $name): void
public function isUnused(string $name): bool
```

### 4. RuleRegistry (Rule Management)

**File**: `src/Rules/RuleRegistry.php`

Centralized registry for all available SonarCube rules.

**Responsibilities**:
- Register new rules
- Map rules to visitor implementations
- Manage rule metadata (name, severity, description)
- Validate rule IDs
- Provide rule lookup functionality

**Structure**:
```php
[
    'S1125' => [
        'name' => 'Boolean Literals Should Not Be Used Directly',
        'severity' => 'minor',
        'visitor' => 'SonarFixer\Visitors\BooleanLiteralVisitor',
        'description' => 'Removes redundant boolean literal comparisons'
    ]
]
```

### 5. IssueScanner (Detection Only)

**File**: `src/Scanner/IssueScanner.php`

Scans code for issues without applying fixes (read-only mode).

**Responsibilities**:
- Parse PHP code
- Apply scanning visitors
- Collect issues without modifying code
- Generate issue reports

**Key Methods**:
```php
public function scanFile(string $filePath, array $rules = []): array
public function scanCode(string $code, string $filename, array $rules = []): array
public function scanDirectory(string $dirPath, array $rules = []): array
```

## Visitor Pattern Implementation

### How It Works

1. **Parse Phase**: PHP code is parsed into an AST
2. **Visitor Phase**: Tree walker traverses nodes depth-first
3. **Check Phase**: Each visitor's methods are called on enter/leave
4. **Transform Phase**: Visitors return modified nodes or null to remove
5. **Generate Phase**: Modified AST is converted back to PHP code

### Example Visitor Structure

```php
class MyVisitor extends BaseVisitor
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\If_) {
            // Analyze condition
            // Apply transformation if needed
            $this->recordChange($node, 'S1125', 'Removed redundant boolean comparison');
            return $modifiedNode; // Return modified node
        }
        return null; // null = no change
    }
}
```

## Processing Pipeline

### Step-by-Step Execution

```
Input Code
    ↓
PHP Parser
    ↓ (AST)
Visitor Instantiation
    ↓
AST Traversal (enter nodes)
    ↓
Rule Logic Application
    ↓
AST Traversal (leave nodes)
    ↓
Node Replacement/Removal
    ↓
Code Generation
    ↓
Output Result
```

### Result Structure

```php
[
    'success' => true,
    'fixed_code' => '<?php ...',
    'changes_count' => 3,
    'changes' => [
        [
            'rule' => 'S1125',
            'line' => 10,
            'description' => 'Removed redundant boolean comparison'
        ]
    ]
]
```

## Rule Lifecycle

### Creating a New Rule

1. **Create Visitor Class** (`src/Visitors/NewRuleVisitor.php`)
   - Extend `BaseVisitor`
   - Implement `leaveNode()` logic
   - Use `recordChange()` for tracking

2. **Register Rule** (in `RuleRegistry`)
   - Add rule metadata
   - Map to visitor class
   - Set severity level

3. **Write Tests** (`tests/`)
   - Test before/after code
   - Verify change count
   - Test edge cases

4. **Add Documentation** (`docs/rules/`)
   - Explain the rule
   - Show examples
   - Document edge cases

## Data Flow Examples

### Fix Mode

```
CLI Input: "php bin/sonar-fixer.php fix src --rules=S1125"
    ↓
CodeFixer::fixDirectory('src', ['S1125'])
    ↓
For each PHP file:
  - Parse to AST
  - Create BooleanLiteralVisitor
  - Traverse AST
  - Collect changes
  - Write back to file
    ↓
Report: "Fixed 5 files, 12 changes total"
```

### Scan Mode

```
CLI Input: "php bin/sonar-fixer.php scan src"
    ↓
IssueScanner::scanDirectory('src')
    ↓
For each PHP file:
  - Parse to AST
  - Create scan visitors (all rules)
  - Traverse AST
  - Collect issues (NO modifications)
    ↓
Report: Issues grouped by rule and file
```

## Performance Considerations

### Optimization Strategies

1. **Lazy Visitor Creation**: Only instantiate visitors for selected rules
2. **Single AST Traversal**: One parser pass, multiple visitors applied
3. **Memory Efficiency**: Stream large files when possible
4. **Selective Scanning**: Skip rules not requested

### Benchmarks (Approximate)

- Single file: ~50-100ms
- Directory (100 files): ~5-10 seconds
- Memory: ~2-5MB per file

## Extension Points

### How to Extend

1. **New Rule**: Create visitor extending `BaseVisitor`
2. **Custom Visitors**: Implement `PhpParser\NodeVisitor`
3. **Output Formats**: Add new result formatters
4. **Configuration**: Extend `RuleRegistry` with config loading

## Testing Strategy

### Test Categories

1. **Unit Tests**: Individual visitor logic
2. **Integration Tests**: Full fix pipeline
3. **Regression Tests**: Verify known issues stay fixed
4. **Performance Tests**: Ensure speed targets

### Test Fixtures

```
tests/
├── fixtures/
│   ├── before/
│   │   └── s1125_before.php
│   └── after/
│       └── s1125_after.php
├── CodeFixerTest.php
└── Visitors/
    ├── BooleanLiteralVisitorTest.php
    └── UnusedVariableVisitorTest.php
```

## Error Handling

### Exception Types

- `ParseException`: PHP code parsing failure
- `RuleNotFoundException`: Invalid rule ID
- `FileNotFoundException`: File/directory doesn't exist
- `PermissionException`: No write permission

## Future Enhancements

1. **Configuration System**: .sonarfixer.json support
2. **Plugin Architecture**: Load external rule sets
3. **Performance Optimization**: Parallel file processing
4. **IDE Integration**: LSP server implementation
5. **Advanced Reporting**: JSON, XML, HTML output formats
