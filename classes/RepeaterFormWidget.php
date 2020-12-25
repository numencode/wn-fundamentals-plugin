<?php namespace NumenCode\Fundamentals\Classes;

use NumenCode\Fundamentals\Traits\Wrapper;

class RepeaterFormWidget
{
    use Wrapper;

    public function getSaveValue($value, $debug = false)
    {
        // Handle second level repeater
        if (is_array($value)) {
            return $value;
        }

        // Handle Translatable fields
        if (starts_with(class_basename($this->parent), 'ML') && is_scalar($value)) {
            return $value ?: null;
        }

        // Default action
        return $this->parent->getSaveValue($value, $debug);
    }
}
