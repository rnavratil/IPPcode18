<?php

/**
 * Class ParameterFlagsClass
 * Trida pouzitych parametru skriptu.
 */
class ParameterFlagsClass{
    public $flagRecursive = false; // Testy se budou hledat rekurzivne ve vsech podadresarich.
    public $flagDirectory = false; // Testy se budou hledat v zadanem adresari.
    public $flagParse = false; // Soubor se skriptem parseru.
    public $flagInterpret = false; // Soubor se skriptem interpretu.

    public $Directory; // Adresar pro testovani.
    public $Parse; // Soubor se skriptem parseru.
    public $Interpret; // Soubor se skriptem interpretu.
}
$flags = new ParameterFlagsClass(); // Objekt obsahuje pouzite parametry skriptu.

/**
 * Class ReferenceTestsClass
 * Troda obsahuje veschny soubory jednoho testu.
 */
class ReferenceTestsClass{
    public $in; // Stdin pro interpret v jazyce XML.
    public $out; // Vystup z interpretu.
    public $src; // Zdrojovy kod v jazyce IPPcode18.
    public $rc; // Prvni navratovy kod.

    public $parseOutput; // Stdout z 'parse.php'.
    public $parseReturnCode; // Navratova hodnota z 'parse.php'.
    public $interpretOutput; // Stdout z 'interpret.py'.
    public $interpretReturnCode; // Navratova hodnota z 'intrpret.py'.
}

/** Funkce na zpracovani parametru skriptu.
 * @param $count - argc. Pocet parametru skriptu.
 * @param $parameters - argv. Parametry skriptu.
 * @param $flag - objekt s parametry.
 */
function Params($count,$parameters,$flag){
    for($i = 1; $i < $count; $i++){
        if(($parameters[$i] == "--help") and ($count == 2)) {
            HelpPrint(); // Volani funkce pro vypis napovedy.
            exit(0);
        }elseif (($parameters[$i] == "--recursive") and !($flag->flagRecursive)){
            $flag->flagRecursive = true;

        }elseif (preg_match("/^--directory=.+$/", $parameters[$i],$tmp ) and !($flag->flagDirectory)) {
            $flag->flagDirectory =  true;
            $correctFolder = substr( $parameters[$i], 12 ); // Cesta k souboru. TODO more na konci musi byt /
            if(substr($correctFolder,-1) != "/")
                $correctFolder = $correctFolder."/";
            $flag->Directory = $correctFolder;

        }elseif (preg_match("/^--parse-script=.+$/", $parameters[$i],$tmp ) and !($flag->flagParse)) {
            $flag->flagParse = true;
            $flag->Parse = substr( $parameters[$i], 15 ); // Cesta k souboru.

        }elseif (preg_match("/^--int-script=.+$/", $parameters[$i],$tmp ) and !($flag->flagInterpret)) {
            $flag->flagInterpret = true;
            $flag->Interpret = substr( $parameters[$i], 13 ); // Cesta k souboru.

        }else
            ErrorOutput(10);
    }

    // Zpracovani implicitnich hodnot.
    if(!$flag->flagParse)
        $flag->Parse = "parse.php";
    if(!$flag->flagInterpret)
        $flag->Interpret = "interpret.py";
    if(!$flag->flagDirectory) {
        $actualFolder = shell_exec('pwd');
        $actualFolder = substr($actualFolder, 0,-1)."/";
        $flag->Directory = $actualFolder;
    }

}

/** Funkce na ukonceni skriptu s navratovou hodnotou jinou nez 0.
 * @param $number - cislo chyby.
 */
function ErrorOutput($number){
    switch ($number){
        case 21: // Lexikální nebo syntaktická chyba.
            fwrite(STDERR,"21"); // TODO
            exit(21);

        case 10: // Chyba vstupnich parametru.
            fwrite(STDERR,"Chyba"); // TODO
            exit(10);

        case 12: // Chyba vstupnich parametru.
            fwrite(STDERR,"12"); // TODO
            exit(12);
    }
}

