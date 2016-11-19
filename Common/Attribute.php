<?php

/**
 * Created by PhpStorm.
 * User: John
 * Date: 19.11.2016 г.
 * Time: 18:04
 */
class Attribute
{
    private $name;
    private $values = [];

    /**
     * Attribute constructor.
     * @param $name
     * @param array $values
     */
    public function __construct($name, array $values)
    {
        $this->name = $name;
        $this->values = $values;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
}