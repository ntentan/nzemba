# ntentan/nzemba

A lightweight, repository-based ORM and data access library for the **ntentan** framework.

## Features

- **Repository Pattern**: Clean separation of domain models and database persistence.
- **Model Reflection & Caching**: Automatic schema extraction and property caching using `ntentan/kaikai`.
- **Pluggable Storage Backends**: Extensible backend architecture (`Backend`) supporting SQL database engines (e.g., PostgreSQL support via `ntentan\nzemba\backends\Postgres`).
- **Flexible Data Mapping**: Insert records directly from associative arrays or model objects, automatically mapping defined model properties.
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
use ntentan\nzemba\Repository;
use ntentan\nzemba\backends\Postgres;
use ntentan\nzemba\backends\SqlDatabase;

$pdo = new PDO('pgsql:host=localhost;dbname=myapp', 'username', 'password');
$backend = new SqlDatabase($pdo, new Postgres());

// Repository resolves table name to pluralized decamelized form (e.g., 'users')
// An optional Cache and custom table name can also be provided
$userRepository = new Repository(User::class, $backend);

// Insert a record from an array
$userId = $userRepository->insert([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Or insert from a model object instance
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$userId = $userRepository->insert($user);

// Query records
$users = $userRepository->find(['role' => 'admin']);
$user = $userRepository->findOne($userId);
```

### 3. Service Container Configuration

You can also use the `Repository::getService()` helper for dependency injection wiring (providing bindings for `Backend::class` and `PDO::class`):

```php
$services = Repository::getService([
    'dsn' => 'pgsql:host=localhost;dbname=myapp',
    'user' => 'username',
    'password' => 'password'
]);
```

## License

This project is licensed under the MIT License.
