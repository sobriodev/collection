<?php

namespace Collection;


class Collection
{
    const KEY_PATTERN = '\p{L}|\d';

    const GET_REQUEST = 2;

    const HAS_REQUEST = 4;

    private $elements = [];

    private $cache = [];

    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function get(string $path, $placeholder = null)
    {
        $this->expectValidPath($path);

        try {
            return $this->getByRequest($path, self::GET_REQUEST);
        } catch (\OutOfBoundsException $e) {
            if (is_null($placeholder)) {
                throw $e;
            }
            return $placeholder;
        }
    }

    public function has(string $path): bool
    {
        $this->expectValidPath($path);

        try {
            return $this->getByRequest($path, self::HAS_REQUEST);
        } catch (\OutOfBoundsException $e) {
            return false;
        }
    }

    public function set(string $path, bool $strict = false)
    {

    }

    public function unset(string $path): Collection
    {
        $this->expectValidPath($path);
        $keys = explode('.', $path);

        if (count($keys) == 1) {
            $key = $keys[0];

            if (!array_key_exists($key, $this->elements)) {
                $this->createKeyNotFoundException($key);
            }
            unset($this->elements[$key]);
        } else {
            $lastKey = array_pop($keys);
            $response =& $this->search($keys, true);

            if (!is_array($response)) {
                $this->createKeyNotFoundException($lastKey);
            }
            unset($response[$lastKey]);
        }
        return $this;
    }

    private function &search(array $keys, bool $byReference = false)
    {
        $response = $byReference ? $response =& $this->elements : $this->elements;

        for ($i = 0, $lastIndex = count($keys) - 1; $i <= $lastIndex; $i++) {
            $key = $keys[$i];
            $keyExists = array_key_exists($key, $response);

            if (!$keyExists || ($keyExists && $i !== $lastIndex && !is_array($response[$key]))) {
                $this->createKeyNotFoundException($keyExists ? $keys[$i + 1] : $key);
            }
            $response = $byReference ? $response =& $response[$key] : $response[$key];
        }
        return $response;
    }

    //TODO add cache obj
    private function getByRequest(string $path, int $request)
    {
        if (array_key_exists($path, $this->cache)) {
            $response = $this->cache[$path];
        } else {
            $response = $this->search(explode('.', $path));
            $this->cache[$path] = $response;
        }

        switch ($request) {
            case self::GET_REQUEST:
                return $response;
            case self::HAS_REQUEST:
                return true;
            default:
                throw new \InvalidArgumentException(
                    sprintf('Unknown request method [%s]', $request)
                );
        }
    }

    private function getUsedPath(array $keys, int $index): array
    {
        $response = [];

        for ($i = 0; $i <= $index; $i++) {
            $response[] = $keys[$i];
        }
        return $response;
    }

    private function expectValidPath(string $path)
    {
        if (!preg_match(sprintf('/^(?:%1$s)+(?:\.(?:%1$s)+)*$/', self::KEY_PATTERN), $path)) {
            throw new \InvalidArgumentException(
                sprintf('[%s] isn\'t valid path', $path)
            );
        }
    }

    private function createKeyNotFoundException($key)
    {
        throw new \OutOfBoundsException(
            sprintf('[%s] key doesn\'t exist', $key)
        );
    }
}