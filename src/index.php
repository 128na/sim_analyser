<?php
define('DS', DIRECTORY_SEPARATOR);

require_once 'lib/args.php';
require_once 'lib/log.php';
require_once 'Sim_Analyser.php';

$file = Args::get('-f');
if (is_null($file)) {
    echo "sim_analyzer -f file.sve -o output.json\n";
    exit;
}

$output = Args::get('-o');
if (is_null($output)) {
    $output = 'result.json';
}

// TODO:extract zipfile
if (Args::has('--zipped')) {

}

$r = new Sim_Analyser($file);

Log::info($r->get_app_version(), true);

$r->execute();

if (@file_put_contents($output, $r)){
    Log::info("result saved -> {$output}", true);
}
else {
    Log::error("result cannot saved -> {$output}");
}
