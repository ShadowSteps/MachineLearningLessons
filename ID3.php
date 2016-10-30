<?php
$language = [
    "SKY" => ["SUN", "RAIN"],
    "AIR" => ["HOT", "COLD"],
    "AIR_WET" => ["NORMAL", "HIGH"],
    "WIND" => ["STRONG", "WEAK"],
    "WATER" => ["HOT", "COLD"],
    "PROGNOZA" => ["SAME", "CHANGE"],
    "RESULT" => ["T", "F"]
];

function Entropy(array $SetOfExamples) {
    global $language;
    $resultLang = $language["RESULT"];
    $countArray = [];
    foreach ($resultLang as $resultValue)
        $countArray[$resultValue] = 0;
    foreach ($SetOfExamples as $example) {
        $result = $example["RESULT"];
        $countArray[$result]++;
    }
    $count = array_sum($countArray);
    $result = 0;
    foreach ($countArray as $valueCount){
        $proportion = ($valueCount/$count);
        $result += $proportion == 0 ? 0: -$proportion * log($proportion, 2);
    }
    return $result;
}

function FilterSetByAttributeValue(array $Set, $AttributeName, $attributeValue) {
    $newArray = [];
    foreach ($Set as $val){
        if ($val[$AttributeName] == $attributeValue)
            $newArray[] = $val;
    }
    return $newArray;
}

function Gain(array $Set, $AttributeName) {
    global $language;
    if (!count($Set))
        return -1;
    $attributeLang = $language[$AttributeName];
    $entropy = Entropy($Set);
    $setCount = count($Set);
    foreach ($attributeLang as $attributeValue) {
        $filtered = FilterSetByAttributeValue($Set, $AttributeName, $attributeValue);
        $filteredCount = count($filtered);
        $entropy -= $filteredCount == 0 ? 0 : ($filteredCount/$setCount) * Entropy($filtered);
    }
    return $entropy;
}

function MostGainAttribute(array $Set, array $SetOfAttributeNames) {
    $MostValueAble = "";
    $MostValueAbleGain = -2;
    foreach ($SetOfAttributeNames as $name){
        $gain = Gain($Set, $name);
        echo "Gain for attribute ".$name.":".$gain.PHP_EOL;
        if ($gain > $MostValueAbleGain) {
            $MostValueAble = $name;
            $MostValueAbleGain = $gain;
        }
    }
    echo "Most valueable $MostValueAble with gain: $MostValueAbleGain".PHP_EOL;
    return $MostValueAble;
}

function ID3(array $Set, array $Attributes) {
    global $language;
    $countElements = count($Set);
    $main = "RESULT";
    $entropy = Entropy($Set);
    echo "Set entropy: ".$entropy.PHP_EOL;
    if ($entropy == 0)
    {
        echo "Tree leaf: ".$Set[0]["RESULT"].PHP_EOL;
    } elseif (!count($Attributes)) {
        $resultLang = $language["RESULT"];
        $countArray = [];
        foreach ($resultLang as $resultValue)
            $countArray[$resultValue] = 0;
        foreach ($Set as $example) {
            $result = $example["RESULT"];
            $countArray[$result]++;
        }
        asort($countArray);
        $most = array_pop(array_keys($countArray));
        echo "Tree leaf: ".$most.PHP_EOL;
    } else {
        $mostGainAttribute = MostGainAttribute($Set, $Attributes);
        echo "Tree root: ".$mostGainAttribute.PHP_EOL;
        foreach ($language[$mostGainAttribute] as $attr){
            echo "Tree branch: ".$attr.PHP_EOL;
            $filtered = FilterSetByAttributeValue($Set, $mostGainAttribute, $attr);
            if (!count($filtered)){
                $resultLang = $language["RESULT"];
                $countArray = [];
                foreach ($resultLang as $resultValue)
                    $countArray[$resultValue] = 0;
                foreach ($Set as $example) {
                    $result = $example["RESULT"];
                    $countArray[$result]++;
                }
                asort($countArray);
                $most = array_pop(array_keys($countArray));
                echo "Tree leaf: ".$most.PHP_EOL;
            } else {
                ID3($filtered, array_diff($Attributes, [$mostGainAttribute]));
            }
        }
    }
}

$examples = [
    [
        "SKY" => "SUN",
        "AIR" => "HOT",
        "AIR_WET" => "NORMAL",
        "WIND" => "STRONG",
        "WATER" => "HOT",
        "PROGNOZA" => "SAME",
        "RESULT" => "T",
    ],
    [
        "SKY" => "SUN",
        "AIR" => "HOT",
        "AIR_WET" => "HIGH",
        "WIND" => "STRONG",
        "WATER" => "HOT",
        "PROGNOZA" => "SAME",
        "RESULT" => "T",
    ],
    [
        "SKY" => "RAIN",
        "AIR" => "COLD",
        "AIR_WET" => "HIGH",
        "WIND" => "STRONG",
        "WATER" => "HOT",
        "PROGNOZA" => "CHANGE",
        "RESULT" => "F",
    ],
    [
        "SKY" => "SUN",
        "AIR" => "HOT",
        "AIR_WET" => "HIGH",
        "WIND" => "STRONG",
        "WATER" => "COLD",
        "PROGNOZA" => "CHANGE",
        "RESULT" => "T",
    ],
    [
        "SKY" => "SUN",
        "AIR" => "HOT",
        "AIR_WET" => "NORMAL",
        "WIND" => "WEAK",
        "WATER" => "HOT",
        "PROGNOZA" => "SAME",
        "RESULT" => "F",
    ],
];

ID3($examples, array_diff(array_keys($language), ["RESULT"]));