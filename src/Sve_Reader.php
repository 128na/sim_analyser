<?php
/**
 * セーブデータの解凍・読み込み担当
 * @author  128Na
 * @version 2016.Jul.14
 * @since   PHP5.6
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
class Sve_Reader extends Sim_Analyser_Api{
    private $file;
    private $reader;
    public function __construct($filename){
        $this->open($filename);
    }

    public function __destruct() {
        $this->close();
    }

    protected function close(){
        $this->get_reader()->close();
        if(is_resource($this->file)) {
            fclose($this->file);
        }
    }


    private function open($filename) {

        $mime = mime_content_type($filename);
        switch ($mime) {
            case 'application/x-bzip2':
                if (!$this->bz_installed()) {
                    throw new RuntimeException('need bz2 support!');
                }
                $this->decompress_bz2($filename);
                break;

            case 'application/x-gzip':
                if (!$this->gz_installed()) {
                    throw new RuntimeException('need zlib support');
                }
                $this->decompress_gz($filename);
                break;

            case 'application/xml':
                break;

            default :
                 throw new NonSupportVersionException();
        }

        //既にファイルオープンで来ている場合→gz,bz2解凍された場合はポインタを先頭へ戻す。
        if (is_resource($this->file)) {
            rewind($this->file);
        }
        else {
            $this->file = fopen($filename, 'r');
        }

        $this->init_xml();
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function decompress_gz($filename) {
        $file_from = gzopen($filename, 'r');

        if (is_resource($file_from) ) {
            $file_to = tmpfile();

            while (!gzeof($file_from)) {
                fwrite($file_to, gzread($file_from, 8192));
            }

            $this->file = $file_to;
            gzclose($file_from);
            return true;
        }
        return false;
    }

    /**
     * @param string $filename
     * @return bool
     */
    private function decompress_bz2($filename){
        $file_from = bzopen($filename, 'r');

        if (is_resource($file_from)) {
            $file_to = tmpfile();

            while ($data = bzread($file_from, 8192)) {
                fwrite($file_to, $data);
            }

            $this->file = $file_to;
            gzclose($file_from);
            return true;
        }
        return false;
    }


    protected function bz_installed() {
        return function_exists('bzopen');
    }
    protected function gz_installed() {
        return function_exists('gzopen');
    }

    private function init_xml(){
        $filename = stream_get_meta_data($this->file)['uri'];
        $this->reader = new XMLReader();
        $this->reader->open($filename);
    }



    /**
     * XMLReader インスタンスを返す
     * @return XMLReader
     */
    protected function get_reader() {
        return $this->reader;
    }

    /**
     * 次の行のXMLデータを読み取る
     * @return bool 読み取りの成否
     */
    protected function read() {
        return @$this->get_reader()->read();
    }

    /**
     * XML要素の値を読み取る
     * @return string
     */
    protected function read_value() {
        return $this->get_reader()->value;
    }


    /**
     * 現在のノードが要素か
     * @return bool
     */
    protected function is_element() {
        return $this->get_reader()->nodeType === XMLReader::ELEMENT;
    }

    /**
     * 現在のノードがCDATAか
     * @return bool
     */
    protected function is_cdata() {
        return $this->get_reader()->nodeType === XMLReader::CDATA;
    }

    /**
     * 指定された名前の要素の開始か
     * @param string $name element name
     * @return bool
     */
    protected function is_name_open($name) {
        return ($this->get_reader()->nodeType !== XMLReader::END_ELEMENT) && ($this->reader->localName === $name);
    }

    /**
     * 指定された名前の要素の終了か
     * @param string $name element name
     * @return bool
     */
    protected function is_name_close($name) {
        return ($this->get_reader()->nodeType === XMLReader::END_ELEMENT) && ($this->reader->localName === $name);
    }

    /**
     * @param string $attr
     * @return string
     */
    protected function getAttribute($attr) {
        return $this->get_reader()->getAttribute($attr);
    }

    /**
     * XML子要素を配列で返す
     * @return array XML strin garray
     */
    protected function get_children_arr() {
        return explode("\n", $this->get_children_str());
    }

    /**
     * XML子要素の文字列を返す
     * @return string XML string
     */
    protected function get_children_str() {
        return $this->get_reader()->readInnerXml();
    }

}