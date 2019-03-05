<?php

namespace AEngine\Entity;

use AEngine\Entity\Interfaces\CollectionInterface;
use AEngine\Entity\Support\Arr;
use AEngine\Entity\Traits\Macroable;
use ArrayIterator;
use Closure;
use Exception;
use InvalidArgumentException;
use JsonSerializable;
use stdClass;
use Traversable;

/**
 * @property-read CollectionHigherOrderProxy $average
 * @property-read CollectionHigherOrderProxy $avg
 * @property-read CollectionHigherOrderProxy $contains
 * @property-read CollectionHigherOrderProxy $each
 * @property-read CollectionHigherOrderProxy $every
 * @property-read CollectionHigherOrderProxy $filter
 * @property-read CollectionHigherOrderProxy $first
 * @property-read CollectionHigherOrderProxy $flatMap
 * @property-read CollectionHigherOrderProxy $groupBy
 * @property-read CollectionHigherOrderProxy $keyBy
 * @property-read CollectionHigherOrderProxy $map
 * @property-read CollectionHigherOrderProxy $max
 * @property-read CollectionHigherOrderProxy $min
 * @property-read CollectionHigherOrderProxy $partition
 * @property-read CollectionHigherOrderProxy $reject
 * @property-read CollectionHigherOrderProxy $sortBy
 * @property-read CollectionHigherOrderProxy $sortByDesc
 * @property-read CollectionHigherOrderProxy $sum
 * @property-read CollectionHigherOrderProxy $unique
 *
 * Class Collection
 */
class Collection implements CollectionInterface
{
    use Macroable;

    /**
     * Internal storage of models
     *
     * @var array
     */
    protected $items = [];
    /**
     * Iterator position
     *
     * @var int
     */
    protected $position = 0;

    /**
     * The methods that can be proxied.
     *
     * @var array
     */
    protected static $proxies = [
        'average', 'avg', 'contains', 'each', 'every', 'filter', 'first',
        'flatMap', 'groupBy', 'keyBy', 'map', 'max', 'min', 'partition',
        'reject', 'sortBy', 'sortByDesc', 'sum', 'unique',
    ];

    /**
     * Create a new collection.
     *
     * @param mixed $items
     *
     * @return void
     */
    final public function __construct($items = [])
    {
        $this->replace($this->getArrayByItems($items));
    }

