# PHP Script

<p align="center">
    <a href="https://github.com/php-script/php-script" target="_blank">
        <img src="/art/php-script-logo.png" alt="PHP Script" style="width:70%;">
    </a>
</p>

<p align="center">
    <a href="https://github.com/php-script/php-script/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/php-script/php-script/actions/workflows/tests.yml/badge.svg"></a>
    <a href="https://packagist.org/packages/php-script/php-script"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/php-script/php-script"></a>
    <a href="https://packagist.org/packages/php-script/php-script"><img alt="Latest Version" src="https://img.shields.io/packagist/v/php-script/php-script"></a>
    <a href="https://packagist.org/packages/php-script/php-script"><img alt="License" src="https://img.shields.io/packagist/l/php-script/php-script"></a>
</p>

---

PHP Script is a scripting language that allows end-users to customize and extend your PHP-powered backend with the simplicity of JavaScript. It provides a secure and controlled environment to execute user-generated scripts without the need for a separate Node.js service.

## Features

- **Easy to Use:** The syntax is inspired by JavaScript, making it familiar to a wide range of developers.
- **Secure:** The engine provides a sandboxed environment, giving you full control over the exposed functions and data.
- **Flexible:** You can expose any PHP function or variable to the script, allowing for powerful customizations.
- **Lightweight:** The package is designed to be lightweight and has minimal dependencies.

## Installation

You can install the package via composer:

```bash
composer require php-script/php-script
```

## Usage

### 1. Setting up the Engine

First, you need to create an instance of the `Engine` and expose the necessary data and functions to the script.

```php
use PhpScript\Core\Engine;

class LoginStats
{
    public function count(): int
    {
        return 42;
    }
}

class User
{
    public string $name = "Administrator";
    public LoginStats $logins;

    public function __construct()
    {
        $this->logins = new LoginStats();
    }

    public function hasPermission(string $perm): bool
    {
        return $perm === 'admin';
    }
}

// Setting up the PHP Script engine
$engine = new Engine();
$engine->set('user', new User());
$engine->set('app_version', '1.2.3');
$engine->set('users_list', ['Alice', 'Bob', 'Charlie']);
```

### 2. Writing a PHP Script

Now, you can write a script that interacts with the exposed data and functions.

```javascript
// This is a line comment
echo 'Hello ' ~ user.name // String concatenation and object property access

// Calling a method
totalLogins = user.logins.count();
echo 'Logins: ' ~ totalLogins;

// Working with variables
var1 = 10;
var2 = var1 * 2 + totalLogins;
echo 'Sum: ' ~ var2;

// Conditional statements
if (var2 > 50) {
    echo 'var2 is greater than 50!';
}

// Looping through an array
echo 'Users list:';
foreach (users_list as u) {
    echo '- ' ~ u;
}

// Calling a method with an argument
if (user.hasPermission('admin')) {
    echo 'Access granted!';
}

// Accessing a global variable
echo 'App Version: ' ~ app_version;
```

### 3. Executing the Script

Finally, you can execute the script using the `execute` method of the `Engine`.

```php
try {
    echo $engine->execute($script);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```

This will produce the following output:

```text
Hello Administrator
Logins: 42
Sum: 62
var2 is greater than 50!
Users list:
- Alice
- Bob
- Charlie
Access granted!
App Version: 1.2.3
```

## TODO

- [x] Proof of concept
- [x] Create an Abstract Syntax Tree (AST)
- [x] Render PHP from AST
- [ ] Improve robustness and error handling
- [ ] Achieve 100% code coverage
- [ ] Provide a Monaco editor component for vanilla JavaScript
- [ ] Provide a Monaco editor component for Vue.js
- [ ] Provide a Monaco editor component for React.js
- [ ] Implement dynamic code completion for the editor component based on the provided context

## Local development

🧹 Keep a modern codebase with **Pint**:
```bash
composer lint
```

✅ Run refactors using **Rector**
```bash
composer refactor
```

⚗️ Run static analysis using **PHPStan**:
```bash
composer test:types
```

✅ Run unit tests using **PEST**
```bash
composer test:unit
```

🚀 Run the entire test suite:
```bash
composer test
```

**PHP Script** was created by **[Robert Kummer](https://robert-kummer.de)** under the **[MIT license](https://opensource.org/licenses/MIT)**.
