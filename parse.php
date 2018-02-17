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

class InstructionClass{
    public $order; // Poradi instrukce.
    public $opcode; // Hodnota operacniho kodu.
    public $arguments = array(); // Argumenty instrukce.
}

// Objekty instrukci.
$instructions = array();

// Osetreni vstupnich parametru.
Params($argc, $argv);

// Nacteni vstupu do pole.
//$inputFile = explode(PHP_EOL,file_get_contents("php://stdin"));
$inputFile = array("# adadad","  .IPPcode18"," #poca" ,"  ADD var 4 5 #scitani", "EQ pes kocka"); //DEBUG

// Zpracovani prvniho radku.
foreach ($inputFile as $line){
    // Odstraneni mezer a tabulatoru na zacatku radku.
    $line = trim($line," \t");

    // Odstraneni komentare.
    $line = preg_replace("/#.*/", "", $line);

    // Nalezeni prvniho radku.
    if(strtoupper($line) == ".IPPCODE18") {
        array_shift($inputFile);
        break;
    }

    // Odstraneni radku.
    array_shift($inputFile);
}

// Nenalezeno klicove slovo '.IPPCODE18'.
if(empty($inputFile))
    ErrorOutput(21);

// Zpracování instrukci.
$index = 1; // Pocitadlo poradi instrukce.
foreach ($inputFile as $line){
    // Odstraneni mezer a tabulatoru na zacatku radku.
    $line = trim($line," \t");

    // Odstraneni komentare.
    $line = preg_replace("/#.*/", "", $line);
    if(empty($line)) // Radek obsahoval pouze komentar.
        continue;

    // Zpracovani hodnoty operacniho kodu
    preg_match("/^\S*/", $line, $operationCode); // Nalezeni opcode.
    $operationCode = strtoupper(implode($operationCode)); // Prevod na velka pismena.
    $line = preg_replace("/^\S*/", "", $line); // Odstraneni opcode ze zbytku radku.

    $ins = new InstructionClass();
    $ins->order = $index; // Poradi instrukce.

    // Zpracovani argumentu.
    switch ($operationCode){
        case "ADD":
        case "SUB":
        case "MUL":
        case "IDIV":
            $ins->opcode = $operationCode;
            break;
        default:
            ErrorOutput(21);
    }


    $instructions[] =$ins;
    $index++;
}