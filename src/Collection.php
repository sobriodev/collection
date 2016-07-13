<?php
/**
 * Created by PhpStorm.
 * User: maki
 * Date: 12.07.16
 * Time: 20:28
 */

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

    public function get(string $path)
    {
        $this->expectValidPath($path);

        try {
            return $this->search(explode('.', $path));
        } catch (\OutOfBoundsException $e) {
            echo $e->getMessage();
            exit;
        }
    }

    private function &search(array $keys, bool $byReference = false)
    {
        $response = $byReference ? $response = &$this->elements : $this->elements;

        for ($i = 0, $lastIndex = count($keys) - 1; $i <= $lastIndex; $i++) {
            $key = $keys[$i];
            $keyExists = array_key_exists($key, $response);

            if (!$keyExists || ($keyExists && $i !== $lastIndex && !is_array($response[$key]))) {
                throw new \OutOfBoundsException(
                    sprintf('%s key doesn\'t exist', $keyExists ? $keys[$i + 1] : $key)
                );
            }
            $response = $byReference ? $response = &$response[$key] : $response[$key];
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
}