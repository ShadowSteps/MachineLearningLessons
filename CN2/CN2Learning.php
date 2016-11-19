<?php
require_once __DIR__ . "/../Common/Rule.php";
require_once __DIR__ . "/../Common/AttributeTest.php";

$language = [
    "SKY" => ["SUN", "RAIN"],
    "AIR" => ["HOT", "COLD"],
    "AIR_WET" => ["NORMAL", "HIGH"],
    "WIND" => ["STRONG", "WEAK"],
    "WATER" => ["HOT", "COLD"],
    "PROGNOZA" => ["SAME", "CHANGE"],
    "RESULT" => ["T", "F"]
];

function array_kshift(&$arr)
{
    list($k) = array_keys($arr);
    $r  = array($k=>$arr[$k]);
    unset($arr[$k]);
    return $r;
}

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

/**
 * @return AttributeTest[]
 */
function GetAllAttributeTests() {
    global $language;
    $tests = [];
    foreach ($language as $attr => $values) {
        if ($attr !== 'RESULT'){
            foreach ($values as $value){
                $tests[] = new AttributeTest($attr, [$value]);
            }
        }
    }
    return $tests;
}

/**
 * @param array $setOfExamples
 * @param Rule $rule
 * @return array
 */
function GetSubSetOfExamplesForRule(array $setOfExamples, Rule $rule) {
    $subSet = [];
    foreach ($setOfExamples as $example) {
        if ($rule->doesClassify($example))
            $subSet[] = $example;
    }
    return $subSet;
}

function GetMostCommonResultFromSet(array $SetOfExamples){
    global $language;
    $resultLang = $language["RESULT"];
    $countArray = [];
    foreach ($resultLang as $resultValue)
        $countArray[$resultValue] = 0;
    foreach ($SetOfExamples as $example) {
        $result = $example["RESULT"];
        $countArray[$result]++;
    }
    arsort($countArray);
    $most = array_kshift($countArray);
    $most = array_flip($most);
    return array_pop($most);
}

function RuleResultEntropy(array $SetOfExamples, Rule $rule) {
    $subSet = GetSubSetOfExamplesForRule($SetOfExamples, $rule);
    if (count($subSet) <= 0)
        return -1;
    return Entropy($subSet);
}

/**
 * @param array $setOfExamples
 * @param int $beamLevel
 * @return Rule
 */
function LearnOneRule(array $setOfExamples, int $beamLevel = 5) {
    $best = new Rule("T");
    $bestEntropy = 1.01;
    $candidates = [$best];
    $allTests = GetAllAttributeTests();
    while (count($candidates) > 0) {
        $i = -1;
        $newCandidates = [];
        $newCandidatesEntropy = [];
        foreach ($candidates as $candidate)
            foreach ($allTests as $test) {
                try {
                    /**
                     * @var $newCandidate Rule
                     */
                    $newCandidate = clone $candidate;
                    $newCandidate->addTest($test);
                    if (in_array($newCandidate, $newCandidates))
                        continue;
                    $subSet = GetSubSetOfExamplesForRule($setOfExamples, $newCandidate);
                    if (count($subSet) <= 0)
                        continue;
                    $i++;
                    $newCandidate->setResult(GetMostCommonResultFromSet($subSet));
                    $newCandidates[$i] = $newCandidate;
                    $newCandidatesEntropy[$i] = Entropy($subSet);
                } catch (TestAlreadyAddedToRuleException $exp){
                    continue;
                }
            }
        asort($newCandidatesEntropy);
        if (count($newCandidatesEntropy) > $beamLevel)
            $newCandidatesEntropy = array_slice($newCandidatesEntropy, 0, $beamLevel, true);
        $candidates = [];
        foreach ($newCandidatesEntropy as $key => $entropy) {
            if ($entropy < $bestEntropy){
                $best = $newCandidates[$key];
                $bestEntropy = $entropy;
            }
            $candidates[] = $newCandidates[$key];
        }
    }
    return $best;
}

function LearnSetOfRules(array $setOfExamples, int $beamLevel = 5, float $treshold = 0.25) {
    $i = -1;
    $learned = [];
    $learnedEntropy = [];
    $rule = LearnOneRule($setOfExamples, $beamLevel);
    while(count($setOfExamples) > 0&&($ruleEntropy = RuleResultEntropy($setOfExamples, $rule)) < $treshold) {
        $i++;
        $learned[$i] = $rule;
        $learnedEntropy[$i] = $ruleEntropy;
        foreach ($setOfExamples as $key => $example) {
            if ($rule->doesClassify($example)){
                unset($setOfExamples[$key]);
            }
        }
        $setOfExamples = array_values($setOfExamples);
        $rule = LearnOneRule($setOfExamples, $beamLevel);
    }
    asort($learnedEntropy);
    $resultRules = [];
    foreach ($learnedEntropy as $key => $value) {
        $resultRules[] = $learned[$key];
    }
    return $resultRules;
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

$rules = LearnSetOfRules($examples);
print_r($rules);
file_put_contents("rules.ml", serialize($rules));