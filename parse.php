<?php

function Params($count,$parameters){
    for($i = 1; $i < $count; $i++){
        if(($parameters[$i] == "--help") and ($count == 2)){
            //TODO print napoveda
            exit(0);
        }else
            ErrorOutput(10);
 }

  /*
    $options = getopt('', array("help", "stats"));

    foreach($options as $parameter => $value){

        if($parameter == "help") {
            if (count($options) != 1)
                ErrorOutput(10);
            // print napoveda
            echo "napoveda";
        }
        if($parameter == "stats"){

        }
    }
  */

}

function ErrorOutput($index){
    switch ($index){
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

// Standartní vstup.
$inputFile = file_get_contents("php://stdin");
echo $inputFile;