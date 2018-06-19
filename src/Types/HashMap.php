<?php

namespace ArtisanSDK\Generic\Types;

use ArtisanSDK\Generic\Generic;
use ArtisanSDK\Generic\Contract;

class HashMap extends Generic
{
    /**
     * Construct the generic as a typed proxy to the untyped template.
     *
     * @param mixed $key type for generic hash map
     * @param mixed $value type for generic hash map
     */
    public function __construct($key, $value)
    {
        parent::__construct(Templates\HashMap::class, $key, $value);
    }

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

        return new static(...$args);
    }
}