    /**
     * Get all items in collection
     *
     * @return array The collection's source data
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Add item to collection, replacing existing items with the same data key
     *
     * @param array $items Key-value array of data to append to this collection
     *
     * @return $this
     */
    public function replace(array $items)
    {
        foreach ($items as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Set value of the element
     *
     * @param int   $key
     * @param mixed $value
     *
     * @return $this
     */
    public function set($key, $value)
    {
        return $this->offsetSet($key, $value);
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     *
     * @return $this
     */
    public function remove($key)
    {
        return $this->offsetUnset($key);
    }

    /**
     * Remove all items from collection
     *
     * @return $this
     */
    public function clear()
    {
        $this->items = [];

        return $this;
    }

    /**
     * Create a new collection instance if the value isn't one already.
     *
     * @param mixed $items
     *
     * @return self
     */
    public static function make($items = [])
    {
        return new self($items);
    }

    /**
     * Wrap the given value in a collection if applicable.
     *
     * @param mixed $value
     *
     * @return self
     */
    public static function wrap($value)
    {
        if ($value instanceof self) {
            return new self($value);
        }

        return new self(Arr::wrap($value));
    }

    /**
     * Get the underlying items from the given collection if applicable.
     *
     * @param array|static $value
     *
     * @return array
     */
    public static function unwrap($value)
    {
        if ($value instanceof self) {
            return $value->all();
        }

        return $value;
    }

    /**
     * Create a new collection by invoking the callback a given amount of times.
     *
     * @param int      $number
     * @param callable $callback
     *
     * @return self
     */
    public static function times($number, callable $callback = null)
    {
        if ($number < 1) {
            return new self;
        }
        if (is_null($callback)) {
            return new self(range(1, $number));
        }

        return (new self(range(1, $number)))->map($callback);
    }

    /**
     * Run a map over each of the items.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function map(callable $callback)
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);

        return new self(array_combine($keys, $items));
    }

    /**
     * Add a method to the list of proxied methods.
     *
     * @param string $method
     *
     * @return void
     */
    public static function proxy($method)
    {
        static::$proxies[] = $method;
    }

    /**
     * Get the median of a given key.
     *
     * @param string|array|null $key
     *
     * @return mixed
     */
    public function median($key = null)
    {
        $values = (isset($key) ? $this->pluck($key) : $this)
            ->filter(function ($item) {
                return !is_null($item);
            })
            ->sort()
            ->values();

        $count = $values->count();
        if ($count == 0) {
            return null;
        }

        $middle = (int)($count / 2);

        if ($count % 2) {
            return $values->get($middle);
        }

        return (
        new self([
            $values->get($middle - 1),
            $values->get($middle),
        ])
        )->avg();
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return self
     */
    public function values()
    {
        return new self(array_values($this->items));
    }

    /**
     * Sort through each item with a callback.
     *
     * @param callable|null $callback
     *
     * @return self
     */
    public function sort($callback = null)
    {
        $items = $this->items;
        $callback ? uasort($items, $callback) : asort($items);

        return new self($items);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     *
     * @return self
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            return new self(Arr::where($this->items, $callback));
        }

        return new self(array_filter($this->items));
    }

    /**
     * Get the values of a given key.
     *
     * @param string|array $value
     * @param string|null  $key
     *
     * @return self
     */
    public function pluck($value, $key = null)
    {
        return new self(Arr::pluck($this->items, $value, $key));
    }

    /**
     * Get an item from the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->offsetGet($key);
        }

        return value($default);
    }

    /**
     * Get the average value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function avg($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        $items = $this->map(
            function ($value) use ($callback) {
                return $callback($value);
            }
        )->filter(
            function ($value) {
                return !is_null($value);
            }
        );

        if ($count = $items->count()) {
            return $items->sum() / $count;
        }

        return null;
    }

    /**
     * Get a value retrieving callback.
     *
     * @param string $value
     *
     * @return callable
     */
    protected function valueRetriever($value)
    {
        if ($this->useAsCallable($value)) {
            return $value;
        }

        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Determine if the given value is callable, but not a string.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected function useAsCallable($value)
    {
        return !is_string($value) && is_callable($value);
    }

    /**
     * Get the sum of the given values.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function sum($callback = null)
    {
        if (is_null($callback)) {
            return array_sum($this->items);
        }
        $callback = $this->valueRetriever($callback);

        return $this->reduce(function ($result, $item) use ($callback) {
            return $result + $callback($item);
        }, 0);
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Get the mode of a given key.
     *
     * @param string|array|null $key
     *
     * @return array|null
     */
    public function mode($key = null)
    {
        if ($this->count() === 0) {
            return null;
        }
        $collection = isset($key) ? $this->pluck($key) : $this;
        $counts = new self;
        $collection->each(function ($value) use ($counts) {
            $counts[$value] = isset($counts[$value]) ? $counts[$value] + 1 : 1;
        });
        $sorted = $counts->sort();
        $highestValue = $sorted->last();

        return $sorted->filter(
            function ($value) use ($highestValue) {
                return $value == $highestValue;
            }
        )->sort()->keys()->all();
    }

    /**
     * Execute a callback over each item.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key) === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * Get the last item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function last(callable $callback = null, $default = null)
    {
        return Arr::last($this->items, $callback, $default);
    }

    /**
     * Get the keys of the collection items.
     *
     * @return self
     */
    public function keys()
    {
        return new self(array_keys($this->items));
    }

    /**
     * Determine if an item exists in the collection using strict comparison.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return bool
     */
    public function containsStrict($key, $value = null)
    {
        if (func_num_args() === 2) {
            return $this->contains(function ($item) use ($key, $value) {
                return data_get($item, $key) === $value;
            });
        }

        if ($this->useAsCallable($key)) {
            return !is_null($this->first($key));
        }

        return in_array($key, $this->items, true);
    }

    /**
     * Determine if an item exists in the collection.
     *
     * @param mixed $key
     * @param mixed $operator
     * @param mixed $value
     *
     * @return bool
     */
    public function contains($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            if ($this->useAsCallable($key)) {
                $placeholder = new stdClass;

                return $this->first($key, $placeholder) !== $placeholder;
            }

            return in_array($key, $this->items);
        }

        return $this->contains($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get the first item from the collection.
     *
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        return Arr::first($this->items, $callback, $default);
    }

    /**
     * Get an operator checker callback.
     *
     * @param string $key
     * @param string $operator
     * @param mixed  $value
     *
     * @return Closure
     */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = data_get($item, $key);
            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':
                    return $retrieved == $value;
                case '!=':
                case '<>':
                    return $retrieved != $value;
                case '<':
                    return $retrieved < $value;
                case '>':
                    return $retrieved > $value;
                case '<=':
                    return $retrieved <= $value;
                case '>=':
                    return $retrieved >= $value;
                case '===':
                    return $retrieved === $value;
                case '!==':
                    return $retrieved !== $value;
            }
        };
    }

    /**
     * Cross join with the given lists, returning all possible permutations.
     *
     * @param mixed ...$lists
     *
     * @return self
     */
    public function crossJoin(...$lists)
    {
        return new self(Arr::crossJoin(
            $this->items, ...array_map([$this, 'getArrayByItems'], $lists)
        ));
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function diff($items)
    {
        return new self(array_diff($this->items, $this->getArrayByItems($items)));
    }

    /**
     * Get the items in the collection that are not present in the given items.
     *
     * @param mixed    $items
     * @param callable $callback
     *
     * @return self
     */
    public function diffUsing($items, callable $callback)
    {
        return new self(array_udiff($this->items, $this->getArrayByItems($items), $callback));
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function diffAssoc($items)
    {
        return new self(array_diff_assoc($this->items, $this->getArrayByItems($items)));
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     *
     * @param mixed    $items
     * @param callable $callback
     *
     * @return self
     */
    public function diffAssocUsing($items, callable $callback)
    {
        return new self(array_diff_uassoc($this->items, $this->getArrayByItems($items), $callback));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function diffKeys($items)
    {
        return new self(array_diff_key($this->items, $this->getArrayByItems($items)));
    }

    /**
     * Get the items in the collection whose keys are not present in the given items.
     *
     * @param mixed    $items
     * @param callable $callback
     *
     * @return self
     */
    public function diffKeysUsing($items, callable $callback)
    {
        return new self(array_diff_ukey($this->items, $this->getArrayByItems($items), $callback));
    }

    /**
     * Execute a callback over each nested chunk of items.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function eachSpread(callable $callback)
    {
        return $this->each(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Determine if all items in the collection pass the given test.
     *
     * @param string|callable $key
     * @param mixed           $operator
     * @param mixed           $value
     *
     * @return bool
     */
    public function every($key, $operator = null, $value = null)
    {
        if (func_num_args() === 1) {
            $callback = $this->valueRetriever($key);

            foreach ($this->items as $k => $v) {
                if (!$callback($v, $k)) {
                    return false;
                }
            }

            return true;
        }

        return $this->every($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get all items except for those with the specified keys.
     *
     * @param Collection|mixed $keys
     *
     * @return self
     */
    public function except($keys)
    {
        if ($keys instanceof self) {
            $keys = $keys->all();
        } else if (!is_array($keys)) {
            $keys = func_get_args();
        }

        return new self(Arr::except($this->items, $keys));
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return self
     */
    public function whereStrict($key, $value)
    {
        return $this->where($key, '===', $value);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return self
     */
    public function where($key, $operator = null, $value = null)
    {
        return $this->filter($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $values
     *
     * @return self
     */
    public function whereInStrict($key, $values)
    {
        return $this->whereIn($key, $values, true);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return self
     */
    public function whereIn($key, $values, $strict = false)
    {
        $values = $this->getArrayByItems($values);

        return $this->filter(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Filter items by the given key value pair using strict comparison.
     *
     * @param string $key
     * @param mixed  $values
     *
     * @return self
     */
    public function whereNotInStrict($key, $values)
    {
        return $this->whereNotIn($key, $values, true);
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $values
     * @param bool   $strict
     *
     * @return self
     */
    public function whereNotIn($key, $values, $strict = false)
    {
        $values = $this->getArrayByItems($values);

        return $this->reject(function ($item) use ($key, $values, $strict) {
            return in_array(data_get($item, $key), $values, $strict);
        });
    }

    /**
     * Create a collection of all elements that do not pass a given truth test.
     *
     * @param callable|mixed $callback
     *
     * @return self
     */
    public function reject($callback)
    {
        if ($this->useAsCallable($callback)) {
            return $this->filter(function ($value, $key) use ($callback) {
                return !$callback($value, $key);
            });
        }

        return $this->filter(function ($item) use ($callback) {
            return $item != $callback;
        });
    }

    /**
     * Filter the items, removing any items that don't match the given type.
     *
     * @param string $type
     *
     * @return self
     */
    public function whereInstanceOf($type)
    {
        return $this->filter(function ($value) use ($type) {
            return $value instanceof $type;
        });
    }

    /**
     * Apply the callback if the value is falsy.
     *
     * @param bool     $value
     * @param callable $callback
     * @param callable $default
     *
     * @return self|mixed
     */
    public function unless($value, callable $callback, callable $default = null)
    {
        return $this->when(!$value, $callback, $default);
    }

    /**
     * Apply the callback if the value is truthy.
     *
     * @param bool     $value
     * @param callable $callback
     * @param callable $default
     *
     * @return self|mixed
     */
    public function when($value, callable $callback, callable $default = null)
    {
        if ($value) {
            return $callback($this, $value);
        } else if ($default) {
            return $default($this, $value);
        }

        return $this;
    }

    /**
     * Get the first item by the given key value pair.
     *
     * @param string $key
     * @param mixed  $operator
     * @param mixed  $value
     *
     * @return mixed
     */
    public function firstWhere($key, $operator, $value = null)
    {
        return $this->first($this->operatorForWhere(...func_get_args()));
    }

    /**
     * Get a flattened array of the items in the collection.
     *
     * @param int $depth
     *
     * @return self
     */
    public function flatten($depth = INF)
    {
        return new self(Arr::flatten($this->items, $depth));
    }

    /**
     * Flip the items in the collection.
     *
     * @return self
     */
    public function flip()
    {
        return new self(array_flip($this->items));
    }

    /**
     * Remove an item from the collection by key.
     *
     * @param string|array $keys
     *
     * @return $this
     */
    public function forget($keys)
    {
        foreach ((array)$keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Group an associative array by a field or using a callback.
     *
     * @param callable|string $groupBy
     * @param bool            $preserveKeys
     *
     * @return self
     */
    public function groupBy($groupBy, $preserveKeys = false)
    {
        if (is_array($groupBy)) {
            $nextGroups = $groupBy;
            $groupBy = array_shift($nextGroups);
        }
        $groupBy = $this->valueRetriever($groupBy);
        $results = [];
        foreach ($this->items as $key => $value) {
            $groupKeys = $groupBy($value, $key);
            if (!is_array($groupKeys)) {
                $groupKeys = [$groupKeys];
            }
            foreach ($groupKeys as $groupKey) {
                $groupKey = is_bool($groupKey) ? (int)$groupKey : $groupKey;
                if (!array_key_exists($groupKey, $results)) {
                    $results[$groupKey] = new self;
                }
                $results[$groupKey]->offsetSet($preserveKeys ? $key : null, $value);
            }
        }
        $result = new self($results);

        if (!empty($nextGroups)) {
            return $result->map->groupBy($nextGroups, $preserveKeys);
        }

        return $result;
    }

    /**
     * Key an associative array by a field or using a callback.
     *
     * @param callable|string $keyBy
     *
     * @return self
     */
    public function keyBy($keyBy)
    {
        $keyBy = $this->valueRetriever($keyBy);
        $results = [];

        foreach ($this->items as $key => $item) {
            $resolvedKey = $keyBy($item, $key);
            if (is_object($resolvedKey)) {
                $resolvedKey = (string)$resolvedKey;
            }
            $results[$resolvedKey] = $item;
        }

        return new self($results);
    }

    /**
     * Determine if an item exists in the collection by key.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function has($key)
    {
        $keys = is_array($key) ? $key : func_get_args();
        foreach ($keys as $value) {
            if (!$this->offsetExists($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Concatenate values of a given key as a string.
     *
     * @param string $value
     * @param string $glue
     *
     * @return string
     */
    public function implode($value, $glue = null)
    {
        $first = $this->first();
        if (is_array($first) || is_object($first)) {
            return implode($glue, $this->pluck($value)->all());
        }

        return implode($value, $this->items);
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function intersect($items)
    {
        return new self(array_intersect($this->items, $this->getArrayByItems($items)));
    }

    /**
     * Intersect the collection with the given items by key.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function intersectByKeys($items)
    {
        return new self(array_intersect_key(
            $this->items, $this->getArrayByItems($items)
        ));
    }

    /**
     * Determine if the collection is not empty.
     *
     * @return bool
     */
    public function isNotEmpty()
    {
        return !$this->isEmpty();
    }

    /**
     * Determine if the collection is empty or not.
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->items);
    }

    /**
     * Run a map over each nested chunk of items.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function mapSpread(callable $callback)
    {
        return $this->map(function ($chunk, $key) use ($callback) {
            $chunk[] = $key;

            return $callback(...$chunk);
        });
    }

    /**
     * Run a grouping map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function mapToGroups(callable $callback)
    {
        $groups = $this->mapToDictionary($callback);

        return $groups->map([$this, 'make']);
    }

    /**
     * Run a dictionary map over the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function mapToDictionary(callable $callback)
    {
        $dictionary = [];
        foreach ($this->items as $key => $item) {
            $pair = $callback($item, $key);
            $key = key($pair);
            $value = reset($pair);
            if (!isset($dictionary[$key])) {
                $dictionary[$key] = [];
            }
            $dictionary[$key][] = $value;
        }

        return new self($dictionary);
    }

    /**
     * Run an associative map over each of the items.
     *
     * The callback should return an associative array with a single key/value pair.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];
        foreach ($this->items as $key => $value) {
            $assoc = $callback($value, $key);
            foreach ($assoc as $mapKey => $mapValue) {
                $result[$mapKey] = $mapValue;
            }
        }

        return new self($result);
    }

    /**
     * Map a collection and flatten the result by a single level.
     *
     * @param callable $callback
     *
     * @return self
     */
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * Collapse the collection of items into a single array.
     *
     * @return self
     */
    public function collapse()
    {
        return new self(Arr::collapse($this->items));
    }

    /**
     * Map the values into a new class.
     *
     * @param string $class
     *
     * @return self
     */
    public function mapInto($class)
    {
        return $this->map(function ($value, $key) use ($class) {
            return new $class($value, $key);
        });
    }

    /**
     * Get the max value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function max($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $item) use ($callback) {
            $value = $callback($item);

            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * Get the min value of a given key.
     *
     * @param callable|string|null $callback
     *
     * @return mixed
     */
    public function min($callback = null)
    {
        $callback = $this->valueRetriever($callback);

        return $this->map(function ($value) use ($callback) {
            return $callback($value);
        })->filter(function ($value) {
            return !is_null($value);
        })->reduce(function ($result, $value) {
            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * Create a new collection consisting of every n-th element.
     *
     * @param int $step
     * @param int $offset
     *
     * @return self
     */
    public function nth($step, $offset = 0)
    {
        $new = [];
        $position = 0;
        foreach ($this->items as $item) {
            if ($position % $step === $offset) {
                $new[] = $item;
            }
            $position++;
        }

        return new self($new);
    }

    /**
     * Merge the collection with the given items.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function merge($items)
    {
        return new self(array_merge($this->items, $this->getArrayByItems($items)));
    }

    /**
     * Create a collection by using this collection for keys and another for its values.
     *
     * @param mixed $values
     *
     * @return self
     */
    public function combine($values)
    {
        return new self(array_combine($this->all(), $this->getArrayByItems($values)));
    }

    /**
     * Union the collection with the given items.
     *
     * @param mixed $items
     *
     * @return self
     */
    public function union($items)
    {
        return new self($this->items + $this->getArrayByItems($items));
    }

    /**
     * Get the items with the specified keys.
     *
     * @param mixed $keys
     *
     * @return self
     */
    public function only($keys)
    {
        if (is_null($keys)) {
            return new self($this->items);
        }
        if ($keys instanceof self) {
            $keys = $keys->all();
        }
        $keys = is_array($keys) ? $keys : func_get_args();

        return new self(Arr::only($this->items, $keys));
    }

    /**
     * "Paginate" the collection by slicing it into a smaller collection.
     *
     * @param int $page
     * @param int $perPage
     *
     * @return self
     */
    public function forPage($page, $perPage)
    {
        $offset = max(0, ($page - 1) * $perPage);

        return $this->slice($offset, $perPage);
    }

    /**
     * Slice the underlying collection array.
     *
     * @param int $offset
     * @param int $length
     *
     * @return self
     */
    public function slice($offset, $length = null)
    {
        return new self(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Partition the collection into two arrays using the given callback or key.
     *
     * @param callable|string $key
     * @param mixed           $operator
     * @param mixed           $value
     *
     * @return self
     */
    public function partition($key, $operator = null, $value = null)
    {
        $partitions = [new self, new self];
        $callback = func_num_args() === 1
            ? $this->valueRetriever($key)
            : $this->operatorForWhere(...func_get_args());
        foreach ($this->items as $key => $item) {
            $partitions[(int)!$callback($item, $key)][$key] = $item;
        }

        return new self($partitions);
    }

    /**
     * Pass the collection to the given callback and return the result.
     *
     * @param callable $callback
     *
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param mixed $value
     * @param mixed $key
     *
     * @return $this
     */
    public function prepend($value, $key = null)
    {
        $this->items = Arr::prepend($this->items, $value, $key);

        return $this;
    }

    /**
     * Push all of the given items onto the collection.
     *
     * @param Traversable|array $source
     *
     * @return self
     */
    public function concat($source)
    {
        $result = new self($this);

        foreach ($source as $item) {
            $result->push($item);
        }

        return $result;
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function push($value)
    {
        $this->offsetSet(null, $value);

        return $this;
    }

    /**
     * Get and remove an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return Arr::pull($this->items, $key, $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    public function put($key, $value)
    {
        $this->offsetSet($key, $value);

        return $this;
    }

    /**
     * Get one or a specified number of items randomly from the collection.
     *
     * @param int|null $number
     *
     * @return self|mixed
     *
     * @throws InvalidArgumentException
     */
    public function random($number = null)
    {
        if (is_null($number)) {
            return Arr::random($this->items);
        }

        return new self(Arr::random($this->items, $number));
    }

    /**
     * Reverse items order.
     *
     * @return self
     */
    public function reverse()
    {
        return new self(array_reverse($this->items, true));
    }

    /**
     * Search the collection for a given value and return the corresponding key if successful.
     *
     * @param mixed $value
     * @param bool  $strict
     *
     * @return mixed
     */
    public function search($value, $strict = false)
    {
        if (!$this->useAsCallable($value)) {
            return array_search($value, $this->items, $strict);
        }
        foreach ($this->items as $key => $item) {
            if (call_user_func($value, $item, $key)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Shuffle the items in the collection.
     *
     * @param int $seed
     *
     * @return self
     */
    public function shuffle($seed = null)
    {
        return new self(Arr::shuffle($this->items, $seed));
    }

    /**
     * Split a collection into a certain number of groups.
     *
     * @param int $numberOfGroups
     *
     * @return self
     */
    public function split($numberOfGroups)
    {
        if ($this->isEmpty()) {
            return new self;
        }
        $groups = new self;
        $groupSize = floor($this->count() / $numberOfGroups);
        $remain = $this->count() % $numberOfGroups;
        $start = 0;
        for ($i = 0; $i < $numberOfGroups; $i++) {
            $size = $groupSize;
            if ($i < $remain) {
                $size++;
            }
            if ($size) {
                $groups->push(new self(array_slice($this->items, $start, $size)));
                $start += $size;
            }
        }

        return $groups;
    }

    /**
     * Chunk the underlying collection array.
     *
     * @param int $size
     *
     * @return self
     */
    public function chunk($size)
    {
        if ($size <= 0) {
            return new self;
        }
        $chunks = [];
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * Sort the collection in descending order using the given callback.
     *
     * @param callable|string $callback
     * @param int             $options
     *
     * @return self
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * Sort the collection using the given callback.
     *
     * @param callable|string $callback
     * @param int             $options
     * @param bool            $descending
     *
     * @return self
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];
        $callback = $this->valueRetriever($callback);

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value, $key);
        }
        $descending ? arsort($results, $options) : asort($results, $options);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        return new self($results);
    }

    /**
     * Sort the collection keys in descending order.
     *
     * @param int $options
     *
     * @return self
     */
    public function sortKeysDesc($options = SORT_REGULAR)
    {
        return $this->sortKeys($options, true);
    }

    /**
     * Sort the collection keys.
     *
     * @param int  $options
     * @param bool $descending
     *
     * @return self
     */
    public function sortKeys($options = SORT_REGULAR, $descending = false)
    {
        $items = $this->items;
        $descending ? krsort($items, $options) : ksort($items, $options);

        return new self($items);
    }

    /**
     * Splice a portion of the underlying collection array.
     *
     * @param int      $offset
     * @param int|null $length
     * @param mixed    $replacement
     *
     * @return self
     */
    public function splice($offset, $length = null, $replacement = [])
    {
        if (func_num_args() === 1) {
            return new self(array_splice($this->items, $offset));
        }

        return new self(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     *
     * @return self
     */
    public function take($limit)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Pass the collection to the given callback and then return it.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function tap(callable $callback)
    {
        $callback(new self($this->items));

        return $this;
    }

    /**
     * Transform each item in the collection using a callback.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function transform(callable $callback)
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * Return only unique items from the collection array using strict comparison.
     *
     * @param string|callable|null $key
     *
     * @return self
     */
    public function uniqueStrict($key = null)
    {
        return $this->unique($key, true);
    }

    /**
     * Return only unique items from the collection array.
     *
     * @param string|callable|null $key
     * @param bool                 $strict
     *
     * @return self
     */
    public function unique($key = null, $strict = false)
    {
        $callback = $this->valueRetriever($key);
        $exists = [];

        return $this->reject(function ($item, $key) use ($callback, $strict, &$exists) {
            if (in_array($id = $callback($item, $key), $exists, $strict)) {
                return true;
            }
            $exists[] = $id;
        });
    }

    /**
     * Get the collection of items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_map(
            function ($value) {
                return $value instanceof Collection ? $value->toArray() : $value;
            },
            $this->items
        );
    }

    /**
     * Zip the collection together with one or more arrays.
     *
     * e.g. new Collection([1, 2, 3])->zip([4, 5, 6]);
     *      => [[1, 4], [2, 5], [3, 6]]
     *
     * @param mixed ...$items
     *
     * @return self
     */
    public function zip($items)
    {
        $arrayableItems = array_map(
            function ($items) {
                return $this->getArrayByItems($items);
            },
            func_get_args()
        );
        $params = array_merge(
            [
                function () {
                    return new self(func_get_args());
                },
                $this->items,
            ],
            $arrayableItems
        );

        return new self(call_user_func_array('array_map', $params));
    }

    /**
     * Pad collection to the specified length with a value.
     *
     * @param int   $size
     * @param mixed $value
     *
     * @return self
     */
    public function pad($size, $value)
    {
        return new self(array_pad($this->items, $size, $value));
    }

    /**
     * Returns current element of the array
     *
     * @return mixed
     */
    public function current()
    {
        return $this->offsetGet($this->key());
    }

    /**
     * Returns current element key
     *
     * @return int
     */
    public function key()
    {
        $bufKeys = array_keys($this->items);

        if ($bufKeys && isset($bufKeys[$this->position])) {
            return $bufKeys[$this->position];
        }

        return false;
    }

    /**
     * Move forward to next element
     *
     * @return $this
     */
    public function next()
    {
        $this->position++;

        return $this;
    }

    /**
     * Move forward to previously element
     *
     * @return $this
     */
    public function prev()
    {
        $this->position--;

        return $this;
    }

    /**
     * Check current position of the iterator
     *
     * @return bool
     */
    public function valid()
    {
        return $this->key() !== false && $this->offsetExists($this->key());
    }

    /**
     * Set iterator to the first element
     *
     * @return $this
     */
    public function rewind()
    {
        $this->position = 0;

        return $this;
    }

    /**
     * Returns number of elements of the object
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Get collection iterator
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Does this collection have a given key?
     *
     * @param string $key The data key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Get collection item for key
     *
     * @param string $key The data key
     *
     * @return mixed The key's value, or the default value
     */
    public function offsetGet($key)
    {
        return $this->items[$key];
    }

    /**
     * Set collection item
     *
     * @param string $key   The data key
     * @param mixed  $value The data value
     *
     * @return $this
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }

        return $this;
    }

    /**
     * Remove item from collection
     *
     * @param string $key The data key
     *
     * @return $this
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);

        return $this;
    }

    /**
     * Results array of items from Collection or Array.
     *
     * @param mixed $items
     *
     * @return array
     */
    protected function getArrayByItems($items)
    {
        switch (true) {
            case is_array($items):
                return $items;

            case $items instanceof self:
                return $items->all();

            case $items instanceof JsonSerializable:
                return $items->jsonSerialize();

            case $items instanceof Traversable:
                return iterator_to_array($items);
        }

        return (array)$items;
    }

    /**
     * Get the collection of items as JSON.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = JSON_UNESCAPED_UNICODE)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_map(function ($value) {
            if ($value instanceof JsonSerializable) {
                return $value->jsonSerialize();
            }

            return $value;
        }, $this->items);
    }

    /**
     * Dynamically access collection proxies.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function __get($key)
    {
        if (!in_array($key, static::$proxies)) {
            throw new Exception("Property [{$key}] does not exist on this collection instance.");
        }

        return new CollectionHigherOrderProxy($this, $key);
    }

    /**
     * Convert the collection to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}
