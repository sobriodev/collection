<?php

namespace Collection;


class Cache
{
    private $storage = [];

    public function get(string $path)
    {
        if (!array_key_exists($path, $this->storage)) {
            throw new \OutOfBoundsException(
                sprintf('[%s] key doesn\'t exist', $path)
            );
        }

        return $this->storage[$path];
    }

    public function set(string $path, $value)
    {
        $this->storage[$path] = $value;
        return $this;
    }

    public function unset(string $path)
    {
        foreach ($this->storage as $key => $value) {
            if (preg_match($pattern = sprintf('/^%s(?:\.(?:%s)+)*$/', $path, Collection::KEY_PATTERN), $key)) {
                unset($this->storage[$key]);
            }
        }
        return $this;
    }
}