/** Funkce na zpracovani '.src' souboru testu.
 * @param $flags - objekt se zvolenymi parametry skriptu.
 * @return array - pole zdrojovych souboru testu.
 */
function SourcesFile($flags){
    $sources = array(); // Pole zdrojovych souboru
    if($flags->flagRecursive){ // Testy se budou v adresari hledat rekurzivne ve vsech podadresarich.
        $directory = new \RecursiveDirectoryIterator($flags->Directory);
        $iterator = new \RecursiveIteratorIterator($directory);
        foreach ($iterator as $info) { // Trideni souboru v adresari.
            if(preg_match("/.src$/", $info->getFilename(), $tmp)) {
                array_push($sources, $info->getPathname());
            }
        }

    }else{ // Testy se budou hledat v dane slozce.
        $testDirectory = scandir($flags->Directory);
        foreach ($testDirectory as $info) { // Trideni souboru v adresari.
            if(preg_match("/.src$/", $info, $tmp)) {
                array_push($sources, $info);
            }
        }
    }
    return $sources;
}

/** Funkce vygeneruje chybejici soubory.
 * @param $sources - '.src' soubory testu.
 * @param $flags - objekt se zvolenymi parametry skriptu.
 * @return array - $referenceTest. Pole testu.
 */
function GenerateMissingFiles($sources, $flags){
    $referenceTests = array(); // Pole referencnich testu.

    foreach ($sources as $src) {
        // Zjisteni nazvu ostatnich souboru testu.
        if($flags->flagRecursive) { // Rekurzivni hledani v adresarich.
            $srcFile = $src;
            $inFile = substr($src, 0, -3) . "in";
            $outFile = substr($src, 0, -3) . "out";
            $rcFile = substr($src, 0, -3) . "rc";
        }else {
            $srcFile = $flags->Directory . $src;
            $inFile = $flags->Directory . substr($src, 0, -3) . "in";
            $outFile = $flags->Directory . substr($src, 0, -3) . "out";
            $rcFile = $flags->Directory . substr($src, 0, -3) . "rc";
        }
        // Ukladani vsech souboru daneho testu do objektu.
        $refTest = new ReferenceTestsClass(); // Objekt obsahuje soubory testu.
        $refTest->src = $srcFile;
        $refTest->in = $inFile;
        $refTest->out = $outFile;
        $refTest->rc = $rcFile;

        // Generovani '.in' souboru.
        if (!file_exists($inFile)) { // Vytvoreni souboru, pokud neexistuje.
            $myfile = fopen($inFile, "w") or exit(12);
            fwrite($myfile, '');
            fclose($myfile);
        }
        // Generovani '.out' souboru.
        if (!file_exists($outFile)) { // Vytvoreni souboru, pokud neexistuje.
            $myfile = fopen($outFile, "w") or exit(12);
            fwrite($myfile, '');
            fclose($myfile);
        }
        // Generovani '.rc' souboru.
        if (!file_exists($rcFile)) { // Vytvoreni souboru, pokud neexistuje.
            $myfile = fopen($rcFile, "w") or exit(12);
            fwrite($myfile, '0');
            fclose($myfile);
        }
        array_push($referenceTests,$refTest); // Vlozeni objektu do pole objektu testu.
    }
    return $referenceTests;
}

/** Funkce testuje soubor 'parse.php'.
 * @param $referenceTests - pole objektu referencnich testu.
 * @param $flags - objekt se zvolenymi parametry skriptu.
 */
function ParseProcess($referenceTests, $flags){
    foreach ($referenceTests as $test){
        // Zjisteni navratove hodnoty testu z parse.php.
        $parseExitCode = 44;
        exec('php5.6 ' . $flags->Parse . ' < '.$test->src, $tmp, $parseExitCode);
        $test->parseReturnCode =$parseExitCode; // Ulozeni hodnoty do objektu.

        if($parseExitCode == 0) {
            $test->parseOutput = shell_exec('php5.6 ' . $flags->Parse . ' < ' . $test->src); // Vystup z parse.php.
        }
        /*else{
            $referenceReturnCode = file_get_contents($test->rc, FILE_USE_INCLUDE_PATH);
            if($referenceReturnCode === false)
                ErrorOutput(12);
            continue;
        }*/
    }
}


