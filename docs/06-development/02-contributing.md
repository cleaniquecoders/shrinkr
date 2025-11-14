# Contributing

Thank you for considering contributing to Shrinkr! This guide will help you get started with contributing to the project.

## Getting Started

### Prerequisites

- PHP 8.2, 8.3, or 8.4
- Composer
- Git
- Laravel 10, 11, or 12

### Development Setup

1. **Fork the Repository**

   Fork the repository on GitHub and clone your fork:

   ```bash
   git clone https://github.com/your-username/shrinkr.git
   cd shrinkr
   ```

2. **Install Dependencies**

   ```bash
   composer install
   ```

3. **Run Tests**

   Ensure all tests pass:

   ```bash
   ./vendor/bin/pest
   ```

## Development Workflow

### Branching Strategy

- `main` - Stable production branch
- `develop` - Active development branch
- `feature/*` - New features
- `fix/*` - Bug fixes
- `docs/*` - Documentation updates

### Creating a Feature Branch

```bash
git checkout -b feature/your-feature-name
```

### Making Changes

1. **Write Tests First** - Follow TDD principles
2. **Implement Your Feature** - Keep changes focused and minimal
3. **Update Documentation** - Document new features or changes
4. **Run Tests** - Ensure all tests pass
5. **Code Style** - Follow PSR-12 coding standards

### Testing Your Changes

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/UrlTest.php

# Run with coverage
./vendor/bin/pest --coverage

# Run architecture tests
./vendor/bin/pest tests/ArchTest.php
```

### Code Style

Run PHP Code Sniffer and Fixer:

```bash
# Check code style
./vendor/bin/pint --test

# Fix code style issues
./vendor/bin/pint
```

### Static Analysis

Run PHPStan for static analysis:

```bash
./vendor/bin/phpstan analyse
```

## Submitting Changes

### Commit Messages

Follow conventional commit format:

```
type(scope): subject

body

footer
```

**Types:**
- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation changes
- `style` - Code style changes (formatting)
- `refactor` - Code refactoring
- `test` - Adding or updating tests
- `chore` - Maintenance tasks

**Examples:**

```bash
git commit -m "feat(actions): add bulk URL creation action"
git commit -m "fix(models): resolve hasExpired() logic issue"
git commit -m "docs(readme): update installation instructions"
```

### Pull Request Process

1. **Update Your Branch**

   ```bash
   git checkout main
   git pull upstream main
   git checkout feature/your-feature-name
   git rebase main
   ```

2. **Push to Your Fork**

   ```bash
   git push origin feature/your-feature-name
   ```

3. **Create Pull Request**

   - Go to the original repository on GitHub
   - Click "New Pull Request"
   - Select your fork and branch
   - Fill out the PR template

### Pull Request Guidelines

- **Title** - Clear, descriptive title
- **Description** - Explain what and why
- **Tests** - Include tests for new features
- **Documentation** - Update relevant documentation
- **Screenshots** - Add screenshots for UI changes
- **Breaking Changes** - Clearly note any breaking changes

### PR Template

```markdown
## Description

Brief description of changes.

## Type of Change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing

- [ ] All tests pass
- [ ] Added new tests
- [ ] Updated existing tests

## Checklist

- [ ] Code follows PSR-12 standards
- [ ] PHPStan analysis passes
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
```

## Code Standards

### PSR-12 Compliance

Shrinkr follows PSR-12 coding standards:

```php
<?php

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Models\Url;

class CreateShortUrlAction
{
    /**
     * Execute the action
     */
    public function execute(array $data): Url
    {
        // Implementation
    }
}
```

### Type Declarations

Use strict types and type hints:

```php
<?php

declare(strict_types=1);

namespace CleaniqueCoders\Shrinkr\Actions;

use CleaniqueCoders\Shrinkr\Models\Url;

class UpdateShortUrlAction
{
    public function execute(Url $url, array $data): Url
    {
        // Implementation
    }
}
```

### Documentation Blocks

Use PHPDoc for methods and classes:

```php
/**
 * Create a shortened URL
 *
 * @param array $data URL data
 * @return Url The created URL model
 * @throws SlugAlreadyExistsException If slug exists
 */
