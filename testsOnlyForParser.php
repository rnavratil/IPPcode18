<?php
$sources = array();

$directory = new \RecursiveDirectoryIterator('./tests_for_parser/tests');
$iterator = new \RecursiveIteratorIterator($directory);
$files = array();
foreach ($iterator as $info) {
  if(preg_match("/.src/", $info->getFilename(), $tmp)) {
      array_push($sources, $info);
      $a = "ju";
  }
}
$succesTest = array();
$failedTest = array();

$index = 0;
foreach ($sources as $sourcePatch) {
    $index++;
    // Zjisteni referencniho souboru
    $referencePatch =  preg_replace("/.src/", ".out", $sourcePatch);
    // Vystup z parse.php.
    $scriptOutput = shell_exec('php5.6 parse.php < '.$sourcePatch);
    // Referencni vystup.
    $referenceFile = fopen($referencePatch, "r");
    $referenceOutput = fread($referenceFile, filesize($referencePatch));
    fclose($referenceFile);
    //diff referenceOutput a scriptOutput
    if($referenceOutput == $scriptOutput) {
        array_push($succesTest, $sourcePatch);
    }else{
        array_push($failedTest, $sourcePatch);

        $myfile = fopen("./tests_for_parser/fails/fail".$index, "w") or die("Unable to open file!");
        fwrite($myfile, $scriptOutput);
        fclose($myfile);

    }
}
echo "-------------------Succes---------------\n";
foreach ($succesTest as $test){
    echo $test."\n";
}
echo "-------------------Failed--------------------\n";
$index = 0;
foreach ($failedTest as $test1){
    $index++;
    echo $test1." ---> fail$index\n";
}


