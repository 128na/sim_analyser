<?php
/**
 * 静的呼び出しからのチェーンメソットを安心サポート
 * @author  128Na
 * @version 2016.Jul.10
 * @since   PHP5.4
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
trait Loadable {
    public static function load($filename){
        return new static($filename);
    }
}

/**
 * zipped XMLを解凍する
 * @author  128Na
 * @version 2016.Jul.10
 * @since   PHP5.4
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
class Sim_XML_Zipped {
    use Loadable;

    private $file_from;
    private $file_to;

    /**
     * Sim_XML_Zipped constructor.
     * @param $filename
     */
    public function __construct($filename)
    {
        $this->file_from = gzopen($filename, 'r');

        if (!$this->file_from) {
            Log::error('Cannot read sve file. Did you saved file as "xml_zipped" format?');
        }
    }

    public function __destruct(){
        bzclose( $this->file_from);
        fclose( $this->file_to);
    }

    /**
     * 指定ファイルに保存する
     * @param $filename
     * @return bool
     */
    public function extract($filename = 'tmp') {
        $this->file_to = fopen($filename, 'w');

        if (!$this->file_to){
            Log::error('Cannot write file! : '.$filename);
        }

        while (!gzeof($this->file_from)) {
            fwrite($this->file_to, gzread($this->file_from, 8192));
        }
        Log::info('Extracted file to : '.$filename);
    }
}


/**
 * bz2 XMLを解凍する
 * @author  128Na
 * @version 2016.Jul.10
 * @since   PHP5.4
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
class Sim_XML_Bz2 {
    use Loadable;

    private $file_from;
    private $file_to;

    public function __construct($filename){
        $this->file_from = bzopen($filename, 'r');
        if (!$this->file_from) {
            Log::error('Cannot read sve file. Did you saved file as "xml_bz2" format?');
        }
    }

    public function __destruct(){
        bzclose( $this->file_from);
        fclose( $this->file_to);
    }

    /**
     * 指定ファイルに保存する
     * @param $filename
     * @return bool
     */
    public function extract($filename = 'tmp') {
        $this->file_to = fopen($filename, 'w');

        if (! $this->file_to){
            Log::error('Cannot write file! : '.$filename);
        }

        while ($data = bzread($this->file_from)) {
            fwrite( $this->file_to, $data);
        }
        Log::info('Extracted file to : '.$filename);
    }
}