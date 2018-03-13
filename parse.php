<?php

/** Funkce pro osetreni vstupnich parametru.
 * @param $count -  Pocet parametru.
 * @param $parameters - Parametry.
 * @param $flags - Objekt obsahujici pouzite parametry.
 */
function Params($count,$parameters, $flags){

    for($i = 1; $i < $count; $i++) {
        if (($parameters[$i] == "--help") and ($count == 2)) {
            HelpPrint();
            exit(0);
        }elseif (preg_match("/^--stats=.+$/", $parameters[$i],$tmp )) {
            if($flags->flagStatp) // Osetreni duplicity zadaneho parametru.
                ErrorOutput(10);
            $flags->flagStatp = true;
            $flags->file = preg_replace("/^--stats=/", "", $parameters[$i]);
        }elseif (($parameters[$i] == "--loc")) {
            if($flags->flagLoc) // Osetreni duplicity zadaneho parametru.
                ErrorOutput(10);
            $flags->flagLoc = true;
            $flags->LocOrderArgument = $i;
        }elseif (($parameters[$i] == "--comments")) {
            if($flags->flagComment) // Osetreni duplicity zadaneho parametru.
                ErrorOutput(10);
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

/**
 * Funkce pro vypis napovedy.
 */
function HelpPrint(){
    echo "Projekt do predmetu IPP. Vytvoril Rostislav Navratil v roce 2018.\n\n";
    echo "Skript typu filtr 'parse.php'napsany v jazyce PHP 5.6 nacte ze standardniho vstupu zdrojovy kod v IPPcode18";
    echo ", zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardni vystup XML reprezentaci";
    echo " programu\n";
    echo "\nZakladni parametry bez rozsireni:\n";
    echo " --help  - vypise napovedu. Nelze ho kombinovat s jinymi parametry.\n";
    echo "\nChybove navratove kody specifické pro analyzátor:\n";
    echo "21 - lexikalni nebo syntakticka chyba zdrojoveho kodu zapsaneho v IPPcode18\n";
    echo "10 - nespravne pouzite parametry.\n";
    echo "12 - chyba pri manipulaci z vystupnim souborem\n";
    echo "\nRozsireni STATP:\n";
    echo "poradi parametru --loc a --coments ovlivnuje poradi vypisu hodnot do souboru.\n\n";
    echo "--stats=\<file\>  -  povinny parametr. \<file\> znaci cestu k souboru.\n";
    echo "--loc - volitelny parametr. Vypise do zvoleneho souboru \<file\> pocet instrukci.\n";
    echo "--comments - volitelny parametr. Vypise do souboru \<file\> komentaru za uvodnim kodem '.IPPcode18'.\n";
}

/** Funkce pro nestandartni ukonceni skriptu.
 * @param $number - Cislo chyby.
 */
function ErrorOutput($number){
    switch ($number){
        case 1:
            fwrite(STDERR,"Nenalezeno klicove slovo '.IPPCODE18'.\n");
            exit(21);
        case 2:
            fwrite(STDERR,"Pouzit neznamy operacni kod.\n");
            exit(21);
        case 3:
            fwrite(STDERR,"Operand instrukce ma spatny typ.\n");
            exit(21);
        case 4:
            fwrite(STDERR,"Nespravny pocet argumentu instrukce.\n");
            exit(21);
        case 5:
            fwrite(STDERR,"Nespravny format operandu.\n");
            exit(21);
        case 21:
            fwrite(STDERR,"Lexikální nebo syntaktická chyba.\n");
            exit(21);
        case 10:
            fwrite(STDERR,"Chyba vstupnich parametru.\n");
            exit(10);
        case 12:
            fwrite(STDERR,"Chyba pri praci s vystupnim souborem.\n");
            exit(12);
    }
}

/** Funkce overuje zda ma promenna spravny format.
 * @param $variable - promenna na overeni.
 * @return int - '0' nejedna se o promennou. '1' jedna se o promennou.
 */
function IsVariable($variable){
    if(!preg_match("/^(LF|GF|TF)@([a-zA-Z\_$\-\&\%\*]+)$/", $variable,$tmp ))
        return 0;
    return 1;
}

/** Funkce pro zpracovani navesti.
 * @param $line - radek s operandy.
 * @param $ins - objekt pro aktualni instrukci.
 * @return mixed - vraci zbytek radku.
 */
function Label($line, $ins){
    $label = GetOperand($line); // Z radku vybere prvni operand.
    if(!preg_match("/^([a-zA-Z\_$\-\&\%\*]+)$/", $label,$tmp ))
        ErrorOutput(5);
    $label = htmlentities($label); // Prevod problematickych znaku na entity.
    array_push($ins->arguments, $label); // Vlozeni hodnoty promenne do objektu.
    array_push($ins->types, "label"); // Vlozeni typu promenne do objektu.
    return $line;
}

/** Funkce pro zpracovani promenne.
 * @param $line - radek s operandy.
 * @param $ins - objekt pro aktualni instrukci.
 * @return mixed - vraci zbytek radku.
 */
function Variable($line, $ins){
    $variable = GetOperand($line); // Z radku vybere prvni operand.
    if (!isVariable($variable)) // Overeni zda se jedna o promennou.
        ErrorOutput(5);
    $variable = strtoupper( substr( $variable, 0, 2 ) ).substr( $variable, 2 );
    $variable = htmlentities($variable); // Prevod problematickych znaku na entity.
    array_push($ins->arguments, $variable); // Vlozeni hodnoty promenne do objektu.
    array_push($ins->types, "var"); // Vlozeni typu promenne do objektu.
    return $line;
}

/** Funkce pro nalezeni prvniho operandu z radku.
 * @param $line - radek s operandy.
 * @return string - nalezeny operand.
 */
function GetOperand(&$line){
    $line = trim($line," \t"); // Odstraneni mezer a tabulatoru pred operandem.
    if(empty($line)) // Test na maly pocet operandu instrukce.
        ErrorOutput(4);
    preg_match("/^\S*/", $line, $operand); // Nalezeni operandu.
    $line = preg_replace("/^\S*/", "", $line); // Vyjmuti operandu z radku.
    return implode($operand);
}

/** Funkce zpracuje konec radku za operandy.
 * @param $line - radek.
 */
function EndOfOperands($line){
    $line = trim($line, " \t");
    if($line != null)
        ErrorOutput(4);
}

/** Funkce na rozpoznavani typu operandu.
 * @param $operand - zpracovavany operand.
 * @return string -  typ operandu.
 */
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
}

/** Funkce kontrolujici promenne typu bool.
 * @param $operand
 */
function BoolChecker($operand){
    $operand = substr($operand,5);
    if(!preg_match("/^(true|false)$/", $operand,$tmp ))
        ErrorOutput(5);
}

/** /** Funkce kontrolujici promenne typu string.
 * @param $operand
 */
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

/** Funkce generujici XML vystup.
 * @param $instructions - pole instrukci.
 */
function XmlOutput($instructions){
    $xml = new DOMDocument('1.0',"UTF-8");
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

/** Funkce na zpracovani rozsireni STATP
 * @param $flags
 */
function Statistics($flags){
    $content="";
    if($flags->flagLoc and $flags->flagComment){
        if($flags->LocOrderArgument < $flags->CommentOrderArgument) {
            $content = $flags->LocNumber;
            $content = $content . "\n";
            $content = $content . $flags->CommentNumber;
            $content = $content . "\n";
        }else{
            $content = $flags->CommentNumber;
            $content = $content . "\n";
            $content = $content . $flags->LocNumber;
            $content = $content . "\n";
        }
    }elseif($flags->flagLoc){
        if($flags->LocOrderArgument < $flags->CommentOrderArgument) {
            $content = $flags->LocNumber;
            $content = $content . "\n";
        }
    }elseif($flags->flagComment){
        if($flags->LocOrderArgument > $flags->CommentOrderArgument) {
            $content = $flags->CommentNumber;
            $content = $content . "\n";
        }
    }
    $myfile = fopen($flags->file, "w") or ErrorOutput(12);
    fwrite($myfile, $content);
    fclose($myfile);

}

/**
 * Trida instrukci.
 */
class InstructionClass{
    public $order; // Poradi instrukce.
    public $opcode; // Hodnota operacniho kodu.
    public $arguments = array(); // Argumenty instrukce.
    public $types = array(); // Typy argumentu instrukce.
}

/**
 * Trida pouzitych parametru.
 */
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

// Zpracovani prvniho radku.
foreach ($inputFile as $line){
    // Odstraneni mezer a tabulatoru na zacatku radku.
    $line = trim($line," \t");
    // Odstraneni komentare.
    if(strpos($line, "#") !== false) {
        $line = preg_replace("/#.*/", "", $line);
    }
    // Nalezeni prvniho radku.
    if (strtoupper($line) == ".IPPCODE18") {
        array_shift($inputFile);
        break;
    }
    // Pokud je pred .IPPCODE18 nebily znak.
    if(!empty($line))
        ErrorOutput(21);
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
        case "DPRINT":
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
exit(0);