# Changelog

All notable changes to SonarFixer will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project setup and documentation
- Project architecture and contribution guidelines
- Core framework for AST-based code fixing
- Rule registration system
- Support for multiple SonarCube rules

### Planned
- Configuration file support (.sonarfixer.json)
- GitHub Actions integration
- Performance optimization for large codebases
- Custom rule registration API
- JSON/XML output formats for CI/CD
- Pre-commit hook integration
- Additional SonarCube rules (S1066, S1128, etc.)

## [0.1.0] - 2026-07-18

### Added
- **Initial Release**
- Core `CodeFixer` class for fixing PHP code
- `IssueScanner` class for detecting issues without fixes
- Visitor pattern implementation for rule processing
- `SymbolTable` for scope and variable tracking
- `RuleRegistry` for centralized rule management

#### Supported Rules
- **S1125**: Boolean literals should not be used directly
  - Removes redundant `== true` and `=== false` comparisons
  - Converts to `$var` or `!$var` idioms
- **S1481**: Local variables should not be declared and then unused
  - Detects unused variable declarations
  - Reports issues without removing (destructive change)
- **S1763**: All code should be reachable
  - Removes dead code after return statements
  - Removes dead code after break/continue
- **S1118**: Utility classes should not have public constructors
  - Detects utility classes with only static members
  - Adds or makes private the constructor

#### Features
- **CLI Interface**
  - `fix` command: Apply fixes to files/directories
  - `scan` command: Report issues without modifications
  - `list-rules` command: Display available rules
  - `--dry-run` flag: Preview changes without writing
  - `--rules` flag: Select specific rules

- **PHP API**
  - `fixCode()`: Fix string of PHP code
  - `fixFile()`: Fix single PHP file
  - `fixDirectory()`: Fix all PHP files in directory
  - `scanCode()`: Scan code for issues
  - `scanFile()`: Scan single file for issues
  - `scanDirectory()`: Scan directory for issues

- **Configuration**
  - `composer.json` with proper PSR-4 autoloading
  - `phpunit.xml` for testing framework
  - Support for dev dependencies (PHPUnit, PHP_CodeSniffer)

- **Documentation**
  - Comprehensive README with examples
  - ARCHITECTURE.md explaining system design
  - CONTRIBUTING.md with development guidelines
  - CHANGELOG.md (this file)
  - Implementation guides for adding new rules

### Dependencies
- `nikic/php-parser: ^4.14` - PHP parsing and AST manipulation
- `phpunit/phpunit: ^9.0` (dev) - Testing framework
- `squizlabs/php_codesniffer: ^3.7` (dev) - Code style checking

---

## Version History Summary

### v0.1.0 (Initial Release)
- Core framework and 4 SonarCube rules
- CLI and PHP API
- Full documentation

### Future Versions
See [Unreleased](#unreleased) section for planned features.

## Migration Guides

### Upgrading from 0.0.x to 0.1.0
Initial release - no migration needed.

## Known Issues

### Current
- None documented yet

### Fixed in Previous Versions
- N/A (initial release)

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on reporting bugs and submitting improvements.

## Deprecations

None currently.

## Security

No known security vulnerabilities. Please report security issues responsibly.