public function execute(array $data): Url
{
    // Implementation
}
```

## Testing Guidelines

### Writing Tests

```php
test('can create shortened URL with custom slug', function () {
    $data = [
        'original_url' => 'https://example.com',
        'custom_slug' => 'my-slug',
        'user_id' => User::factory()->create()->id,
    ];

    $url = (new CreateShortUrlAction)->execute($data);

    expect($url->custom_slug)->toBe('my-slug')
        ->and($url->shortened_url)->toBe('my-slug');
});
```

### Test Coverage

Aim for:
- **Unit Tests** - Test individual methods
- **Integration Tests** - Test component interactions
- **Feature Tests** - Test complete workflows

### Test Best Practices

1. **Descriptive Names** - Use clear test descriptions
2. **Single Assertion** - Focus on one behavior per test
3. **Clean State** - Use `beforeEach()` for setup
4. **Mock External Calls** - Use `Http::fake()` for APIs
5. **Edge Cases** - Test boundary conditions

## Contributing Areas

### Bug Fixes

Found a bug? Great! Here's how to fix it:

1. **Create an Issue** - Describe the bug
2. **Write a Failing Test** - Reproduce the issue
3. **Fix the Bug** - Implement the fix
4. **Verify Tests Pass** - Ensure the fix works
5. **Submit PR** - Include the issue number

### New Features

Adding a feature? Follow these steps:

1. **Discuss First** - Create an issue to discuss
2. **Plan Implementation** - Outline your approach
3. **Write Tests** - Test-driven development
4. **Implement Feature** - Keep changes focused
5. **Update Docs** - Document the new feature
6. **Submit PR** - Link to the discussion issue

### Documentation

Improving docs? Awesome!

1. **Identify Gaps** - What's missing or unclear?
2. **Write Clear Content** - Use examples
3. **Check Links** - Ensure all links work
4. **Submit PR** - Tag as documentation

### Performance Improvements

Optimizing performance:

1. **Benchmark Current** - Measure existing performance
2. **Implement Optimization** - Make changes
3. **Benchmark Again** - Verify improvement
4. **Document Changes** - Explain the optimization

## Code Review Process

### For Contributors

- **Be Patient** - Reviews take time
- **Be Responsive** - Address feedback promptly
- **Be Open** - Consider suggestions
- **Be Respectful** - Maintain professionalism

### For Reviewers

- **Be Timely** - Review PRs promptly
- **Be Constructive** - Provide helpful feedback
- **Be Specific** - Point to exact issues
- **Be Encouraging** - Recognize good work

## Release Process

### Version Numbering

Shrinkr follows Semantic Versioning (SemVer):

- **Major** (1.0.0) - Breaking changes
- **Minor** (1.1.0) - New features, backward compatible
- **Patch** (1.0.1) - Bug fixes, backward compatible

### Changelog

Update `CHANGELOG.md` with:

```markdown
## v1.1.0 - 2024-03-15

### Added
- New bulk URL creation feature
- Custom domain support

### Changed
- Improved health check performance

### Fixed
- Resolved expiry check edge case

### Deprecated
- Old config format (use new format)
```

## Communication

### Getting Help

- **GitHub Discussions** - General questions
- **GitHub Issues** - Bug reports and features
- **Email** - Sensitive security issues

### Reporting Security Issues

**DO NOT** open public issues for security vulnerabilities.

Email security issues to: [security@cleaniquecoders.com](mailto:security@cleaniquecoders.com)

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

## Recognition

### Contributors

All contributors are recognized in:
- CONTRIBUTORS.md file
- GitHub contributors page
- Release notes

### Hall of Fame

Significant contributions earn a place in our Hall of Fame:
- Major features
- Critical bug fixes
- Extensive documentation
- Community support

## License

By contributing to Shrinkr, you agree that your contributions will be licensed under the MIT License.

## Questions?

Have questions about contributing? Feel free to:
- Open a GitHub Discussion
- Email the maintainers
- Join our community chat

## Thank You! 🎉

Your contributions make Shrinkr better for everyone. We appreciate your time and effort!

---

## Quick Reference

### Common Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/pest

# Fix code style
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse

# Run all checks
composer test
```

### Branch Workflow

```bash
# Create feature branch
git checkout -b feature/my-feature

# Keep branch updated
git pull --rebase origin main

# Push changes
git push origin feature/my-feature
```

### Before Submitting

- [ ] Tests pass
- [ ] Code style fixed
- [ ] PHPStan passes
- [ ] Documentation updated
- [ ] CHANGELOG.md updated
- [ ] Commit messages follow convention

## Next Steps

- [Testing](01-testing.md) - Learn about testing
- [Security](03-security.md) - Security best practices
- [GitHub Repository](https://github.com/cleaniquecoders/shrinkr) - View source code
