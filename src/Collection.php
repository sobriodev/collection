<?php

namespace Collection;


class Collection
{
    const KEY_PATTERN = '\p{L}|\d';

    const GET_REQUEST = 2;

    const HAS_REQUEST = 4;

    private $elements = [];

    private $cache;

    public function __construct(array $elements = [])
    {
        $this->elements = $elements;
        $this->cache = new Cache();
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

    public function set(string $path, $value, bool $strict = false)
    {
        $keys = explode('.', $path);
        $keysCopy = $keys;
        $elements =& $this->elements;
        $usedPath = [];

        for ($i = 0, $lastIndex = count($keys) - 1; $i <= $lastIndex; $i++) {
            $key = $keys[$i];

            if (!array_key_exists($key, $elements)) {
                if ($i !== $lastIndex) {
                    array_shift($keysCopy);
                    $elements[$key] = $this->createLevel($keysCopy, $value);
                    break;
                }
                $elements[$key] = $value;
            } else {
                if ($i === $lastIndex) {
                    $usedPath[] = $key;

                    if ($strict === true) {
                        $this->createStrictTypeException($usedPath);
                    }
                    $elements[$key] = $value;
                } else {
                    array_shift($keysCopy);

                    if (!is_array($elements[$key])) {
                        $usedPath[] = $key;

                        if ($strict === true) {
                            $this->createStrictTypeException($usedPath);
                        }
                        $elements[$key] = $this->createLevel($keysCopy, $value);
                        break;
                    }
                    $usedPath[] = $key;
                    $elements =& $elements[$key];
                }
            }
        }
        return $this;
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

    //TODO add cache obj
    private function getByRequest(string $path, int $request)
    {
        $response = $this->search(explode('.', $path));

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

    private function createLevel(array $keys, $value): array
    {
        foreach ($keys as $key) {
            $value = [$key => $value];
        }
        return $value;
    }

    private function expectValidPath(string $path)
    {
        if (!preg_match(sprintf('/^(?:%1$s)+(?:\.(?:%1$s)+)*$/', self::KEY_PATTERN), $path)) {
            throw new \InvalidArgumentException(
                sprintf('[%s] isn\'t valid path', $path)
            );
        }
    }

    private function createKeyNotFoundException(string $key)
    {
        throw new \OutOfBoundsException(
            sprintf('[%s] key doesn\'t exist', $key)
        );
    }

    private function createStrictTypeException(array $usedPath)
    {
        throw new \Exception(
            sprintf('[%s] path returned value (strict type enabled)', implode('.', $usedPath))
        );
    }
}