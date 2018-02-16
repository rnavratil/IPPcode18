<?php

class ParameterFlags{
    public $flagRecursive = false;
    public $flagDirectory = false;
    public $flagParse = false;
    public $flagInt = false;
}
$flags = new ParameterFlags();

function Params($count,$parameters,$flag){
    for($i = 1; $i < $count; $i++){
        if(($parameters[$i] == "--help") and ($count == 2)) {
            //TODO print napoveda
            exit(0);
        }elseif (($parameters[$i] == "--recursive") and !($flag->flagRecursive)){
            $flag->flagRecursive = true;
        }else
            ErrorOutput(10);
    }
}

function ErrorOutput($number){
    switch ($number){
        case 21: // Lexikální nebo syntaktická chyba.
            fwrite(STDERR,"21"); // TODO
            exit(21);

        case 10: // Chyba vstupnich parametru.
            fwrite(STDERR,"10"); // TODO
            exit(10);
    }
}

Params($argc,$argv,$flags);