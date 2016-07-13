<?php

namespace Collection;


class Collection
{
    private $elements;

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
            return $this->search(explode('.', $path));
        } catch (\OutOfBoundsException $e) {
            if (is_null($placeholder)) {
                throw $e;
            }
            return $placeholder;
        }
    }

    public function has(string $path)
    {
        $this->expectValidPath($path);
        
        try {
            $this->search(explode('.', $path));
            return true;
        } catch (\OutOfBoundsException $e) {
            return false;
        }
    }

    public function unset(string $path)
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

    private function expectValidPath(string $path)
    {
        if (!preg_match('/^(?:\p{L}|\d)+(?:\.(?:\p{L}|\d)+)*$/', $path)) {
            throw new \InvalidArgumentException(
                sprintf('%s isn\'t valid path', $path)
            );
        }
    }

    private function createKeyNotFoundException($key)
    {
        throw new \OutOfBoundsException(
            sprintf('%s key doesn\'t exist', $key)
        );
    }
}