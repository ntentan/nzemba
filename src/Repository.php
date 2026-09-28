<?php

namespace ntentan\nzemba;
use ntentan\kaikai\backends\VolatileCache;
use ntentan\kaikai\Cache;
use ntentan\nzemba\exceptions\RepositoryException;
use ntentan\utils\Text;
use ntentan\nzemba\generators\Generator;


class Repository
{
    private string $model;
    private Cache $cache;
    private Generator $queryGenerator;
    private string $table;

    public function __construct(string $model, Generator $queryGenerator, ?Cache $cache = null, ?string $table = null)
    {
        $this->model = $model;
        $this->queryGenerator = $queryGenerator;
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
        return $this->queryGenerator->insert($this->table, $data);
    }

    public static function getService(array $config): array
    {
        if(isset($config['dsn'])) {
            $driver = explode(":", $config['dsn'] ?? "", 2)[0];
            $queryGeneratorClass = match ($driver) {
                'pgsql' => generators\Postgres::class,
                default => throw new RepositoryException("Unsupported database driver: {$driver}")
            };
        } else {
            throw new RepositoryException("Please specify a driver for the repository backend.");
        }
        return [
            generators\Generator::class => $queryGeneratorClass,
            \PDO::class => function () use ($config) {
                return new \PDO($config['dsn'], $config['user'] ?? null, $config['password'] ?? null);
            }
        ];
    }
}
