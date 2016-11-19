<?php
require_once __DIR__ . "/local.classes.php";

$example = [
    "SKY" => "SUN",
    "AIR" => "COLD",
    "AIR_WET" => "LOW",
    "WIND" => "STRONG",
    "WATER" => "HOT",
    "PROGNOZA" => "CHANGE"
];

$rules = unserialize(file_get_contents(__DIR__."/rules.ml"));

foreach ($rules as $rule) {
    /**
     * @var $rule Rule
     */
    if ($rule->doesClassify($example)){
        echo "Example result is: " . $rule->getResult();
        return;
    }
}