<?php

namespace ArtisanSDK\Generic\Types;

use ArtisanSDK\Generic\Generic;
use ArtisanSDK\Generic\Contract;

class Collection extends Generic
{
    /**
     * Construct the generic as a typed proxy to the untyped concrete.
     *
     * @param mixed $item
     */
    public function __construct($item)
    {
        parent::__construct(Templates\Collection::class, $item);
    }

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

        return new static(...$args);
    }
}
