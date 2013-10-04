<? ob_start(); ?>
<? if(!empty($_SERVER['HTTP_HOST'])) echo "<pre>"; ?>
<?
set_time_limit(0);
$file = "D:/Media/Shows/Archer/Season 1/[01x01] Mole Hunt.avi";
$times_to_run = 3;
$algos = hash_algos();
$results = array();

showProgress("Caching:");
foreach($algos as $algo) {
    if($algo == "md2") continue;
    showProgress(" " . $algo);
    hash_file($algo, $file);
        
}

showProgress("\n\n");

foreach($algos as $algo) {
    if($algo == "md2") continue;
    
    showProgress(str_pad(strtoupper($algo), 10, " ", STR_PAD_RIGHT));
    
    for($i=0; $i<$times_to_run; $i++) {
        $t1 = microtime(true);
        hash_file($algo, $file);
        $t2 = microtime(true);
        
        $diff = $t2 - $t1;
        
        $results[$algo] += $diff;
        $diff = number_format($diff, 6);
        showProgress("\t$diff");
    }
    
    $avg = number_format($results[$algo] / $times_to_run,6);
    showProgress("\tAVG: [$avg]\n");
}

foreach($results as $algo=>$time) {
    $results[$algo] = $time / $times_to_run;
}

asort($results);

$data = "";
foreach($results as $algo=>$time) {
    $data .= "<li><b>$algo</b> => <i>" . $time . "</i>";
}

fwrite(fopen("results.html", "w"), $data);

function showProgress($text) {
    print "$text";
    ob_flush();
    flush();
    ob_flush();
    flush();
    ob_flush();
    flush();
    ob_flush();
    flush();
    ob_flush();
    flush();
}

ob_end_flush();
?>