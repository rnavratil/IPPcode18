<?php

class ExitCodes{
    public $sourcePatch;
    public $scriptCode;
    public $referenceCode;
}

$sources = array();

$directory = new \RecursiveDirectoryIterator('./tests_for_parser/tests');
$iterator = new \RecursiveIteratorIterator($directory);
$files = array();
foreach ($iterator as $info) {
  if(preg_match("/.src/", $info->getFilename(), $tmp)) {
      array_push($sources, $info);
  }
}
$succesTest = array();
$failedTest = array();
$failedExitCodes = array();

$index = 0;
foreach ($sources as $sourcePatch) {
    $index++;
    // Zjisteni referencniho souboru.
    $referencePatch =  preg_replace("/.src/", ".out", $sourcePatch);
    // Zjisteni exit code.
    $codePatch =  preg_replace("/.src/", ".err", $sourcePatch);

    // Vystup z parse.php.
    $scriptOutput = shell_exec('php5.6 parse.php < '.$sourcePatch);
   // zjisteni Exit code.
    exec('php5.6 parse.php < '.$sourcePatch, $tmp, $scriptExitCode);

    // Referencni vystup.
    $referenceFile = fopen($referencePatch, "r");
    $referenceOutput = fread($referenceFile, filesize($referencePatch));
    fclose($referenceFile);
    // Exit code.
    $referenceExitCodeFile = fopen($codePatch , "r");
    $referenceExitCode = fread($referenceExitCodeFile, filesize($codePatch ));
    fclose($referenceExitCodeFile);
    $referenceExitCode = trim($referenceExitCode, " \n");
    $referenceExitCode = intval($referenceExitCode);


    if($referenceExitCode == $scriptExitCode) {
        //diff referenceOutput a scriptOutput
        if ($referenceOutput == $scriptOutput) {
            array_push($succesTest, $sourcePatch);
        } else {
            array_push($failedTest, $sourcePatch);

            $myfile = fopen("./tests_for_parser/fails/fail" . $index, "w") or die("Unable to open file!");
            fwrite($myfile, $scriptOutput);
            fclose($myfile);

        }
    }
    else{
        $obj = new ExitCodes();
        $obj->sourcePatch=$sourcePatch;
        $obj->referenceCode=$referenceExitCode;
        $obj->scriptCode=$scriptExitCode;
        array_push($failedExitCodes,$obj);
    }
}
echo "-------------------Succes----------------------------\n";
foreach ($succesTest as $test){
    echo $test."\n";
}
echo "--------------Exit code Failed-----------------------\n";
foreach ($failedExitCodes as $test2){
    echo $test2->sourcePatch." R:".$test2->referenceCode." S:".$test2->scriptCode."\n";

}

echo "-------------------Failed-----------------------------\n";
$index = 0;
foreach ($failedTest as $test1){
    $index++;
    echo $test1." ---> fail$index\n";
}