function InterpretProcess($referenceTests, $flags){
    foreach ($referenceTests as $test){
        if($test->parseReturnCode != "0") // Preskoci testy, ktere se nedostali za 'parse.php'.
            continue;
        // Vytvoreni jedinecneho nazvu pro docasny soubor.
        $filename = './TMPFILEaqapoldea';
        while(file_exists($filename)){
           $filename = $filename . "x";
       }
        // Vytvoreni docasneho souboru.
        $myfile = fopen($filename, "w") or die("Unable to open file!");
        fwrite($myfile, $test->parseOutput);
        fclose($myfile);
        // Zjisteni navratove hodnoty z python.py
        //exec ('python3.6 interpret.py', $tmp, $interpretExitCode);
        exec('python3.6 '.$flags->Interpret.' --source='.$filename.' < '.$test->in, $tmp, $interpretExitCode);
        $test->parseReturnCode =$interpretExitCode; // Ulozeni navratove hodnoty z interpret.py.
        // Zpracovani vystupu z interpret.py.
        if($interpretExitCode == 0) {
            $test->interpretOutput = shell_exec('python3.6 ' . $flags->Interpret . ' --source=' . $filename . ' < ' . $test->in);
        }
        // Mazani docasneho souboru.
        unlink($filename);
    }
}

function TestSort($referenceTests, &$succesTests, &$failedTests){
    // Sablona pro HTML tagy tabulky.
    $trStart = "<tr".">\n";
    $tdStart = "<td".">";
    $tdEnd = "</td".">\n";
    $trEnd = "</tr".">\n";
    // Pole roztrizenych testu.
    $failedTests = array();
    $succesTests = array();

    // Proces vyhodnoceni a trideni testu.
    foreach ($referenceTests as $test){
        $htmlTest = $trStart; // Promenna pro radek tabulky.
        $htmlTest = $htmlTest. $tdStart.substr($test->src,0,-4).$tdEnd; // Pole Identification.

        if($test->parseReturnCode != 0){ // Navratova hodnota z parse.php neni roven nule.
            $rcCode = file_get_contents($test->rc, FILE_USE_INCLUDE_PATH);
            $htmlTest = $htmlTest.$tdStart.$rcCode.$tdEnd;
            $htmlTest = $htmlTest.$tdStart.$test->parseReturnCode.$tdEnd.$trEnd;
            if($rcCode == $test->parseReturnCode ) // Porovnani navratove hodnoty ze skriptu s hodntou v '.rc'.
                array_push($succesTests,$htmlTest);
            else
                array_push($failedTests,$htmlTest);
            continue;
        }else{ // Navratova hodnota z parse.php je nula.

        }
    }
}

