<?php

namespace AEngine\Entity\Interfaces;

interface ModelInterface
{
    public function get($key);

    public function set($key, $value);

    public function replace(array $items);

    public function has($key);

    public function isEmpty();

    public function delete($key);

    public function clear();

    public function toArray();

    public function __toString();

    public function __get($key);

    public function __set($key, $value);
}
