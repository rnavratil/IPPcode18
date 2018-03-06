<?php
function Params($count,$parameters, $flags){

    for($i = 1; $i < $count; $i++) {
        if (($parameters[$i] == "--help") and ($count == 2)) {
            exit(0);
        }if (preg_match("/^--stats=.+$/", $parameters[$i],$tmp )) {
            $flags->flagStatp = true;
            $flags->file = preg_replace("/^--stats=/", "", $parameters[$i]);
        }if (($parameters[$i] == "--loc")) {
            $flags->flagLoc = true;
            $flags->LocOrderArgument = $i;
        }if (($parameters[$i] == "--comments")) {
            $flags->flagComment = true;
            $flags->CommentOrderArgument = $i;
        } else {
            ErrorOutput(10);
        }

    }
    // Kontrola pouzitych argumentu.
    if ($flags->flagLoc and !$flags->flagStatp)
        ErrorOutput(10);
    if ($flags->flagComment and !$flags->flagStatp)
        ErrorOutput(10);
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
            fwrite(STDERR,"Operand instrukce ma spatny typ.");
            exit(21);
        case 4:
            fwrite(STDERR,"Nespravny pocet argumentu instrukce.");
            exit(21);
        case 5:
            fwrite(STDERR,"Nespravny format operandu.");
            exit(21);
        case 21:
            fwrite(STDERR,"Lexikální nebo syntaktická chyba.");
            exit(21);
        case 10:
            fwrite(STDERR,"Chyba vstupnich parametru.");
            exit(10);
        case 12:
            fwrite(STDERR,"Chyba pri praci s vystupnim souborem");
            exit(12);
    }
}

/*  IsVariable: funkce na kontrolu formatu promenne.
 *  Vstupni hodnota: Textovy retezec.
 *  return :
 *      1 - Jedna se o promennou.
 *      0 - Nejedna se o promennou.
 * */
function IsVariable($variable){
    if(!preg_match("/^(LF|lf|lF|Lf|GF|gf|gF|Gf|TF|tf|Tf|tF)@([a-zA-Z\_$\-\&\%\*]+)$/", $variable,$tmp ))
        return 0; //ErrorOutput(5);
    //$variable = strtoupper( substr( $variable, 0, 2 ) ).substr( $variable, 2 );
    return 1; //return $variable;
}

function Label($line, $ins){
    $label = GetOperand($line);
    if(!preg_match("/^([a-zA-Z\_$\-\&\%\*]+)$/", $label,$tmp ))
        ErrorOutput(5);
    array_push($ins->arguments, $label);
    array_push($ins->types, "label");
    return $line;
}

function Variable($line, $ins){
    $variable = GetOperand($line);
    //$variable = IsVariable($variable); // Validace promenne.
    if (!isVariable($variable))
        ErrorOutput(5);
    $variable = strtoupper( substr( $variable, 0, 2 ) ).substr( $variable, 2 );
    array_push($ins->arguments, $variable);
    array_push($ins->types, "var");
    return $line;
}

