<?php

require_once __DIR__. "/Rule.php";
require_once __DIR__. "/Language.php";

class RulesSet
{
    /**
     * @var Rule[]
     */
    private $rules = [];

    public function AddRule(Rule $rule) {
        $this->rules[] = $rule;
    }

    public function toBitString(): string{
        $bitString = "";
        foreach ($this->rules as $rule)
            $bitString .= $rule->toBitString();
        return $bitString;
    }

    public static function fromBitString(string $bitString, int $ruleLength, Language $language): RulesSet {
        $ruleStrings = str_split($bitString, $ruleLength);
        $self = new self();
        foreach ($ruleStrings as $ruleString){
            $self->AddRule(Rule::fromBitString($ruleString, $language));
        }
        return $self;
    }

    /**
     * @return Rule[]
     */
    public function getRules()
    {
        return $this->rules;
    }

    public function __toString(): string
    {
        $string = "";
        foreach ($this->getRules() as $key => $rule) {
            $string .= $key.". " .$rule->__toString() . PHP_EOL;
        }
        return $string;
    }


}