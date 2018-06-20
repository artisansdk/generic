<?php

namespace ArtisanSDK\Generic\Types\Templates;

use ArtisanSDK\Generic\Contract;
use ArtisanSDK\Generic\Types\HashMap as Type;
use InvalidArgumentException;

class HashMap implements Contract
{
    /**
     * Untyped hash map.
     *
     * @var array
     */
    protected $map = [];

    /**
     * Make a new generic based on class.
     *
     * @param mixed $key type for generic hash map
     * @param mixed $value type for generic hash map
     *
     * @return \ArtisanSDK\Generic\Contract
     */
    public static function generic() : Contract
    {
        $args = func_get_args();

        return new Type(...$args);
    }

    /**
     * Get the untyped map.
     *
     * @return array
     */
    public function all() : array
    {
        return $this->map;
    }

    /**
     * Get untyped hash map value by key.
     *
     * @param mixed $key
     *
     * @throws \InvalidArgumentException when key is not set in map.
     *
     * @return mixed|null
     */
    public function get($key)
    {
        if( isset($this->map[$key]) ) {
            return $this->map[$key];
        }

        throw new InvalidArgumentException(sprintf('The key %s is not set in the map.', (string) $key));
    }

    /**
     * Set untyped hash map value by key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return self
     */
    public function set($key, $value)
    {
       $this->map[$key] = $value;

       return $this;
    }

    /**
     * Unset untyped hash map value by key.
     *
     * @param mixed $key
     *
     * @return self
     */
    public function unset($key)
    {
        unset($this->map[$key]);

        return $this;
    }

    /**
     * Lookup untyped hash map value to get the key.
     *
     * @param mixed $value
     *
     * @throws \InvalidArgumentException when value is not found in map.
     *
     * @return mixed
     */
    public function key($value)
    {
        $key = array_search($value, $this->map, true);

        if( $key === false ) {
           throw new InvalidArgumentException('The value is not set in the map.');
        }

       return $key;
    }
}
