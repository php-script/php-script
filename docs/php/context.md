---
title: Handle Context
parent: PHP
nav_order: 3
layout: default
---

# Handle Context
{: .no_toc }

## Table of contents
{: .no_toc .text-delta }

1. TOC
{:toc}

---

## Allow the interaction with context

Besides the global php [functions](./functions.html) you can provide Context Variables as context to the engine.
Therefore the end-user can code with the given variables. The main reason for this is providing context to teh script
with service class instances or definition values like collections of items or access to the current stack and executing
php code with much more functionality built-in and not accessible directly by the end-user.

For example think of having these classes in your application code:

```php
class LoginStats
{
    public int $countLogins = 42;

    public function count(): int
    {
        return $this->countLogins;
    }

    public function increment(): void
    {
        $this->countLogins++;
    }
}

class User
{
    public string $name = 'Administrator';

    public LoginStats $logins;

    public function __construct()
    {
        $this->logins = new LoginStats;
    }

    public function login()
    {
        $this->logins->increment();
    }

    public function hasPermission(string $perm): bool
    {
        return $perm === 'admin';
    }
}
```

Now you can provide variables as context to the engine:

```php
use PhpScript\Core\Engine;

$engine = new Engine;
$engine->set('user', new \User, 'User instance')
    ->set('app_version', '1.0.0', 'Application version')
    ->set('users_list', ['Alice', 'Bob', 'Charlie'], 'List of users');
```

And then you can run the engine and execute a given script:

```php
try {
    $echoResult = $engine->execute($phpScript);
} catch (\PhpScript\Exceptions\EngineException $exception) {
    // log or fetch the cause of the error
}
```
