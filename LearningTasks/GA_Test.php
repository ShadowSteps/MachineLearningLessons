<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
/**
 * Created by PhpStorm.
 * User: John
 * Date: 19.11.2016 г.
 * Time: 18:48
 */

require_once __DIR__ . "/../GA/GeneticAlgorithm.php";
require_once __DIR__ . "/../Common/Language.php";
require_once __DIR__ . "/../Common/Attribute.php";

$Language = new Language(
    [
        new Attribute("SKY", ["SUN", "RAIN", "CLOUDS"]),
        new Attribute("AIR", ["HOT", "COLD"]),
        new Attribute("AIR_WET", ["NORMAL", "HIGH"]),
        new Attribute("WIND", ["STRONG", "WEAK"]),
        new Attribute("WATER", ["HOT", "COLD"]),
        new Attribute("PROGNOZA", ["SAME", "CHANGE"])
    ],
    new Attribute("RESULT", ["T", "F"])
);

class GA_Test extends GeneticAlgorithm {

    protected function CalculateRulesSetCheckValue(RulesSet $rule, array $setOfExamples): float
    {
        $examplesCount = count($setOfExamples);
        $correct = 0;
        foreach ($setOfExamples as $example){
            foreach ($rule->getRules() as $rules){
                if ($rules->doesClassify($example)){
                    if ($rules->getResult() == $example[$rules->getResultAttribute()->getName()])
                        $correct++;
                    break;
                }
            }
        }
        return $correct / $examplesCount;
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

$Test = new GA_Test($Language, 100, $examples, 0.90, 0.001, 0.6);
$Rules = $Test->RunLearning();
echo "Found rules:".PHP_EOL;
echo ($Rules);