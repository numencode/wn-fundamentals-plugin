<?php namespace NumenCode\Fundamentals\Traits;

trait Wrapper
{
    private $parent;

    public function __construct($parent)
    {
        $this->parent = $parent;

        if (method_exists($this, 'init')) {
            $this->init($parent);
        }
    }

    public function __get($property)
    {
        return $this->parent->$property;
    }

    public function __set($property, $value)
    {
        $this->parent->$property = $value;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->parent, $method], $parameters);
    }

    public function __isset($property)
    {
        return isset($this->parent->$property);
    }

    public function getWrappedObject()
    {
        return $this->parent;
    }
}
