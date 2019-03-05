<?php

namespace AEngine\Entity\Interfaces;

use AEngine\Entity\Model;

interface CollectionInterface extends \ArrayAccess, \Countable, \Iterator, \JsonSerializable
{
    /**
     * Returns element that corresponds to the specified index
     *
     * @param int   $key
     * @param mixed $default
     *
     * @return mixed
     * @internal param int $index
     */
    public function get($key, $default = null);

    /**
     * Set value of the element
     *
     * @param int         $key
     * @param Model|array $value
     *
     * @return $this
     */
    public function set($key, $value);

    /**
     * Add item to collection, replacing existing items with the same data key
     *
     * @param array $items Key-value array of data to append to this collection
     *
     * @return $this
     */
    public function replace(array $items);

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all();

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function has($key);

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     *
     * @return $this
     */
    public function remove($key);

    /**
     * Remove all items from collection
     *
     * @return $this
     */
    public function clear();

    /**
     * Return collection as string
     *
     * @return string
     */
    public function __toString();
}