function GetOperand(&$line){
    $line = trim($line," \t"); //
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

function WhatType($operand){
    if(preg_match("/^bool@\S+$/", $operand,$tmp )) {
        BoolChecker($operand);
        return "bool";
    }
    elseif (preg_match("/^string@\S*$/", $operand,$tmp )) {
        StringChecker($operand);
        return "string";
    }
    elseif (preg_match("/^int@\S+$/", $operand,$tmp ))
        return "int";
    else {
        if (IsVariable($operand))
            return "var";
        ErrorOutput(5);
    }
} // I know.

function BoolChecker($operand){
    $operand = substr($operand,5);
    if(!preg_match("/^(true|false)$/", $operand,$tmp ))
        ErrorOutput(5);
}

function StringChecker($operand){
    $operand = substr($operand,7);
    $operand = htmlentities($operand); // Prevod na XML entity.
    // Kontrola tvaru escape sekvence.
    $length = strlen($operand);
    for($i = 1; $i <= $length; $i++) {
        $char = substr($operand, 0, 1);
        $operand = substr($operand, 1);

        switch ($char){
            default:
                break;

            case "\\":
                    if(($i + 3) > $length)
                        ErrorD(5);
                    $i = $i + 3;
                    $char = substr($operand, 0, 3);
                    $operand = substr($operand, 3);
                    if(!preg_match("/^[0-9][0-9][0-9]$/", $char,$tmp ))
                        ErrorOutput(5);
                    break;
                }
    }
}

/*
 *
 *
 */
function XmlOutput($instructions){
    $xml = new DOMDocument('1.0');
    $xml->formatOutput = true;
    $program=$xml->createElement("program");
    $program->setAttribute("language", "IPPcode18");
    $xml->appendChild($program);
    $index = 1;
    foreach ($instructions as $object){
        $instruction=$xml->createElement("instruction");
        $instruction->setAttribute("order", $index);
        $instruction->setAttribute("opcode", $object->opcode);
        $program->appendChild($instruction);

        $indexArg = 1;
        foreach ($object->arguments as $argnt) {
            $argument = $xml->createElement("arg$indexArg", $argnt);
            $argument->setAttribute("type", array_shift($object->types));
            $instruction->appendChild($argument);
            $indexArg++;
        }

        $index++;
    }
    echo $xml->saveXML();
}

function Statistics($flags){
    $content="";
    if($flags->flagLoc and $flags->flagComment){
        if($flags->LocOrderArgument < $flags->CommentOrderArgument) {
            $content = $flags->LocNumber;
            $content = $content . "\r\n";
            $content = $content . $flags->CommentNumber;
        }else{
            $content = $flags->CommentNumber;
            $content = $content . "\r\n";
            $content = $content . $flags->LocNumber;
        }
    }elseif($flags->flagLoc){
        if($flags->LocOrderArgument < $flags->CommentOrderArgument) {
            $content = $flags->LocNumber;
        }
    }elseif($flags->flagComment){
        if($flags->LocOrderArgument > $flags->CommentOrderArgument) {
            $content = $flags->CommentNumber;
        }
    }
    $myfile = fopen($flags->file, "w") or ErrorOutput(12);
    fwrite($myfile, $content);
    fclose($myfile);

}

class InstructionClass{
    public $order; // Poradi instrukce.
    public $opcode; // Hodnota operacniho kodu.
    public $arguments = array(); // Argumenty instrukce.
    public $types = array(); // Typy argumentu instrukce.
}

class FlagClass{
    public $flagStatp = false;
    public $flagLoc = false;
    public $flagComment = false;

    public $LocOrderArgument = 42;
    public $CommentOrderArgument = 42;

    public $LocNumber = 0;
    public $CommentNumber = 0;
    public $file;
}

// Objekty instrukci.
$instructions = array();
// Objekt oznacujici pouzite parametry.
$flags = new FlagClass();
// Osetreni vstupnich parametru.
Params($argc, $argv, $flags);

// Nacteni vstupu do pole.
$inputFile = explode(PHP_EOL,file_get_contents("php://stdin"));
//$inputFile = array("MOVE s ","  .IPPcode18","CONCAT GF@par GF@lar GF@pars #cpmca" ); //DEBUG

// Zpracovani prvniho radku.
foreach ($inputFile as $line){
    // Odstraneni mezer a tabulatoru na zacatku radku.
    $line = trim($line," \t");
    // Odstraneni komentare.
    if(strpos($line, "#") !== false) {
        $flags->CommentNumber++;
        $line = preg_replace("/#.*/", "", $line);
    }
    // Nalezeni prvniho radku.
    if (strtoupper($line) == ".IPPCODE18") {
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
    if(strpos($line, "#") !== false) {
        $flags->CommentNumber++;
        $line = preg_replace("/#.*/", "", $line);
    }
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
        case "MOVE":
        case "NOT":
        case "INT2CHAR":
        case "TYPE":
        case "STRLEN":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            $symbol = GetOperand($line);
            if (IsVariable($symbol)) { // Pokud je to promenna, zpracuj ji jako promennou.
                Variable($symbol, $ins); // Zpracuj promennou.
            }
            else { // Zjisti typ operandu.
                $tmpType = WhatType($symbol);
                array_push($ins->types, $tmpType);

                if($tmpType == "string"){ // Prevod na HTML entity.
                    $symbol = substr($symbol, strlen($tmpType) + 1);
                    $symbol = htmlentities($symbol);
                    array_push($ins->arguments, $symbol);
                }else{
                    array_push($ins->arguments,substr($symbol, strlen($tmpType) + 1));
                }
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "CREATEFRAME":
        case "PUSHFRAME":
        case "POPFRAME":
        case "RETURN":
        case "BREAK":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Test na prebytecne operandy instrukce.
           EndOfOperands($line);
            break;

        case "DEFVAR":
        case "POPS":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "CALL":
        case "LABEL":
        case "JUMP":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Zpracovani labelu.
            $line = Label($line, $ins);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "PUSHS":
        case "WRITE":
        case "DPRINT":/*
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Zpracovani symbolu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            array_push($ins->types, $tmpType);
            if($tmpType == "string"){ // Prevod na HTML entity.
                $symbol = substr($symbol, strlen($tmpType) + 1);
                $symbol = htmlentities($symbol);
                array_push($ins->arguments, $symbol);
            } else {
                array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
    */
        $flags->LocNumber++; // Rozsireni statp.
        // Ulozeni operacniho kodu.
        $ins->opcode = $operationCode;
        // Zpracovani symbolu.
        $symbol = GetOperand($line);
        if (IsVariable($symbol)) { // Pokud je to promenna, zpracuj ji jako promennou.
            Variable($symbol, $ins); // Zpracuj promennou.
        }
        else { // Zjisti typ operandu.
            $tmpType = WhatType($symbol);
            array_push($ins->types, $tmpType);

            if($tmpType == "string"){ // Prevod na HTML entity.
                $symbol = substr($symbol, strlen($tmpType) + 1);
                $symbol = htmlentities($symbol);
                array_push($ins->arguments, $symbol);
            }else{
                array_push($ins->arguments,substr($symbol, strlen($tmpType) + 1));
            }
        }
        // Test na prebytecne operandy instrukce.
        EndOfOperands($line);
        break;
/*
        case "POPS":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
        case "ADD":
        case "SUB":
        case "MUL":
        case "IDIV":
        case "LT":
        case "GT":
        case "EQ":
        case "AND":
        case "OR":
        case "CONCAT":
        case "GETCHAR":
        case "SETCHAR":
        case "STRI2INT":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            for($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                if (IsVariable($symbol)) { // Pokud je to promenna, zpracuj ji jako promennou.
                    Variable($symbol, $ins); // Zpracuj promennou.
                }
                else { // Zjisti typ operandu.
                    $tmpType = WhatType($symbol);
                    array_push($ins->types, $tmpType);

                    if($tmpType == "string"){ // Prevod na HTML entity.
                        $symbol = substr($symbol, strlen($tmpType) + 1);
                        $symbol = htmlentities($symbol);
                        array_push($ins->arguments, $symbol);
                    }else{
                        array_push($ins->arguments,substr($symbol, strlen($tmpType) + 1));
                    }
                }
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
/*

 *
        case "LT":
        case "GT":
        case "EQ":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani prvniho symbolu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            // Zpracovani druheho symbolu.
            $symbol2 = GetOperand($line);
            $tmpType2 = WhatType($symbol2);
            // Kontrola typu.
            if($tmpType != $tmpType2)
                ErrorOutput(3);
            // Zapis do objektu.
            array_push($ins->types, $tmpType);
            if($tmpType == "string"){ // HTML entites.
                $symbol = substr($symbol, strlen($tmpType) + 1);
                $symbol = htmlentities($symbol);
                array_push($ins->arguments, $symbol);
            }else {
                array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            }
            array_push($ins->types, $tmpType2);
            if($tmpType2 == "string"){ // HTML entites.
                $symbol2 = substr($symbol2, strlen($tmpType) + 1);
                $symbol2 = htmlentities($symbol2);
                array_push($ins->arguments, $symbol2);
            }else {
                array_push($ins->arguments, substr($symbol2, strlen($tmpType2) + 1));
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "CONCAT":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani prvniho symbolu.
            for ($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                $tmpType = WhatType($symbol);
                if ($tmpType != "string")
                    ErrorOutput(3);
                array_push($ins->types, $tmpType);
                $symbol = substr($symbol, strlen($tmpType) + 1);
                $symbol = htmlentities($symbol);
                array_push($ins->arguments, $symbol);
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "AND":
        case "OR":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani prvniho symbolu.
            for ($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                $tmpType = WhatType($symbol);
                if ($tmpType != "bool")
                    ErrorOutput(3);
                array_push($ins->types, $tmpType);
                array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
/*
        case "STRLEN":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            if ($tmpType != "string")
                ErrorOutput(3);
            array_push($ins->types, $tmpType);
            $symbol = substr($symbol, strlen($tmpType) + 1);
            $symbol = htmlentities($symbol);
            array_push($ins->arguments, $symbol);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
/*
        case "NOT":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            if ($tmpType != "bool")
                ErrorOutput(3);
            array_push($ins->types, $tmpType);
            array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;

        case "INT2CHAR":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani prvniho symbolu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            if(!is_int($symbol))
                ErrorOutput(3);
            array_push($ins->types, $tmpType);
            array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
/*
        case "GETCHAR":
        case "STRI2INT":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            for ($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                $tmpType = WhatType($symbol);
                if ($i == 0 and $tmpType != "string")
                    ErrorOutput(3);
                elseif ($tmpType != "int")
                    ErrorOutput(3);

                array_push($ins->types, $tmpType);
                array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
        case "READ":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            $symbol = GetOperand($line);
            if($symbol != "int" and $symbol != "bool" and $symbol != "string")
                ErrorOutput(21);
            array_push($ins->types, "type");
            array_push($ins->arguments, $symbol);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
/*
        case "SETCHAR":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            for ($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                $tmpType = WhatType($symbol);
                if ($i == 0 and $tmpType != "int") // TODO nemuzou byt zaporna cisla
                    ErrorOutput(3);
                elseif ($tmpType != "string")
                    ErrorOutput(3);
                array_push($ins->types, $tmpType);
                if($tmpType == "string"){
                    $symbol = substr($symbol, strlen($tmpType) + 1);
                    $symbol = htmlentities($symbol);
                    array_push($ins->arguments, $symbol);
                }else {
                    array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
                }
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
            /*
        case "TYPE":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani promenne.
            $line = Variable($line, $ins);
            // Zpracovani symbolu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            array_push($ins->types, $tmpType);
            array_push($ins->arguments, substr($symbol, strlen($tmpType) + 1));
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
        case "JUMPIFEQ":
        case "JUMPIFNEQ":
            $flags->LocNumber++; // Rozsireni statp.
            // Ulozeni operacniho kodu.
            $ins->opcode = $operationCode;
            // Zpracovani labelu.
            $line = Label($line, $ins);
            // Zpracovani symbolu.
            for ($i = 0; $i < 2; $i++) {
                $symbol = GetOperand($line);
                if (IsVariable($symbol)) { // Pokud je to promenna, zpracuj ji jako promennou.
                    Variable($symbol, $ins); // Zpracuj promennou.
                }
                else { // Zjisti typ operandu.
                    $tmpType = WhatType($symbol);
                    array_push($ins->types, $tmpType);

                    if($tmpType == "string"){ // Prevod na HTML entity.
                        $symbol = substr($symbol, strlen($tmpType) + 1);
                        $symbol = htmlentities($symbol);
                        array_push($ins->arguments, $symbol);
                    }else{
                        array_push($ins->arguments,substr($symbol, strlen($tmpType) + 1));
                    }
                }
            }
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
        /*
        case "WRITE":
        case "DPRINT":
            $flags->LocNumber++;
            $ins->opcode = $operationCode;
            // Zpracovani operandu.
            $symbol = GetOperand($line);
            $tmpType = WhatType($symbol);
            array_push($ins->types, $tmpType);
            // Test na prebytecne operandy instrukce.
            EndOfOperands($line);
            break;
*/
        default:
            ErrorOutput(2);
    }

    $instructions[] =$ins;
    $index++;
}
// Volani funkce pro XML vystup.
XmlOutput($instructions);
// Volani funkce pro vystup statistiky.
if($flags->flagStatp)
    Statistics($flags);
return 0;