# AGENTS.md - BWebOS Development Guidelines

## Project Overview

BWebOS is a simple PHP-based web operating system. It uses vanilla PHP with embedded HTML and CSS. No build system, no Composer dependencies, no formal testing framework.

## Build / Lint / Test Commands

### Available Commands
```bash
# PHP syntax check (available now)
php -l filename.php

# Run PHP built-in server
php -S localhost:8000
# Access at http://localhost:8000/BWebOS.php
```

### If Testing/Linting Added Later
```bash
# PHP syntax check
php -l filename.php

# With Composer - run single test
composer require --dev phpunit/phpunit
./vendor/bin/phpunit --filter TestName

# PHP-CS-Fixer for code style
composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Psalm for static analysis
composer require --dev vimeo/psalm
./vendor/bin/psalm
```

## Code Style Guidelines

### General Principles
- Follow PSR-12 where possible
- Keep code readable and maintainable
- Use meaningful variable and function names

### Naming Conventions
- **Classes**: PascalCase (`UserAuth`, `SessionManager`)
- **Functions/methods**: camelCase (`getUserData()`)
- **Variables**: camelCase (`$userName`, `$sessionToken`)
- **Constants**: UPPER_CASE (`OS_SYSTEM_DIR`)
- **Files**: lowercase with underscores (`login_styles.php`)

### PHP Tags
- Use `<?php` for PHP code blocks
- Avoid short tags `<?`
- Short echo tags `<?= ?>` are acceptable

### Strings & Arrays
- Double quotes for strings with variables: `"Hello $name"`
- Single quotes for static strings: `'hello world'`
- Short array syntax: `$array = ['a', 'b', 'c'];`
- Trailing comma in multi-line arrays

### Functions
- Declare return types when possible
- Use type hints for parameters
- Keep functions small and focused

```php
function getUserById(int $id): ?array { /* ... */ }
```

### Classes
- Use strict typing: `declare(strict_types=1);`
- Define properties with visibility keywords
- Use constructor injection for dependencies

### Error Handling
- Use exceptions for error handling
- Never expose sensitive information in error messages
- Log errors appropriately

### Security
- **NEVER** trust user input - always sanitize/validate
- Escape output with `htmlspecialchars()` before displaying user data
- Use prepared statements for database queries
- Store passwords hashed with `password_hash()`

```php
// Good - escaping output
echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

// Bad - SQL injection vulnerable
$query = "SELECT * FROM users WHERE username = '$username'";
```

### File Organization
- One class per file
- File name should match class name
- Group related files in directories

### Include/Require
- Use `require_once` for files that must be loaded (classes, configs)
- Use `include` for optional template files
- Define constants before including files that use them

### CSS Guidelines (embedded PHP style files)
- Use consistent class naming (BEM or similar)
- Use meaningful class names that describe purpose, not appearance
- Use CSS custom properties for values used repeatedly

### JavaScript
- Use strict mode
- Prefer `const` over `let`, avoid `var`
- Use ES6+ features
- Keep scripts in separate files when possible

## File Structure

```
BWebOS/
├── BWebOS.php              # Main entry point
├── system/
│   ├── templates/          # PHP templates (desktop.php, login.php)
│   ├── styles/             # CSS styles (desktop_styles.php, login_styles.php)
│   ├── imgs/               # Images
│   └── programs/           # Application programs
│       ├── terminal/       # Terminal program
│       ├── notepad/        # Notepad program
│       ├── breakcode/      # Breakcode editor
│       └── calculator/      # Calculator
└── users/                  # User data directories
```

## Common Tasks

### Running the Application
```bash
php -S localhost:8000
# Access http://localhost:8000/BWebOS.php
```

### Syntax Check
```bash
php -l BWebOS.php
php -l system/templates/login.php
```

## Notes for AI Agents

1. **This is a simple project** - don't overcomplicate solutions
2. **No dependencies** - avoid adding Composer packages unless necessary
3. **Security matters** - handles user authentication, follow security best practices
4. **Keep it simple** - prefer vanilla PHP solutions over framework additions
5. **Test changes** - manually verify functionality after modifications
