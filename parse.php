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

// Osetreni vstupnich parametru.
Params($argc, $argv);

// Nacteni vstupu do pole.
$inputFile = explode(PHP_EOL,file_get_contents("php://stdin"));

// Osetreni a zpracovani prvniho radku.
if (array_shift($inputFile) != ".IPPcode18")
    ErrorOutput(21);

// Zpracování instrukcí.
foreach ($inputFile as $row){
    echo $row;
    echo "b";
}