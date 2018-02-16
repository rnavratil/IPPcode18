<?php
function Params($count,$parameters){
    for($i = 1; $i < $count; $i++){
        if(($parameters[$i] == "--help") and ($count == 2)){
            //TODO print napoveda
            exit(0);
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


class Instruction{
    public $order; // Poradi instrukce.
    public $opcode; // Hodnota operacniho kodu.
    public $arguments = array(); // Argumenty instrukce.
}

// Objekty instrukci.
$instructions = array();

// Sada instrukci jazyka IPPcode18.
$OperationCodes = array(
    "MOVE", "CREATEFRAME", "PUSHFRAME", "POPFRAME", "DEFVAR", "CALL", "RETURN",
    "ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "NOT", "INT2CHAR", "STRI2INT",
    "READ", "WRITE",
    "CONCAT", "STRLEN", "GETCHAR", "SETCHAR",
    "TYPE",
    "LABEL", "JUMP", "JUMPIFEQ", "JUMPIFNEQ",
    "DPRINT", "BREAK");

// Osetreni vstupnich parametru.
Params($argc, $argv);

// Nacteni vstupu do pole.
//$inputFile = explode(PHP_EOL,file_get_contents("php://stdin"));
$inputFile = array(".IPPcode18", "DEFVAR morgu", "EQ pes kocka"); //DEBUG

// Osetreni a zpracovani prvniho radku.
if (array_shift($inputFile) != ".IPPcode18")
    ErrorOutput(21);

// Zpracování instrukcí.
$index = 1; // Pocitadlo poradi instrukce.
foreach ($inputFile as $line){
    // Zpracovani hodnoty operacniho kodu
    preg_match("/^\S*/", $line, $operationCode); // Nalezeni opcode.
    $operationCode = implode($operationCode);
    $line = preg_replace("/^\S*/", "", $line); // Odstraneni opcode.
    if (!(in_array($operationCode, $OperationCodes))) // Overeni opcode.
        ErrorOutput(21);

    $ins = new Instruction();
    $ins->opcode = $operationCode;
    $ins->order = $index;

    // Zpracovani argumentu
    // Stavovy automat

    $instructions[] =$ins;
    $index++;
}


