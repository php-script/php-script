# PHP Script

PHP Script provides an option to provide the end-user a scriptable option for your backend powered by PHP but with the
easiness of Javascript. So this package provides a lexer and an engine to execute user generated PHP Script without the
hazzle to spin up a Node.js service.

<p align="center">
    <p align="center">
        <a href="https://github.com/php-script/php-script/actions"><img alt="GitHub Workflow Status (master)" src="https://github.com/php-script/php-script/actions/workflows/tests.yml/badge.svg"></a>
        <a href="https://packagist.org/packages/php-script/php-script"><img alt="Total Downloads" src="https://img.shields.io/packagist/dt/php-script/php-script"></a>
        <a href="https://packagist.org/packages/php-script/php-script"><img alt="Latest Version" src="https://img.shields.io/packagist/v/php-script/php-script"></a>
        <a href="https://packagist.org/packages/php-script/php-script"><img alt="License" src="https://img.shields.io/packagist/l/php-script/php-script"></a>
    </p>
</p>

------

## tl;dr

On php provide the following setup:

```php
class LoginStats
{
    public function count()
    {
        return 42;
    }
}

class User
{
    public $name = "Administrator";
    public $logins; // Wird ein Objekt sein

    public function __construct()
    {
        $this->logins = new LoginStats();
    }

    public function hasPermission(string $perm)
    {
        return $perm === 'admin';
    }
}

// setting up the PHP Script engine
$engine = new Engine();
$engine->set('user', new User());
$engine->set('app_version', '1.2.3');
$engine->set('users_list', ['Alice', 'Bob', 'Charlie']);
```

And now the PHP Script:

```javascript
// <- this is a line comment
echo 'Hello ' ~ user.name // string concatenation (with ~ Operator) and object access on users property `name`

// calling a method
totalLogins = user.logins.count();
echo 'Logins: ' ~ totalLogins // line end can have a ";" optional

// setting vars and calculate with it
var1 = 10
var2 = var1 * 2 + totalLogins
echo 'Sum: ' ~ var2 // (with ~ operator)

// control flow: if
if (var2 > 50) {
    echo 'var2 is greater than 50!'
}

// control flow: foreach
echo 'users list:'
foreach (users_list as u) {
    echo '- ' ~ u // (write a list item)
}

// method calls with arguments
if (user.hasPermission('admin')) {
    echo 'Zugriff gewährt!'
}

// accessing the 'app_version' Variable
echo 'App Version: ' ~ app_version
```

Then this PHP Script will get executed on PHP like so:

```php
try {
    echo $engine->execute($script);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
```

This will echo:

```text
Hello Administrator
Logins: 42
Sum: 62
var2 is greater than 50!
users list:
- Alice
- Bob
- Charlie
Zugriff gewährt!
App Version: 1.2.3
```

## TODO

- [x] proof of concept
- [ ] create an AST
- [ ] render PHP from AST
- [ ] render PHP Script from AST
- [ ] make it robust
- [ ] improve error display
- [ ] give it 100% code coverage
- [ ] provide a monaco editor component for vanilla javascript
- [ ] provide a monaco editor component for Vue.js
- [ ] provide a monaco editor component for React.js
- [ ] provide dynamic code completion for the editor component by the provided context

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
