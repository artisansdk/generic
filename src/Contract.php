<?php

namespace ArtisanSDK\Generic;

interface Contract {

    /**
     * The built-in types allowed for a generic.
     *
     * @var string
     */
    const TYPE_ARRAY = 'array';
    const TYPE_BOOL = 'boolean';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_CALLABLE = 'callable';
    const TYPE_DOUBLE = 'float';
    const TYPE_FLOAT = 'float';
    const TYPE_INT = 'integer';
    const TYPE_INTEGER = 'integer';
    const TYPE_NULL = 'null';
    const TYPE_RESOURCE = 'resource';
    const TYPE_STRING = 'string';

    /**
     * Make a new generic based on class.
     *
     * @return \ArtisanSDK\Generic\Contract
     */
    public static function generic() : Contract;
}
