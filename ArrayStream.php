<?php

declare(strict_types=1);
set_include_path(get_include_path() . PATH_SEPARATOR . "core");
spl_autoload_extensions(".php");
spl_autoload_register();

/**
 * Description of ArrayStream
 *
 * @author fulvi
 */
class ArrayStream extends ArrayObject {

    private $iterator, $loopSequence, $iteratorIndex, $collectedArray, $assocMap, $binaryNameMapEntry, $binaryNameListEntry;

    public function __construct(array $input, array $assocMap = null,
            int $flag = ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS,
            string $iterator = "ArrayIterator"
//            ,int $max_nesting_level = 999999999999999999
    ) {
        $this->setFlags($flag);
        $this->setIteratorClass($iterator);
//        ini_set("xdebug.max_nesting_level", (string) $max_nesting_level);
        parent::__construct($input);
//        parent::__construct(array_chunk($input, 2000, true));
        $this->assocMap = $assocMap;
        $this->binaryNameMapEntry = base_convert(unpack('H*', "MapEntry")[1], 16, 2);
        $this->binaryNameListEntry = base_convert(unpack('H*', "ListEntry")[1], 16, 2);
        ;
    }

    public function stream(): ArrayStream {
//        if (count($this) > 2000) {
//            
//        } else {
            $this->iterator = parent::getIterator();
//        }
        return $this;
    }

    public function map(callable $mapper): ArrayStream {
        $this->addHistoryCall("map", $mapper);
        return $this;
    }

    public function filter(callable $filter): ArrayStream {
        $this->addHistoryCall("filter", $filter);
        return $this;
    }

    /**
     * Collect stremed data to a callable function($key, $value){}
     * 
     * @param callable $collector 
     * @return $this: ArrayStream
     */
    public function collect(callable $collector): ArrayStream {
        $this->addHistoryCall("collect", $collector);
        $this->callFisrt();
        return $this;
    }

    public function getCollectedArray(): array {
        return $this->collectedArray;
    }

    private function __map(callable $filter): void {
        if ($this->iterator->valid()) {
            $mapped = call_user_func_array($filter, [$this->iterator->key(), $this->iterator->current()]);
            if ($mapped !== null) {
                $this->iterator->offsetSet($this->iterator->key(), $mapped);
                $this->callNext();
            } else {
                $this->iterator->offsetUnset($this->iterator->key());
                $this->callFisrt();
            }
        }
    }

    private function __filter(callable $filter): void {
        if ($this->iterator->valid()) {
            if ((boolean) call_user_func_array($filter, [$this->iterator->current()])) {
                $this->callNext();
            } else {
                $this->iterator->offsetUnset($this->iterator->key());
                $this->callFisrt();
            }
        }
    }

    private function getAssociation($key) {
        if ($this->assocMap !== null && count($this->assocMap) > 0 && array_key_exists($key, $this->assocMap)) {
            return $this->assocMap[$key];
        } else {
            return $key;
        }
    }

    private function setCollection($result) {
        $binaryClassName = base_convert(unpack('H*', get_class($result))[1], 16, 2);
        switch ($binaryClassName) {
            case $this->binaryNameMapEntry:
                $this->collectedArray[$this->getAssociation($result->getKey())] = $result->getVal();
                break;
            case $this->binaryNameListEntry:
                $this->collectedArray[] = $result->getVal();
                break;
            default:
                $this->collectedArray[$this->getAssociation($this->iterator->key())] = $result;
                break;
        }
    }

    private function __collect(callable $collector): void {
        if ($this->iterator->valid()) {
            $result = call_user_func_array($collector, [$this->iterator->key(), $this->iterator->current()]);
            $this->setCollection($result);
            $this->loop();
        }
    }

    private function addHistoryCall(string $calledMethod, $args): void {
        $this->loopSequence[] = ["__" . $calledMethod, $args];
    }

    private function callFisrt(): void {
        $this->iteratorIndex = 0;
        $this->{(string) $this->loopSequence[$this->iteratorIndex][0] }($this->loopSequence[$this->iteratorIndex][1]);
    }

    private function callNext(): void {
        $loopSequence = $this->loopSequence[++$this->iteratorIndex];
        $this->{(string) $loopSequence[0]}($loopSequence[1]);
    }

    private function loop(): void {
        $this->iterator->next();
        $this->callFisrt();
    }

}

//$x = "teste3" == $x;
$starttime = microtime(true);
$array = [];
//for ($i = 0; $i <= 525000; $i++) {
for ($i = 0; $i <= 600000; $i++) {
    $array[] = "teste" . $i;
}

echo microtime(true) - $starttime;
$starttime = microtime(true);

$test = new ArrayStream($array, [2 => "2zebers", 4 => "4zebers"]);
$arrayCollected = $test->stream()
//        ->filter(function ($val) {
//            return  $val >= "teste73"; // $var = "teste3" == $var
//        })
//        ->map(function($key, $val) {
//            return str_replace("teste", "ola", $val);
//        })
//        ->filter(function ($val) {
//            return "ola4" != $val; // $var = "teste3" == $var
//        })
//        ->map(function($key, $val) {
//            return str_replace("ola", "teste", $val);
//        })
        ->collect(Collectors::toList());
//print_r($arrayCollected);
//print_r($arrayCollected->getArrayCopy());
//print_r($arrayCollected->getCollectedArray());
echo microtime(true) - $starttime;
