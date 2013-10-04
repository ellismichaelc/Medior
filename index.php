<?
// MEDIOR
// This script will eventually serve the
// front end and the back end of the script.

ob_start();
if(!empty($_SERVER['HTTP_HOST'])) echo "<pre>";

// Don't let me time out!
set_time_limit(0);

function __autoload($class_name) {
    include "lib/" . $class_name . '.php';
}

// Import all iMDB library classes
$libDir  = "./lib/imdb/";
$openDir = openDir($libDir);
while($file = readDir($openDir)) {
    if(substr($file, 0, 5) != "imdb_" || !stristr($file, ".class.php")) continue;
    require_once $libDir . $file;
}


// Run it!
$medior = new Medior();
$medior->start();

ob_end_flush();
?>