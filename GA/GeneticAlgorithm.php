<?php
require_once __DIR__ . "/../Common/Language.php";
require_once __DIR__ . "/../Common/Rule.php";
require_once __DIR__ . "/../Common/RulesSet.php";

/**
 * Created by PhpStorm.
 * User: John
 * Date: 19.11.2016 г.
 * Time: 17:57
 */
abstract class GeneticAlgorithm
{
    /**
     * @var array
     */
    private $setOfExamples;
    /**
     * @var float
     */
    private $tresholdValue;
    /**
     * @var float
     */
    private $mutateChance;
    /**
     * @var float
     */
    private $populationChangePercent;

    /**
     * @var integer
     */
    private $populationSize;

    /**
     * @var Language
     */
    private $language;

    private $population;

    /**
     * @var int
     */
    private $bitStringLength = 0;

    private $maxRuleSetCount = 10;

    /**
     * @param int $maxRuleSetCount
     */
    public function setMaxRuleSetCount(int $maxRuleSetCount)
    {
        $this->maxRuleSetCount = $maxRuleSetCount;
    }

    /**
     * GeneticAlgorithm constructor.
     * @param int $populationSize
     * @param array $setOfExamples
     * @param float $tresholdValue
     * @param float $mutateChance
     * @param float $populationChangePercent
     */
    public function __construct(Language $language, int $populationSize, array $setOfExamples, float $tresholdValue, float $mutateChance, float $populationChangePercent)
    {
        if ($populationSize <= 0)
            throw new InvalidArgumentException("Population size must be bigger then 0!");
        $this->populationSize = $populationSize;
        $this->language = $language;
        foreach ($setOfExamples as $example){
            if ($this->language->isExampleFromLanguage($example))
                $this->setOfExamples[] = $example;
        }
        $this->setOfExamples = $setOfExamples;
        if ($tresholdValue > 1 || $tresholdValue < 0)
            throw new InvalidArgumentException("Threshold must be between 0 and 1!");
        $this->tresholdValue = $tresholdValue;
        if ($mutateChance > 1 || $mutateChance < 0)
            throw new InvalidArgumentException("Mutate chance must be between 0 and 1!");
        $this->mutateChance = $mutateChance;
        if ($populationChangePercent > 1 || $populationChangePercent < 0)
            throw new InvalidArgumentException("Population change percent must be between 0 and 1!");
        $this->populationChangePercent = $populationChangePercent;
        foreach ($this->language->getAttributes() as $attribute){
            $this->bitStringLength += count($attribute->getValues());
        }
        $countResult = count($this->language->getResultAttribute()->getValues());
        $this->bitStringLength += $countResult == 2 ? 1 : $countResult;
    }

    protected abstract function CalculateRulesSetCheckValue(RulesSet $rule, array $setOfExamples): float;

    private function GenerateRandomBinaryString(int $length){
        $string = "";
        for($i = 0; $i < $length; $i++) {
            foreach ($this->language->getAttributes() as $attribute) {
                $subString = "";
                $valuesCount = count($attribute->getValues());
                for ($j = 0; $j < $valuesCount; $j++)
                    $subString .= mt_rand(0, 1);
                if (strpos($subString, "1") === FALSE)
                    $subString[mt_rand(0, $valuesCount - 1)] = "1";
                $string .= $subString;
            }
            if (count($this->language->getResultAttribute()->getValues()) == 2)
                $string.= mt_rand(0, 1);
            else
            {
                for ($j = 0; $j < count($this->language->getResultAttribute()->getValues()); $j++)
                    $string .= mt_rand(0, 1);
            }
        }
        return $string;
    }

    private function GenerateInitialPopulation(){
        $maxRuleSet = $this->maxRuleSetCount;
        for($i = 0; $i < $this->populationSize; $i++){
            $numberOfRules = mt_rand(1, $maxRuleSet);
            $this->population[] = $this->GenerateRandomBinaryString($numberOfRules);
        }
    }

    /**
     * @return array
     */
    private function CalculatePopulationCheckValues(): array {
        $checkValues = [];
        foreach ($this->population as $individual) {
            $set = RulesSet::fromBitString($individual, $this->bitStringLength, $this->language);
            $checkValues[] = $this->CalculateRulesSetCheckValue($set, $this->setOfExamples);
        }
        return $checkValues;
    }

