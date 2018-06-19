<?php

namespace ArtisanSDK\Generic;

use InvalidArgumentException;
use BadMethodCallException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

abstract class Generic implements Contract
{
    /**
     * Map of method parameter positions to argument types.
     *
     * @example Generic::make($template, 'int', 'string') --> ['foo' => [0 => 'int', 1 => 'string']]
     *
     * @var array
     */
    private $methods = [];

    /**
     * The underlying untyped template.
     *
     * @var \ArtisanSDK\Generic\Contract
     */
    private $template;

    /**
     * Flag for if generic type checking is enabled.
     *
     * @var bool
     */
    private $enabled;

    /**
     * The environment variable that enables generic type checking.
     *
     * @var string
     */
    const ENV_VARIABLE = 'PHP_GENERICS_DISABLE';

    /**
     * Construct the generic as a typed proxy to the untyped template.
     *
     * @param mixed $template
     * @param mixed $types
     *
     * @throws \ReflectionException when method is missing parameter dockblocks
     */
    public function __construct()
    {
        $types = func_get_args();
        $template = array_shift($types);

        // The untyped template could be anything from a string, object,
        // or template type resolver (callable) so we need to resolve input
        // to an instance of the template object.
        $this->template = $this->resolveTemplate($template);

        // Type checking can be slower than not type checking so if you want
        // max performance in production, set the environment variable to false
        // and that will disable generic type checking. You should have already
        // ran type checks in CI/CD pipelines so any errors should have been caught.
        if( $this->enabled() ) {

            // The remaining arguments of the constructor are the types that need
            // to be setup for when methods are called. Each argument defines a type
            // for that positional argument.
            $types = $this->resolveTypes($types);

            // Using reflection on the generic interface we get the parameters
            // names that correspond to the position of the resolved types.
            $reflection = new ReflectionClass($this->template);
            $method = $reflection->getMethod('generic');
            $params = $this->resolveParams($method);
            if( empty($params) ) {
                throw new ReflectionException(sprintf('Invalid docblock on %s::%s() method.', $method->class, $method->name));
            }

            // Using reflection on the public methods of the template we create
            // a method map of template method parameters to the resolved types.
            foreach($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $this->methods[$method->name] = [];
                foreach($this->resolveParams($method) as $offset => $name) {
                    $index = array_search($name, $params, true);
                    $this->methods[$method->name][$offset] = $types[$index];
                }
                // @todo iterate over actual params instead so dobclock params are defaults only
            }
        }
    }

    /**
     * Forward calls to proxied untyped template.
     *
     * @param  string $method
     * @param  array  $args
     *
     * @throws \BadMethodCallException when call cannot be forwarded
     *
     * @return mixed
     */
    public function __call($method, $args = [])
    {
        if( method_exists($this->template, $method) ) {

            if( $this->enabled() ) {
                foreach($args as $index => $value ) {

                    // Only assert the type matches if the argument for the method
                    // was templated as a generic parameter type.
                    if( isset($this->methods[$method][$index])) {
                        $this->assertTypeMatches($this->methods[$method][$index], $value);
                    }
                }
            }

            // @todo could do return type checking too
            return call_user_func_array([$this->template, $method], $args);
        }

        throw new BadMethodCallException(sprintf('Generic %s::%s() method does not exist.', get_class($this->template), $method));
    }

    /**
     * Make a new generic based on class.
     *
     * @param mixed $template for generic
     * @param mixed $types for generic template
     *
     * @return \ArtisanSDK\Generic\Contract
     */
    abstract public static function generic() : Contract;

    /**
     * Determine if generic type checks are enabled.
     *
     * @return bool
     */
    private function enabled() : bool
    {
        if( is_null($this->enabled) ) {
            $this->enabled = ! (bool) getenv(static::ENV_VARIABLE);
        }

        return $this->enabled;
    }

    /**
     * Assert that the type at the index position matches the type required.
     *
     * @param string $expected type of argument
     * @param mixed $value of argument to be type matched
     *
     * @throws \InvalidArgumentException when type does not match
     *
     * @return void
     */
    private function assertTypeMatches(string $expected, $value) : void
    {
        $actual = is_string($value) ? static::TYPE_STRING : $this->resolveType($value);

        if( $expected !== $actual ) {
            throw new InvalidArgumentException(sprintf('Expecting %s type argument but received %s instead.', $expected, $actual));
        }
    }

    /**
     * Resolve the untyped template from a mixed argument.
     *
     * @param  mixed $template to resolve
     *
     * @return \ArtisanSDK\Generic\Contract
     */
    private function resolveTemplate($template) : Contract
    {
        // Untyped template was passed in already constructed.
        if( is_object($template) ) {
            return $template;
        }

        // A resolver was passed which should make the untyped template.
        if( is_callable($template) ) {
            return $this->resolveTemplate($template());
        }

        // An assumed class name was passed to construct the untyped template.
        // If the untyped template is not implement the generic contract then
        // the template will be constructed but the return type check of this
        // method will fail and that will ensure type consistency.
        return new $template();
    }

    /**
     * Resolve all the types to their position in the array.
     *
     * @param  array $types to be resolved
     *
     * @return array
     */
    private function resolveTypes(array $types = []) : array
    {
        $resolved = [];

        foreach($types as $index => $type) {
            $resolved[$index] = $this->resolveType($type);
        }

        return $resolved;
    }

    /**
     * Resolve a mixed type to a built-in type or assumed class name.
     *
     * @param  mixed $type to resolve
     *
     * @throws \InvalidArgumentException when generic type is not supported.
     *
     * @return string
     */
    private function resolveType($type) : string
    {
        // Pass an object, get back the FQNS as custom type.
        if( is_object($type) ) {
            return get_class($type);
        }

        // Pass an array, get back the array built in type.
        if( is_array($type) ) {
            return static::TYPE_ARRAY;
        }

        // Pass a callable, get back the callable built in type.
        if( is_callable($type) ) {
            return static::TYPE_CALLABLE;
        }

        // Pass a boolean, get back the boolean built in type.
        if( is_bool($type) ) {
            return static::TYPE_BOOLEAN;
        }

        // Pass an integer, get back the integer built in type.
        if( is_int($type) ) {
            return static::TYPE_INT;
        }

        // Pass a float, get back the float built in type.
        if( is_float($type) ) {
            return static::TYPE_FLOAT;
        }

        // Pass a null, get back the null built in type.
        if( is_null($type) ) {
            return static::TYPE_NULL;
        }

        // Pass a string, get back the string as a custom type.
        // If you pass "string" as the type then it's the same
        // as passing in the built in type for string.
        if( is_string($type) ) {
            return $type;
        }

        // Pass a resource, get back the resource built in type.
        if( is_resource($type) ) {
            return static::TYPE_RESOURCE;
        }

        // We could create a macro ability for the generic to resolve custom
        // types using a generic type resolver interface. For now we throw exception.
        throw new InvalidArgumentException('Generic type given is not supported.');
    }

    /**
     * Resolve the parameters from the docblocks for the reflection method.
     *
     * @param \ReflectionMethod $method
     *
     * @return array
     */
    private function resolveParams(ReflectionMethod $method) : array
    {
        preg_match_all('/\@param[\s]+[\w]+[\s]+\$([\w]+)/', $method->getDocComment(), $matches);

        return $matches[1];
    }
}
