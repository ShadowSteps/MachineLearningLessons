<?php

require_once __DIR__ . "/Attribute.php";

class Language
{
    /**
     * @var Attribute[]
     */
    private $attributes = [];
    private $resultAttribute;
    /**
     * Language constructor.
     * @param array $attributes
     */
    public function __construct(array $attributes, Attribute $resultAttribute)
    {
        foreach ($attributes as $attribute){
            if ($attribute instanceof Attribute&&!in_array($attribute, $this->attributes))
                $this->attributes[] = $attribute;
        }
        $this->resultAttribute = $resultAttribute;
    }

    /**
     * @return Attribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @return Attribute
     */
    public function getResultAttribute()
    {
        return $this->resultAttribute;
    }

    public function isExampleFromLanguage(array $example){
        $check = true;
        foreach ($this->attributes as $attribute){
            $check &= (
                    array_key_exists($attribute->getName(), $example) &&
                    in_array($example[$attribute->getName()], $attribute->getValues())
                );
        }
        $check &= array_key_exists($this->resultAttribute->getName(), $example) &&
            in_array($example[$this->resultAttribute->getName()], $this->resultAttribute->getValues());
        return $check;
    }

}