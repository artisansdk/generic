<?php

namespace ArtisanSDK\Generic\Types\Templates;

use ArtisanSDK\Generic\Contract;
use ArtisanSDK\Generic\Types\Collection as Type;

class Collection implements Contract
{
    /**
     * Untyped items in collection.
     *
     * @var array
     */
    private $items = [];

    /**
     * Make a new generic based on class.
     *
     * @param mixed $item for generic collection type
     *
     * @return \ArtisanSDK\Generic\Contract
     */
    public static function generic() : Contract
    {
        $args = func_get_args();

        return new Type(...$args);
    }

    /**
     * Get all the untyped items in the collection.
     *
     * @return array
     */
    public function all() : array
    {
        return $this->items;
    }

    /**
     * Add untyped item to collection.
     *
     * @param mixed $item
     *
     * @return self
     */
    public function add($item)
    {
       $this->items[] = $item;

       return $this;
    }

    /**
     * Remove untyped item from collection.
     *
     * @param mixed $item
     *
     * @return self
     */
    public function remove($item)
    {
       $index = array_search($item, $this->items, true);

       if( false !== $index ) {
           unset($this->items, $index);
       }

       return $this;
    }
}
