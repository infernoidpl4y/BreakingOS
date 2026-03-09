# AGENTS.md - BWebOS Development Guidelines

## Project Overview

BWebOS is a simple PHP-based web operating system. It uses vanilla PHP with embedded HTML and CSS. There is no build system, no Composer dependencies, and no formal testing framework.

## Build / Lint / Test Commands

### Current State
- **No build system**: Plain PHP files, served directly
- **No linting**: No PHP_CodeSniffer, PHP-CS-Fixer, or Psalm configured
- **No testing**: No PHPUnit or other testing framework

### Recommendations for Future

If you add testing later, typical commands would be:

```bash
# PHP syntax check (available now)
php -l filename.php

# With Composer installed
composer require --dev phpunit/phpunit
./vendor/bin/phpunit --filter TestName # Run single test

# PHP-CS-Fixer for code style
composer require --dev friendsofphp/php-cs-fixer
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Psalm for static analysis
composer require --dev vimeo/psalm
./vendor/bin/psalm
```

## Code Style Guidelines

### General Principles
- Follow PSR-12 (PHP Standard Recommendation) as much as possible
- Keep code readable and maintainable
- Use meaningful variable and function names
- Add comments for complex logic only

### Indentation
- Use 4 spaces for indentation (no tabs)
- Be consistent throughout the file

### Naming Conventions
- **Classes**: PascalCase (e.g., `UserAuth`, `SessionManager`)
- **Functions/methods**: camelCase (e.g., `getUserData()`, `processLogin()`)
- **Variables**: camelCase (e.g., `$userName`, `$sessionToken`)
- **Constants**: UPPER_CASE with underscores (e.g., `OS_SYSTEM_DIR`)
- **Files**: lowercase with underscores (e.g., `login_styles.php`)

### PHP Tags
- Use `<?php` for PHP code blocks
- Avoid short tags `<?` - use full `<?php`
- Use short echo tags `<?= ?>` for simple output (acceptable)

### Strings
- Use double quotes for strings with variables: `"Hello $name"`
- Use single quotes for static strings: `'hello world'`
- Prefer string concatenation over interpolation for complex strings

### Arrays
- Use short array syntax: `$array = ['a', 'b', 'c'];`
- Add trailing comma in multi-line arrays

### Functions
- Declare return types when possible
- Use type hints for parameters
- Keep functions small and focused (single responsibility)

```php
// Good
function getUserById(int $id): ?array {
    // ...
}

// Avoid
function getUserById($id) {
    // ...
}
```

### Classes
- Use strict typing at the top of files: `declare(strict_types=1);`
- Define properties with visibility keywords (`private`, `protected`, `public`)
- Use constructor injection for dependencies

```php
<?php
declare(strict_types=1);

class UserService {
    private UserRepository $repository;
    
    public function __construct(UserRepository $repository) {
        $this->repository = $repository;
    }
    
    public function findById(int $id): ?User {
        return $this->repository->find($id);
    }
}
```

### Error Handling
- Use exceptions for error handling (throw meaningful exceptions)
- Never expose sensitive information in error messages
- Log errors appropriately
- In production, display generic error pages, log details

```php
// Good
if (!$user) {
    throw new UserNotFoundException("User with ID $id not found");
}

// Avoid
if (!$user) {
    echo "User not found";
}
```

### Security
- **NEVER** trust user input - always sanitize/validate
- Use prepared statements for database queries
- Escape output with `htmlspecialchars()` before displaying user data
- Implement proper session management
- Store passwords hashed (use `password_hash()`)

```php
// Good - escaping output
echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

// Good - prepared statement (if PDO available)
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);

// Bad - SQL injection vulnerable
$query = "SELECT * FROM users WHERE username = '$username'";
```

### File Organization
- One class per file
- File name should match class name
- Group related files in directories
- Constants in dedicated files or at top of related files

### Include/Require
- Use `require_once` for files that must be loaded (classes, configs)
- Use `include` for optional template files
- Define constants before including files that use them

### Comments
- Add docblocks for classes and public methods
- Comment complex business logic
- Avoid obvious comments

```php
<?php
/**
 * Handles user authentication operations.
 */
class AuthService {
    /**
     * Authenticates user credentials.
     *
     * @param string $username
     * @param string $password
     * @return User|false
     */
    public function authenticate(string $username, string $password) {
        // Complex auth logic here
    }
}
```

### Database/File Operations
- Check return values of file operations
- Handle exceptions from file operations
- Close file handles
- Use transactions for multiple database operations

### Version Control
- Make small, focused commits
- Write meaningful commit messages
- Don't commit sensitive data (passwords, keys, secrets)

### CSS Guidelines (embedded PHP style files)
- Use consistent class naming (BEM or similar)
- Keep CSS in separate style blocks
- Use meaningful class names that describe purpose, not appearance
- Group related styles together
- Use CSS custom properties for values used repeatedly

### JavaScript (if added later)
- Use strict mode
- Prefer `const` over `let`, avoid `var`
- Use ES6+ features
- Keep scripts in separate files when possible

## File Structure

```
/home/infernoid/projects/BWebOS/
├── BWebOS.php              # Main entry point
├── system/
│   ├── templates/          # PHP templates
│   │   ├── desktop.php
│   │   └── login.php
│   ├── styles/             # CSS styles (embedded in PHP)
│   │   ├── desktop_styles.php
│   │   └── login_styles.php
│   ├── imgs/               # Images
│   ├── scripts/            # JavaScript files
│   ├── programs/           # Application programs
│   └── videos/             # Video files
└── users/                   # User data directories
```

## Common Tasks

### Running the Application
Requires a PHP-enabled web server:

```bash
# PHP built-in server (for development)
php -S localhost:8000

# Then access http://localhost:8000/BWebOS.php
```

### Syntax Check
```bash
php -l BWebOS.php
php -l system/templates/login.php
```

## Notes for AI Agents

1. **This is a simple project** - don't overcomplicate solutions
2. **No dependencies** - avoid adding Composer packages unless necessary
3. **Security matters** - this handles user authentication, so follow security best practices
4. **Keep it simple** - prefer vanilla PHP solutions over framework additions
5. **Test changes** - manually verify functionality after modifications
