<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

/**
 * Contenedor de dependencias mínimo: singletons, enlaces y resolución automática por constructor.
 * Complementa el contenedor de Laravel para servicios de la capa limpia (repositorios / servicios API).
 */
final class Container
{
    /** @var array<string, object> */
    private static array $singletons = [];

    /** @var array<string, Closure|class-string> */
    private static array $bindings = [];

    /**
     * Registra un singleton; la fábrica se ejecuta la primera vez que se resuelve.
     *
     * @param  Closure(object):object  $factory
     */
    public static function singleton(string $abstract, Closure $factory): void
    {
        self::$bindings[$abstract] = $factory;
        self::$singletons[$abstract] = null;
    }

    /**
     * Enlaza una implementación concreta a una abstracción.
     *
     * @param  class-string  $concrete
     */
    public static function bind(string $abstract, string $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    /**
     * Resuelve una clase o interfaz registrada, o instancia por reflexión.
     *
     * @template T of object
     *
     * @param  class-string<T>  $abstract
     * @return T
     */
    public static function make(string $abstract): object
    {
        if (array_key_exists($abstract, self::$singletons)) {
            if (self::$singletons[$abstract] === null) {
                $factory = self::$bindings[$abstract] ?? null;
                if (! $factory instanceof Closure) {
                    throw new InvalidArgumentException("Singleton {$abstract} requiere un Closure de fábrica.");
                }
                // Las fábricas no reciben argumentos para mantener la API simple.
                self::$singletons[$abstract] = $factory();
            }

            /** @var T */
            return self::$singletons[$abstract];
        }

        if (isset(self::$bindings[$abstract])) {
            $target = self::$bindings[$abstract];
            if (is_string($target) && class_exists($target)) {
                /** @var T */
                return self::build($target);
            }
        }

        if (class_exists($abstract)) {
            /** @var T */
            return self::build($abstract);
        }

        throw new InvalidArgumentException("No se puede resolver: {$abstract}");
    }

    public static function getInstance(): self
    {
        static $instance;
        $instance ??= new self;

        return $instance;
    }

    /**
     * @param  class-string  $class
     */
    private static function build(string $class): object
    {
        try {
            $ref = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException($e->getMessage(), 0, $e);
        }

        $constructor = $ref->getConstructor();
        if ($constructor === null) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $dep = $type->getName();
                // PDO no debe resolverse por reflexión (requiere DSN).
                if ($dep === 'PDO' || $dep === \PDO::class) {
                    $args[] = Database::connection();

                    continue;
                }
                $args[] = self::make($dep);

                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();

                continue;
            }
            throw new InvalidArgumentException("No se puede resolver el parámetro \${$param->getName()} de {$class}");
        }

        return $ref->newInstanceArgs($args);
    }
}