    private function RandomInterval(int $Max): array {
        $lowerFirst = mt_rand(0, $Max);
        $higherFirst = $lowerFirst;
        while ($lowerFirst == $higherFirst)
            $higherFirst = mt_rand(0, $Max);
        if ($lowerFirst > $higherFirst){
            $c = $higherFirst;
            $higherFirst = $lowerFirst;
            $lowerFirst = $c;
        }
        return [$lowerFirst, $higherFirst];
    }
    /**
     * @param string $parentFirst
     * @param string $parentSecond
     * @return string[]
     */
    private function CreateIndividualsFromParents(string $parentFirst, string $parentSecond): array {
        $lengthFirst = strlen($parentFirst);
        $intervalFirst = [];
        $secondLength = strlen($parentSecond);
        $possibleCuts = [];
        while (count($possibleCuts) <= 0){
            $intervalFirst = $this->RandomInterval($lengthFirst);
            $fullLeft = floor($intervalFirst[0] / $this->bitStringLength);
            $minLeft = $intervalFirst[0] - ($fullLeft*$this->bitStringLength);
            $fullRight = floor($intervalFirst[1] / $this->bitStringLength);
            $minRight = $intervalFirst[1] - ($fullRight*$this->bitStringLength);
            $k = $minRight;
            while ($minLeft < $secondLength) {
                while ($k <= $secondLength){
                    if ($minLeft < $k){
                        $possibleCuts[] = [$minLeft, $k];
                    }
                    $k += $this->bitStringLength;
                }
                $minLeft+= $this->bitStringLength;
                $k = $minRight;
            }
        }
        $selectedCut = $possibleCuts[mt_rand(0, count($possibleCuts) - 1)];
        $firstIndividual = substr($parentFirst, 0, $intervalFirst[0]) . substr($parentSecond, $selectedCut[0], $selectedCut[1] - $selectedCut[0]) .  substr($parentFirst, $intervalFirst[1]);
        $secondIndividual = substr($parentSecond, 0,  $selectedCut[0]) . substr($parentFirst, $intervalFirst[0], $intervalFirst[1] - $intervalFirst[0]) .  substr($parentSecond, $selectedCut[1]);
        return [$firstIndividual, $secondIndividual];
    }

    /**
     * @return Rule[]
     */
    public function RunLearning(): RulesSet {
        echo "Creating population 1".PHP_EOL;
        $this->GenerateInitialPopulation();
        $checkValues = $this->CalculatePopulationCheckValues();
        arsort($checkValues);
        list($first) = $checkValues;
        $population = 1;
        while ($first < $this->tresholdValue) {
            $population++;
            echo "Creating population ".$population.PHP_EOL;
            $sum = array_sum($checkValues);
            $chanceArray = [];
            foreach ($checkValues as $key => $check)
                $chanceArray[$key] = ($check == 0 || $sum == 0) ? 0 : ($check / $sum);
            asort($chanceArray);
            $changeCount = floor($this->populationSize * $this->populationChangePercent);
            if ($changeCount % 2 == 1)
                $changeCount++;
            $stayCount = $this->populationSize - $changeCount;
            $newPopulation = [];
            for ($i = 0; $i < $stayCount; $i++){
                $rand = (float) mt_rand() / (float) mt_getrandmax();
                $selectedKey = mt_rand(0, count($this->population) - 1);
                foreach ($chanceArray as $kkey => $chance)
                    if ($rand < $chance){
                        $selectedKey = $kkey;
                        break;
                    }
                $newPopulation[] = $this->population[$selectedKey];
            }
            for ($i = 0; $i < ($changeCount / 2); $i++){
                $randFirst = (float) mt_rand() / (float) mt_getrandmax();
                $selectedKeyFirst = mt_rand(0, count($this->population) - 1);
                foreach ($chanceArray as $kkey => $chance){
                    if ($randFirst < $chance){
                        $selectedKeyFirst = $kkey;
                        break;
                    }
                }
                $randSecond = (float) mt_rand() / (float) mt_getrandmax();
                $selectedKeySecond = mt_rand(0, count($this->population) - 1);
                foreach ($chanceArray as $kkey => $chance){
                    if ($randSecond < $chance){
                        $selectedKeySecond = $kkey;
                        break;
                    }
                }
                if ($selectedKeySecond == $selectedKeyFirst){
                    if ($selectedKeySecond == 0) {
                        $selectedKeySecond ++;
                    } else {
                        $selectedKeySecond --;
                    }
                }
                $newPopulation = array_merge($newPopulation, $this->CreateIndividualsFromParents($this->population[$selectedKeyFirst], $this->population[$selectedKeySecond]));
            }
            $this->population = $newPopulation;
            if (((float) mt_rand() / (float) mt_getrandmax()) <= $this->mutateChance||count(array_unique($this->population)) == 1) {
                $mutateKey =  mt_rand(0, $this->populationSize - 1);
                $string = $this->population[$mutateKey];
                $rand = mt_rand(0, strlen($string) - 1);
                $string[$rand] = $string[$rand] == "1" ? "0" : "1";
                $this->population[$mutateKey] = $string;
            }
            $checkValues = $this->CalculatePopulationCheckValues();
            arsort($checkValues);
            list($first) = $checkValues;
        }
        list($firstKey) = array_keys($checkValues);
        echo "Population has successor with check: ".$checkValues[$firstKey].PHP_EOL;
        return RulesSet::fromBitString($this->population[$firstKey], $this->bitStringLength, $this->language);
    }

}