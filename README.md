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

// Optionally, set an execution time limit to prevent infinite loops
$engine->setExecutionTimeLimit(5); // Script will time out after 5 seconds
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

## PHP Script Language Reference

This section provides a comprehensive overview of the PHP Script language syntax, including all supported statements, expressions, and their variations.

### 1. Basic Statements and Expressions

#### Echo Statement

The `echo` statement is used to output values.

```javascript
echo 'Hello World!';
echo 123;
echo true;
echo LINEBREAK; // Outputs a new line
```

#### Variable Assignment

Variables can be assigned values using the `=` operator. Variables are dynamically typed.

```javascript
myVariable = 10;
anotherVariable = 'some text';
booleanVariable = true;
```

#### Literals

PHP Script supports the following literal types:

-   **Numbers:** Integers and floating-point numbers.
    ```javascript
    number = 123;
    floatNumber = 3.14;
    ```
-   **Strings:** Enclosed in single or double quotes.
    ```javascript
    singleQuoteString = 'Hello';
    doubleQuoteString = "World";
    ```
-   **Booleans:** `true` and `false`.
    ```javascript
    isTrue = true;
    isFalse = false;
    ```
-   **Null:** `null`.
    ```javascript
    emptyValue = null;
    ```
-   **LINEBREAK:** A special keyword representing a new line character.
    ```javascript
    echo 'Line 1' ~ LINEBREAK ~ 'Line 2';
    ```

### 2. Operators

#### Arithmetic Operators

Standard arithmetic operations are supported.

-   **Addition:** `+`
-   **Subtraction:** `-`
-   **Multiplication:** `*`
-   **Division:** `/`

```javascript
result = 10 + 5;    // 15
result = 20 - 7;    // 13
result = 4 * 6;     // 24
result = 100 / 10;  // 10
```

#### Concatenation Operator

The `~` operator is used for string concatenation.

```javascript
greeting = 'Hello' ~ ' ' ~ 'World!'; // "Hello World!"
```

#### Comparison Operators

Used for comparing values.

-   **Equal to:** `==` (loose comparison)
-   **Strictly equal to:** `===` (strict comparison, checks value and type)
-   **Not equal to:** `!=` (loose comparison)
-   **Strictly not equal to:** `!==` (strict comparison)
-   **Greater than:** `>`
-   **Less than:** `<`

```javascript
isEqual = (10 == '10');   // true
isStrictlyEqual = (10 === '10'); // false
isNotEqual = (10 != 5);   // true
isStrictlyNotEqual = (10 !== '10'); // true
isGreater = (20 > 10);    // true
isLess = (5 < 10);        // true
```

#### Unary Operators

Operators that operate on a single operand.

-   **Negation:** `-` (for numbers)
-   **Logical NOT:** `!` (for booleans)

```javascript
negativeNumber = -10;
isNotTrue = !true; // false
```

#### Postfix Operators

Operators that appear after their operand.

-   **Increment:** `++`
-   **Decrement:** `--`

```javascript
count = 0;
count++; // count is now 1
count--; // count is now 0
```

### 3. Control Flow

#### If-Else Statement

Executes a block of code if a condition is true. An optional `else` block can be provided for when the condition is false.

```javascript
// Basic if statement
if (user.hasPermission('admin')) {
    echo 'Access granted!';
}

// If-else statement
if (totalLogins > 100) {
    echo 'High activity!';
} else {
    echo 'Normal activity.';
}
```

#### For Loop

Executes a block of code a specified number of times. It consists of an initializer, a condition, and an increment expression, all of which are optional.

```javascript
// Standard for loop
for (i = 0; i < 5; i++) {
    echo 'Iteration: ' ~ i ~ LINEBREAK;
}

// For loop with optional parts
for (; count < 10;) { // No initializer, no increment
    echo 'Count: ' ~ count ~ LINEBREAK;
    count++;
}

for (;;) { // Infinite loop (use with caution, requires a break condition inside the body)
    // Note: Break statement is not yet supported.
}
```

#### Foreach Loop

Iterates over elements of an array or iterable object.

```javascript
// Foreach loop over values
foreach (users_list as u) {
    echo '- ' ~ u ~ LINEBREAK;
}

// Foreach loop over key-value pairs (key is optional)
foreach (users_map as key, value) {
    echo key ~ ': ' ~ value ~ LINEBREAK;
}
```

### 4. Object and Array Interaction

#### Member Access

Access properties or methods of an object using the `.` operator.

```javascript
userName = user.name;
loginCount = user.logins.count();
```

#### Array Access

Access elements of an array using square brackets `[]`.

```javascript
firstUser = users_list[0];
secondUser = users_list[1];
```

#### Function Calls

Call functions or methods with arguments.

```javascript
echo 'Hello World!'; // Calling a global function (if exposed)
user.hasPermission('admin'); // Calling a method on an object
```

## Features

- Abstract Syntax Tree (AST) is in use
- we render PHP from AST
- we can render PHP Script from AST
- robust error handling with a pointer to the root cause in the PHP Script
- 100% code coverage
- Whitelist implementation for allowing function calls
- Playground
  - use `make playground` and open http://localhost:8080/playground.php in your browser
  - with a Monaco prepared editor
  - using PhpScriptRenderer as linter
- Monarch language definition for the keywords and dynamic code suggestion for provided context
  - Monaco-based editors can learn the language and provide code completion (Monaco, vscode)

## TODO

- [ ] render Mermaid.js Flowchart from AST
- [ ] Provide a Monaco editor component for vanilla JavaScript
- [ ] Provide a Monaco editor component for Vue.js
- [ ] Provide a Monaco editor component for React.js

## Contribution

1. Create a branch from main
2. do your stuff
3. document your stuff here
4. call `composer lint` until no errors
5. call `composer refactor` until no errors
6. call `composer lint` again until no errors
7. call `composer test` until no errors
8. commit and push your changes and open a PR

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
