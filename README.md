# ntentan/nzemba

A lightweight, repository-based ORM and data access library for the **ntentan** framework.

## Features

- **Repository Pattern**: Clean separation of domain models and database persistence.
- **Model Reflection & Caching**: Automatic schema extraction and property caching using `ntentan/kaikai`.
- **Driver-Agnostic Query Generation**: Pluggable SQL query generation (e.g., PostgreSQL support via `PostgresQueryGenerator`).
- **PDO Integration**: Seamless integration with PDO for prepared statements and data mapping.

## Requirements

- PHP 8.0+
- `ntentan/utils`
- `ntentan/kaikai`
- `pdo` and relevant database drivers (e.g., `pdo_pgsql`)

## Installation

Install via Composer:

```bash
composer require ntentan/nzemba
```

## Basic Usage

### 1. Define a Model

```php
namespace App\Models;

class User
{
    public ?int $id = null;
    public string $name;
    public string $email;
}
```

### 2. Configure and Instantiate Repository

```php
use App\Models\User;
use ntentan\kaikai\Cache;
use ntentan\nzemba\Repository;
use ntentan\nzemba\generators\Postgres;

$pdo = new PDO('pgsql:host=localhost;dbname=myapp', 'username', 'password');
$cache = new Cache(/* ... */);
$generator = new Postgres($pdo);

// Repository resolves table name to pluralized decamelized form (e.g., 'users')
$userRepository = new Repository(User::class, $generator, $cache);

// Insert a record
$userId = $userRepository->insert([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);
```

### 3. Service Container Configuration

You can also use `Repository::getService()` helper for dependency injection wiring:

```php
$services = Repository::getService([
    'dsn' => 'pgsql:host=localhost;dbname=myapp',
    'user' => 'username',
    'password' => 'password'
]);
```

## License

This project is licensed under the MIT License.
