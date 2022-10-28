<?php

declare(strict_types=1);

namespace Tests;

use Psr\SimpleCache\CacheInterface;

class DevNullCache implements CacheInterface
{
    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return null;
    }

    public function set($key, $value, $ttl = null): bool
    {
        return false;
    }

    public function delete($key): bool
    {
        return false;
    }

    public function clear(): bool
    {
        return false;
    }

    /**
     * @param array<scalar> $keys
     * @param $default
     * @return iterable<object>
     */
    public function getMultiple($keys, $default = null): iterable
    {
        return [];
    }

    /**
     * @param array<object> $values
     * @param $ttl
     * @return bool
     */
    public function setMultiple($values, $ttl = null): bool
    {
        return false;
    }

    /**
     * @param array<scalar> $keys
     * @return bool
     */
    public function deleteMultiple($keys): bool
    {
        return false;
    }

    public function has($key): bool
    {
        return false;
    }
}
