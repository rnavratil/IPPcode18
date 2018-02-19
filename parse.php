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
        case 1:
            fwrite(STDERR,"Nenalezeno klicove slovo '.IPPCODE18'.");
            exit(21);
        case 2:
            fwrite(STDERR,"Pouzit neznamy operacni kod.");
            exit(21);
        case 3:
            fwrite(STDERR,"Argument instrukce ma spatny typ.");
            exit(21);
        case 4:
            fwrite(STDERR,"Nespravny pocet argumentu instrukce.");
            exit(21);
        case 5:
            fwrite(STDERR,"Nespravny format promenne");
            exit(21);
        case 21:
            fwrite(STDERR,"Lexikální nebo syntaktická chyba.");
            exit(21);
        case 10:
            fwrite(STDERR,"Chyba vstupnich parametru.");
            exit(10);
    }
}

function IsVariable($variable){
    if(!preg_match("/^(LF|lf|lF|Lf|GF|gf|gF|Gf|TF|tf|Tf|tF)@([a-zA-Z\_$\-\&\%\*]+)$/", $variable,$kkt ))
        ErrorOutput(5);
    $variable = strtoupper( substr( $variable, 0, 2 ) ).substr( $variable, 2 );
    return $variable;
}

function Variable($line, $ins){
    $variable = GetOperand($line);
    $variable = IsVariable($variable); // Validace promenne.
    array_push($ins->arguments, $variable);
    return $line;
}

function GetOperand(&$line){
    $line = trim($line," \t");
    if(empty($line)) // Test na maly pocet argumentu instrukce.
        ErrorOutput(4);
    preg_match("/^\S*/", $line, $operand);
    $line = preg_replace("/^\S*/", "", $line);
    return implode($operand);
}

function EndOfOperands($line){
    $line = trim($line, " \t");
    if($line != null)
        ErrorOutput(4);
}

function XmlOutput($instructions){
    //TODO
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
$inputFile = array("# adadad","  .IPPcode18"," #poca" ,"  ADD Gf@\$fafe 3 5 #scitani"); //DEBUG

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
    ErrorOutput(1);

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
        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "RETURN":
        case "BREAK":
            $ins->opcode = $operationCode;
            // Test na prebytecne operandy instrukce.
           EndOfOperands($line);
            break;

        case "DEFVAR":
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "ADD":
        case "SUB":
        case "MUL":
        case "IDIV":
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            for($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                if(($symbol=intval($symbol)) == 0)
                    ErrorOutput(3);
                array_push($ins->arguments, $symbol);
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "WRITE":
            $ins->opcode = $operationCode;
            // Zpracovani operandu.
            $symbol = GetOperand($line);
            array_push($ins->arguments, $symbol);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "STRLEN":
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani operandu.
            $symbol = GetOperand($line);
            array_push($ins->arguments, $symbol);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        default:
            ErrorOutput(2);
    }

    $instructions[] =$ins;
    $index++;
}
XmlOutput($instructions);