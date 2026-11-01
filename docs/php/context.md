---
title: Handle Context
parent: PHP
nav_order: 4
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
Therefore the end-user can code with the given variables. The main reason for this is providing context to the script
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

## What scripts can do with context objects

{: .warning }
Scripts can **write** to context objects. `user.name = 'x'` changes the property of the object you
passed in, and the change is visible in your PHP code after the script ran. Only set objects whose
public properties end-users may change, or pass a copy or a read-only view.

- **Properties:** public properties can be read and written. Objects with `__get` and `__set`
  (e.g. Eloquent models) work through these methods.
- **Methods:** *every* public instance method can be called, including methods that change state
  or do I/O. Review each exposed class as part of your API.
- **Static methods** cannot be called through an instance (`user.create()` fails when `create`
  is static), because an instance would otherwise give access to every static factory of the class.
- **Magic methods** (`__toString`, `__destruct`, …) cannot be called directly.
- **Closures** set as context can be passed as callbacks, but their methods (`call`, `bind`,
  `bindTo`) cannot be called.
- **`ArrayAccess`** objects support `bag['key']` for reading and writing.

Arrays are passed by value: a script changing `items[0]` works on its own copy.

Exception messages from your methods are passed to the script's caller as part of the
`EngineException` message. Do not include secrets in exception messages of exposed code, if you
show errors to end-users.
