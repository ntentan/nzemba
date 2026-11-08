<?php

namespace ntentan\nzemba;
use ntentan\kaikai\backends\VolatileCache;
use ntentan\kaikai\Cache;
use ntentan\nzemba\backends\Backend;
use ntentan\nzemba\exceptions\RepositoryException;
use ntentan\utils\Text;


class Repository
{
    private string $model;
    private Cache $cache;
    private Backend $backend;
    private string $table;

    public function __construct(string $model, Backend $backend, ?Cache $cache = null, ?string $table = null)
    {
        $this->model = $model;
        $this->backend = $backend;
        $this->cache = $cache ?? new Cache(new VolatileCache());
        $parts = explode("\\", $model);
        $this->table = $table ?? Text::pluralize(Text::deCamelize(end($parts)));
    }

    private function getFields(): array
    {
        return $this->cache->read(
            "repository:{$this->table}",
            function() {
                $reflection = new \ReflectionClass($this->model);
                $properties = $reflection->getProperties();
                $fields = [];
                foreach($properties as $property) {
                    $type = $property->getType();
                    $fields[] = [
                        'name' => $property->name,
                        'type' => $type ? (string)$type : null,
                        'is_required' => $type ? !$type->allowsNull() : false
                    ];
                }
                return $fields;
            }
        );
    }

    private function getDataFromArray($item): array
    {
        $data = [];
        $fields = $this->getFields();
        foreach($fields as $field) {
            $name = $field['name'];
            if (isset($item[$name])) {
                $data[$name] = $item[$name];
            }
        }
        return $data;
    }

    private function getDataFromObject($item): array
    {
        $data = [];
        $fields = $this->getFields();
        foreach($fields as $field) {
            $name = $field['name'];
            $data[$name] = $item->$name ?? null;
        }
        return $data;
    }

    public function insert(mixed $item): int
    {
        if (is_array($item)) {
            $data = $this->getDataFromArray($item);
        } else if (is_object($item)) {
            $data = $this->getDataFromObject($item);
        } else {
            throw new RepositoryException("Unknown datatype for repository insert");
        }
        return $this->backend->insert($this->table, $data);
    }

    public function find(mixed $key = null): array
    {
        return $this->backend->find($this->table, $key, $this->model);
    }

    public function findOne(mixed $key = null): ?object
    {
        return $this->backend->findOne($this->table, $key, $this->model);
    }

    public static function getService(array $config): array
    {
        if(isset($config['dsn'])) {
            $driver = explode(":", $config['dsn'] ?? "", 2)[0];
            $sqlHelperClass = match ($driver) {
                'pgsql' => backends\Postgres::class,
                default => throw new RepositoryException("Unsupported database driver: {$driver}")
            };
        } else {
            throw new RepositoryException("Please specify a driver for the repository backend.");
        }
        return [
            backends\Backend::class => backends\SqlDatabase::class,
            backends\SqlHelper::class => $sqlHelperClass,
            \PDO::class => function () use ($config) {
                return new \PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null);
            }
        ];
    }
}
