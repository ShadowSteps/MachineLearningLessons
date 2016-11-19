<?php

/**
 * Created by PhpStorm.
 * User: John
 * Date: 19.11.2016 г.
 * Time: 17:52
 */

require_once __DIR__ . "/Exceptions/TestAlreadyAddedToRuleException.php";
require_once __DIR__ . "/AttributeTest.php";

class Rule {
    /**
     * @var AttributeTest[]
     */
    private $tests = [];
    private $result;
    /**
     * @var Attribute
     */
    private $resultAttribute;

    /**
     * Rule constructor.
     * @param $result
     */
    public function __construct(Attribute $resultAttribute, $defaultResult)
    {
        $this->resultAttribute = $resultAttribute;
        if (!in_array($defaultResult, $this->resultAttribute->getValues()))
            throw new InvalidArgumentException("Default result must be from possible values of result attribute!");
        $this->result = $defaultResult;
    }


    /**
     * @return boolean
     */
    public function getResult()
    {
        return $this->result;
    }

    public function setResult($result) {
        if (!in_array($result, $this->resultAttribute->getValues()))
            throw new InvalidArgumentException("Result must be from possible values of result attribute!");
        $this->result = $result;
    }


    public function addTest(AttributeTest $test) {
        if (in_array($test, $this->tests))
            throw new TestAlreadyAddedToRuleException();
        $this->tests[] = $test;
    }


    public function doesClassify(array $example) : bool {
        $result = true;
        $i = -1;
        while ($result && $i < count($this->tests) - 1){
            $i++;
            $test = $this->tests[$i];
            $result &= $test->test($example);
        }
        return $result;
    }

    public function toBitString(): string{
        $countResults = count($this->resultAttribute->getValues());
        $keyOfResult = array_search($this->result, $this->resultAttribute->getValues());
        $string = "";
        foreach ($this->tests as $test)
            $string .= $test->toBitString();
        $string .= $countResults == 2 ?
            $keyOfResult :
            str_repeat("0", $keyOfResult) . "1" . str_repeat("0", $countResults - $keyOfResult - 1);
        return $string;
    }

    public static function fromBitString(string $bitString, Language $language): Rule {
        $subStrStart = 0;
        $self = new self($language->getResultAttribute(), $language->getResultAttribute()->getValues()[0]);
        foreach ($language->getAttributes() as $attribute){
            $bitCount = count($attribute->getValues());
            $subBitString = substr($bitString, $subStrStart, $bitCount);
            if (strpos($subBitString, "0") !== false)
                $self->addTest(AttributeTest::fromBitString($subBitString, $attribute));
            $subStrStart += $bitCount;
        }
        $resultBit = substr($bitString, $subStrStart);
        if (strlen($resultBit) == 1){
            $self->setResult($language->getResultAttribute()->getValues()[$resultBit]);
        } else {
            $split = explode("1", $resultBit);
            $key = strlen($split[0]);
            $self->setResult($language->getResultAttribute()->getValues()[$key]);
        }
        return $self;
    }

    /**
     * @return Attribute
     */
    public function getResultAttribute()
    {
        return $this->resultAttribute;
    }

    public function __toString(): string
    {
        $string = "IF ";
        foreach ($this->tests as $test)
            $string .= $test->__toString() . "^";
        $string = rtrim($string, "^");
        $string .= " THEN ". $this->result;
        return $string;
    }

}