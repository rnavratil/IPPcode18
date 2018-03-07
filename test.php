<?php

/**
 * Class ParameterFlags
 * Trida pouzitych parametru skriptu.
 */
class ParameterFlags{
    public $flagRecursive = false; // Testy se budou hledat rekurzivne ve vsech podadresarich.
    public $flagDirectory = false; // Testy se budou hledat v zadanem adresari.
    public $flagParse = false; // Soubor se skriptem parseru.
    public $flagInterpret = false; // Soubor se skriptem interpretu.

    public $Directory; // Adresar pro testovani.
    public $Parse; // Soubor se skriptem parseru.
    public $Interpret; // Soubor se skriptem interpretu.
}
// Objekt obsahuje pouzite parametry skriptu.
$flags = new ParameterFlags();

class ReferenceTests{
    public $in; // Vstup pro interpret v jazyce XML.
    public $out; // Vystup z interpretu.
    public $src; // Zdrojovy kod v jazyce IPPcode18.
    public $rc; // Prvni navratovy kod.
}

$refTest = new ReferenceTests();

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
            $flag->Directory = substr( $parameters[$i], 12 ); // Cesta k souboru.

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
    if(!$flag->flagDirectory)
        $flag->Directory = "."; //TODO pwd

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
            fwrite(STDERR,"10"); // TODO
            exit(10);
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

    }else{ // Testy se budou hledat v dane slozce slozce.
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
 */
function GenerateMissingFiles($sources, $flags){
    if(!$flags->flagRecursive){
        foreach ($sources as $src) {
            // Generovani '.in' souboru.
            $inFile = substr($src, 0, -3) . "in"; //  Zjisteni nazvu souboru '.in'.
            if (!file_exists($flags->Directory.$inFile)) { // Vytvoreni souboru, pokud neexistuje.
                $myfile = fopen($flags->Directory.$inFile, "w") or exit(12);
                fwrite($myfile, '');
                fclose($myfile);
            }
            // Generovani '.out' souboru.
            $outFile = substr($src, 0, -3) . "out"; //  Zjisteni nazvu souboru '.out'.
            if (!file_exists($flags->Directory.$outFile)) { // Vytvoreni souboru, pokud neexistuje.
                $myfile = fopen($flags->Directory.$outFile, "w") or exit(12);
                fwrite($myfile, '');
                fclose($myfile);
            }
            // Generovani '.rc' souboru.
            $rcFile = substr($src, 0, -3) . "rc"; //  Zjisteni nazvu souboru '.out'.
            if (!file_exists($flags->Directory.$rcFile)) { // Vytvoreni souboru, pokud neexistuje.
                $myfile = fopen($flags->Directory.$rcFile, "w") or exit(12);
                fwrite($myfile, '0');
                fclose($myfile);
            }
        }
    }else {
        foreach ($sources as $src) {
            // Generovani '.in' souboru.
            $inFile = substr($src, 0, -3) . "in"; //  Zjisteni nazvu souboru '.in'.
            if (!file_exists($inFile)) { // Vytvoreni souboru, pokud neexistuje.
                $myfile = fopen($inFile, "w") or exit(12);
                fwrite($myfile, '');
                fclose($myfile);
            }
            // Generovani '.out' souboru.
            $outFile = substr($src, 0, -3) . "out"; //  Zjisteni nazvu souboru '.out'.
            if (!file_exists($outFile)) { // Vytvoreni souboru, pokud neexistuje.
                $myfile = fopen($outFile, "w") or exit(12);
                fwrite($myfile, '');
                fclose($myfile);
            }
            // Generovani '.rc' souboru.
            $rcFile = substr($src, 0, -3) . "rc"; //  Zjisteni nazvu souboru '.out'.
            if (!file_exists($rcFile)) { // Vytvoreni souboru, pokud neexistuje.
                $myfile = fopen($rcFile, "w") or exit(12);
                fwrite($myfile, '0');
                fclose($myfile);
            }
        }
    }
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
GenerateMissingFiles($sources, $flags);

//TODO nacteni testu
//TODO nacteni parse.php se vstupem .src
//TODO porovnani navratove hodnoty parse.php a .rc
//TODO ulozeni vysledku

//TODO nacteni interpret.py se vysledkem parse.php a in seslu na stdins
//TODO porovnani .out a vysledku z interpret.py
//TODO ulozeni vysledku

//TODO generovani HTML.


echo "Moskva";
return 0;