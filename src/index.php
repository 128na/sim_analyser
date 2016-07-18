<?php
define('DS', DIRECTORY_SEPARATOR);

require_once 'lib/args.php';
require_once 'lib/log.php';
require_once 'sim_exceptions.php';
require_once 'Sim_Analyser_Api.php';
require_once 'Sve_Reader.php';
require_once 'Sim_Analyser.php';

//入力ファイルが指定されていなければあきらめる
$file = Args::get('-f');
if (is_null($file)) {
    $msg = <<<EOD
Usage
 php sim_analyser.phar -f file.sve [-o output_file [--as-json|--as-csv] [--sjis]]

I/O setting
 -f file.sve\t: input filename. Support format : xml, xml_zipped, xml_bz2.
 -o output\t: output filename (default name is result.xxx. Extension is depends on export format. ).

export format (default jsonp)
 --as-json\t: export as json format.
 --as-csv\t: export as csv format.

charset (default:UTF-8)
 --sjis\t\t: convert text to sjis.

EOD;
    echo $msg;
    exit;
}


$analyser = new Sim_Analyser($file);

Log::info($analyser->get_app(), true);
Log::info('execute analyser', true);
$analyser->execute();
Log::info('executed', true);


//出力形式・ファイル名の指定
$output = Args::get('-o');

if (Args::has('--as-json')) {   // json
    $data = $analyser->get_data_by_json();
    $output = $output ?: 'result.json';
}
elseif (Args::has('--as-csv')) {    // csv
    $data = $analyser->get_data_by_csv();
    $output = $output ?: 'result.csv';
}
else {  // jsonp
    $data = "callback({$analyser->get_data_by_json()})";
    $output = $output ?: 'result.jsonp';
}


if (Args::has('--sjis')) {    // convert to sjis
    Log::info("data convert to SJIS from UTF-8", true);
    $data = mb_convert_encoding($data, 'SJIS', 'UTF-8');
}
// file output
if (@file_put_contents($output, $data)){
    Log::info("result saved -> {$output}", true);
}
else {
    Log::error("result cannot saved -> {$output}");
}