function HTMLgenerate($succesTests,$failedTests){
    // Sablona pro kaskadovy styl.
    $style = "<style".">";
    $style = $style. "table, th, td { border: 1px solid black; border-collapse: collapse; }\n";
    $style = $style. "th, td { padding: 5px; text-align: left; }\n";
    $style = $style. "#cntr{ text-align: center; background-color: #A9A9A9; }\n";
    $style = $style. "#cntr1{ text-align: center; background-color: #D3D3D3; }\n";
    $style = $style. "#cntr2{ text-align: center; background-color: green; }\n";
    $style = $style. "#cntr3{ text-align: center; background-color: red; }\n";
    $style = $style. "</style".">\n";

    // Sablona pro tabulku.
    $table1 = "<table style=\"width:100%\"".">\n"."<tr".">\n<th id=\"cntr";
    $wordFailed = "3\" colspan=\"7\" >Failed</th".">";
    $wordSucces = "2\" colspan=\"7\" >Succes</th".">";
    $table2 = " \n</tr".">\n<tr".">\n<th id=\"cntr\" rowspan=\"2\"".">Identification</th".">\n";
    $table2 = $table2."<th id=\"cntr\" colspan=\"2\"".">parse.php</th".">\n";
    $table2 = $table2."<th id=\"cntr\" colspan=\"4\"".">interpret.py</th".">\n</tr".">\n";

    $table2 = $table2."<tr".">\n"."<th id=\"cntr1\"".">Reference code</th".">\n";
    $table2 = $table2."<th id=\"cntr1\"".">Script code</th".">\n";
    $table2 = $table2."<th id=\"cntr1\"".">Reference code</th".">\n";
    $table2 = $table2."<th id=\"cntr1\"".">Script code</th".">\n";
    $table2 = $table2."<th id=\"cntr1\"".">Reference output</th".">\n";
    $table2 = $table2."<th id=\"cntr1\"".">Script output</th".">\n</tr".">\n";

    // Vysledna HTML5 sablona.
    $html = "<!DOCTYPE html".">\n<html".">\n"."<head".">\n".$style."<meta charset=\"UTF-8\"".">\n"."<title";
    $html = $html.">IPPcode2018 tests</title".">\n"."</head".">\n"."<body".">\n";
        // Tabulka 'Failed'.
        $html = $html.$table1.$wordFailed.$table2;
        foreach ($failedTests as $succes) // Generovani radku tabulky.
            $html = $html.$succes;
        $html = $html."</table".">\n<br".">"; //Konec tabulky

        // Tabulka 'Succes'.
        $html = $html.$table1.$wordSucces.$table2;
        foreach ($succesTests as $succes) // Generovani radku tabulky.
            $html = $html.$succes;
        $html = $html."</table".">\n";  // Konec tabulky.

    // Pokracovani HTML5 sablony.
    $html = $html."</body".">\n"."</html".">\n";
    echo $html;
}

/**
 * Funkce pro vypis napovedy.
 */
function HelpPrint(){
    echo "Projekt do predmetu IPP. Vytvoril Rostislav Navratil v roce 2018.\n\n";
    echo "Skript pro automaticke testovani postupne aplikace 'parse.php'a 'interpret.py'. Skript projde";
    echo " zadany adresar s testy a vyuzije je pro automaticke otestovani spravne funkcnosti obou predchozich ";
    echo "programu vcetne vygenerovani prehledneho souhrnu v HTML 5 do standardniho vystupu. ";
    echo "\nZakladni parametry bez rozsireni:\n";
    echo " --help  - vypise napovedu. Nelze ho kombinovat s jinymi parametry.\n";
    echo " --direcotry=\<path\>  - testy se budou hledat v zadanem adresari \<path\>.\n";
    echo " --recursive  - Testy se budou hledat rekurzivne ve vsech podadresarich.\n";
    echo " --parse-script=\<file\>  -  jako parser se pouzije \<file\> =.\n";
    echo " --int-script=\<file\>  - jako interpret se pouzije \<file\>.\n";
    echo " V pripade vynechaji parametru '--parse-script' a '--int-script' se pouziji implicinti soubory ulozene ";
    echo "v aktualnim adresari. V pripade vynechani parametru '--directory' se bude prochazet aktualni adresar.";
}

// Volani funkce pro zpracovani parametru skriptu.
Params($argc,$argv,$flags);
// Zpracovani zdrojovych souboru z prislusnych adresaru.
$sources = SourcesFile($flags);
// Generovani chybejicich souboru testu.
$referenceTests = GenerateMissingFiles($sources, $flags);
// Zpracovani skriptu 'parse.php'.
ParseProcess($referenceTests, $flags);
// Zpracovani skriptu 'interpret.py'.
InterpretProcess($referenceTests, $flags);
// Trideni testu.
TestSort($referenceTests,$succesTests,$failedTests);
// Generovani HTML5 vystupu.
HTMLgenerate($succesTests,$failedTests);

echo "Moskva";
exit(0);