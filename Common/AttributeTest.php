<?php

/**
 * Created by PhpStorm.
 * User: John
 * Date: 19.11.2016 г.
 * Time: 17:53
 */
require_once __DIR__ . "/Attribute.php";
class AttributeTest {
    /**
     * @var Attribute
     */
    private $attribute;
    private $values = [];

    /**
     * AttributeTest constructor.
     * @param $attribute
     * @param $value
     */
    public function __construct(Attribute $attribute, array $values)
    {
        $this->attribute = $attribute;
        foreach ($values as $value)
            if (in_array($value, $this->attribute->getValues()))
                $this->values[] = $value;
    }

    public function test(array $example): bool {
        return (array_key_exists($this->attribute->getName(), $example) &&
            strlen($example[$this->attribute->getName()]) &&
            in_array($example[$this->attribute->getName()], $this->values));
    }

    public function toBitString(): string{
        $string = "";
        if (count($this->values) <= 0)
            return str_repeat("1", count($this->attribute->getValues()));
        foreach ($this->attribute->getValues() as $possibleValue)
            $string .= in_array($possibleValue, $this->values) ? "1" : "0";
        return $string;
    }

    public static function fromBitString(string $bitString, Attribute $attribute) : AttributeTest{
        $values = $attribute->getValues();
        if (count($values) != strlen($bitString))
            throw new InvalidArgumentException("Length of bit string must be equal to the attribute values count!");
        $bits = str_split($bitString);
        $valueTypes = [];
        foreach ($values as $key => $value){
            $bitMask = $bits[$key];
            if ($bitMask == "1")
                $valueTypes[] = $value;
        }
        return new self($attribute, $valueTypes);
    }

    public function __toString(): string
    {
        $string = $this->attribute->getName() . "=(". implode("V", $this->values) . ")";
        return $string;
    }
}