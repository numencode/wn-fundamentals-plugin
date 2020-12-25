<?php namespace NumenCode\Fundamentals\Classes;

use NumenCode\Fundamentals\Traits\Wrapper;

class RepeaterFormField
{
    use Wrapper;

    public function getName($arrayName = null)
    {
        if (starts_with($arrayName, 'RLTranslate')) {
            $offset = substr($arrayName, strlen('RLTranslate'));
            $arrayName = $this->arrayName . '[RLTranslate]' . $offset;
        }

        return $this->parent->getName($arrayName);
    }
}
