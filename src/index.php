<?php
define('DS', DIRECTORY_SEPARATOR);

require_once 'lib/args.php';
require_once 'lib/log.php';
require_once 'Sim_Analyser.php';

$file = Args::get('-f');
if (is_null($file)) {
    echo "sim_analser -f file.sve [-o output.jsonp --as-json]\n";
    exit;
}

$output = Args::get('-o');
if (is_null($output)) {
    $output = 'result.jsonp';
}

// TODO:extract zipfile
if (Args::has('--zipped')) {
}

$r = new Sim_Analyser($file);

Log::info($r->get_app(), true);

$r->execute();

// output JSONP file
if (!Args::has('--as-json')) {
    $str = "callback({$r})";
}
// TODO:csv export
elseif (Args::has('--as-csv')) {
}
else {
    $str = "{$r}";
}

if (@file_put_contents($output, $str)){
    Log::info("result saved -> {$output}", true);
}
else {
    Log::error("result cannot saved -> {$output}");
}
