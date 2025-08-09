# Contributing to DragonHerd

Thank you for your interest in contributing to DragonHerd! This document provides guidelines and information for developers.

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Getting Started

1. Clone the repository:
   ```bash
   git clone https://github.com/kobkob/DragonHerd.git
   cd DragonHerd
   ```

2. Install dependencies:
   ```bash
   make install
   # or
   composer install
   ```

3. Set up your development environment:
   ```bash
   make dev-setup
   ```

## Development Workflow

### Code Standards

We follow WordPress Coding Standards with some modern PHP enhancements:

- **PHP Version**: PHP 8.1+
- **Coding Standards**: WordPress + PSR-12 elements
- **Documentation**: PHPDoc blocks for all classes and methods
- **Naming**: WordPress prefixes (`dragonherd_`, `DragonHerd\`)

### Available Commands

Use the Makefile for common development tasks:

```bash
# Install dependencies
make install

# Run tests
make test

# Code quality checks
make phpcs        # Check coding standards
make phpcs-fix    # Fix coding standards
make phpstan      # Static analysis

# Coverage report
make coverage

# Full CI pipeline
make ci

# Build for distribution
make build
make package

# Clean artifacts
make clean
```

### Testing

We use PHPUnit with Brain Monkey for WordPress function mocking:

#### Running Tests
```bash
# All tests
make test

# With coverage
make coverage

# Specific test file
./vendor/bin/phpunit tests/Unit/DragonHerdManagerTest.php
```

#### Writing Tests

- **Unit tests**: `tests/Unit/` - Test individual classes/methods
- **Integration tests**: `tests/Integration/` - Test plugin integration
- Use Brain Monkey to mock WordPress functions
- Aim for high code coverage
- Follow PHP naming conventions for test methods

Example test:
```php
/**
 * Test that method does something.
 *
 * @test
 */
public function it_does_something(): void
{
    // Arrange
    $expected = 'expected result';
    
    // Act
    $result = $this->manager->doSomething();
    
    // Assert
    $this->assertEquals($expected, $result);
}
```

### Code Quality

#### PHP CodeSniffer (PHPCS)
```bash
# Check standards
make phpcs

# Auto-fix issues
make phpcs-fix
```

#### PHPStan Static Analysis
```bash
# Run static analysis
make phpstan
```

Configuration files:
- `phpcs.xml` - CodeSniffer rules
- `phpstan.neon` - Static analysis configuration

### Git Workflow

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/your-feature`
3. **Write** tests for your changes
4. **Implement** your changes
5. **Run** the full CI pipeline: `make ci`
6. **Commit** with descriptive messages
7. **Push** and create a Pull Request

#### Commit Messages

Follow conventional commits:
```
feat: add new task filtering functionality
fix: resolve API key validation issue
docs: update installation instructions
test: add unit tests for DragonHerdManager
refactor: extract API client to separate class
```

### Pull Request Guidelines

- **Description**: Clear description of changes and motivation
- **Tests**: Include tests for new functionality
- **Documentation**: Update docs if needed
- **Code Quality**: All CI checks must pass
- **Size**: Keep PRs focused and reasonably sized

### Code Review Process

1. Automated CI checks must pass
2. Manual code review by maintainers
3. Address feedback and update PR
4. Final approval and merge

## Architecture

### Plugin Structure
```
dragonherd/
├── dragonherd.php              # Main plugin file
├── includes/
│   ├── class-dragonherd-manager.php  # Main functionality
│   └── admin-page.php          # Admin interface
└── assets/
    └── img/                    # Plugin assets
```

### Key Components

- **DragonHerdManager**: Core functionality class
- **Admin Interface**: WordPress admin integration
- **API Integration**: BugHerd and OpenAI API calls

### Design Principles

- **Single Responsibility**: Classes have one clear purpose
- **Dependency Injection**: Avoid hard dependencies
- **WordPress Integration**: Follow WP hooks and filters pattern
- **Security**: Sanitize inputs, escape outputs
- **Performance**: Efficient API usage, caching where appropriate

## Security

- Always sanitize user inputs
- Use WordPress nonces for forms
- Escape all outputs
- Follow WordPress security best practices
- Report security issues privately

## Documentation

- Update `README.md` for user-facing changes
- Add PHPDoc blocks for all public methods
- Update inline comments for complex logic
- Keep `CHANGELOG.md` updated

## Release Process

1. Update version numbers
2. Update `CHANGELOG.md`
3. Create release tag
4. GitHub Actions will build and deploy

## Getting Help

- **Issues**: Use GitHub Issues for bug reports
- **Discussions**: Use GitHub Discussions for questions
- **Security**: Email security issues privately

## License

By contributing, you agree that your contributions will be licensed under the GPL-3.0 license.
