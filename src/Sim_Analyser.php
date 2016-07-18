<?php
    /**
 * Sim_Analyser
 * @author  128Na
 * @version 2016.Jul.15
 * @since   PHP5.6
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
class Sim_Analyser extends Sve_Reader{
    const APP_NAME = 'Simutrans save data analyser';
    const APP_VERSION = '1.3.3';

    const SUPPORT_SVE_VERSION_HIGHER = [
        0,
        120,
        0,
    ];

    //planquadrat_t 要素の個数
    private $planquadrat_count = 0;

    /**
     * Sim_Analyser constructor.
     * @param string $filename
     * @internal param string $path input file path
     */
    public function __construct($filename) {
        /** @var string $filename */
        parent::__construct($filename);
    }

    /**
     * execute analyser
     */
    public function execute() {
        $detect_simutrans = false;
        while ($this->read()) {
            if ($this->is_element()) {
                if (!$detect_simutrans && $this->is_name_open('Simutrans')){
                    $detect_simutrans = $this->read_header();
                }
                if ($detect_simutrans) {
                    if ($this->is_name_open('einstellungen_t')) {
                        $this->read_info();
                    }
                    if ($this->is_name_open('planquadrat_t')) {
                        $this->read_stations();
                    }
                    if ($this->is_name_open('haltestelle_t')) {
                        $this->read_relations();
                    }
                    if ($this->is_name_open('spieler_t')) {
                        $this->read_players();
                    }
                }
            }
        }

        if (!$detect_simutrans) {
            throw new SimutransElementNotFoundException('Cannot read file! Did you saved the file as "XML" format?');
        }

        $this->close();
        return true;
    }

    /**
     * バージョンとpakセット名を取得する
     */
    private function read_header() {
        $version = $this->getAttribute("version");
        //対応バージョンかチェック
        if ($version) {
            if($this->check_version($version)){
                $this->set_version($version);
                $this->set_pak($this->getAttribute("pak"));

                return true;
            }
            else {
                throw new NonSupportVersionException('non support version. ->'.$version);
            }
        }
        else {
            throw new NonSupportVersionException('version not found!');
        }
    }

    /**
     * マップ基本情報を取得する
     */
    private function read_info() {
        $lines = $this->get_children_arr();

        $this->set_map_x($this->trim($lines[1]));
        $this->set_map_y($this->trim($lines[14]));
        $this->set_map_no($this->trim($lines[2]));
        $this->set_map_tiles( $this->get_map_x() * $this->get_map_y());
    }

    /**
     * 駅名とその駅名が記載されている3次元座標を取得する
     */
    private function read_stations() {
        $tiles = $this->get_map_tiles();
        if ($this->planquadrat_count > $tiles){
             throw new Exception('tile count over!');
        }

        // split ground_t
        preg_match_all('/<grund_t>([\s\S]+?)<\/grund_t>/', $this->get_children_str(), $grund_t_arr);

        foreach ( $grund_t_arr[1] as $grund_t) {
            $lines = explode("\n",$grund_t);

            //station, marker,etc...
            if ($name = $this->trim($lines[4])){
                foreach ($lines as $i => $line) {
                    //gebaeude_tの一つ上のID=7なら駅、4つ下がプレイヤーID
                    if (stripos($line, '<gebaeude_t>') !== false &&
                        stripos($lines[$i-1], '<id>7</id>') !== false){
                        $coordinate = $this->n_to_xy($this->planquadrat_count);
                        $coordinate['z'] = intval($this->trim($lines[1]));

                        $this->add_station([
                            'name'        => $name,
                            'coordinates' => [$coordinate],
                            'player'      => intval($this->trim($lines[$i+4])),
                        ]);
                    }
                }
            }
        }
        $this->planquadrat_count++;
    }

    /**
     * 各駅の保有座標配列を取得し、駅情報へ組み込む
     */
    private function read_relations() {
        $coordinates = $this->get_coordinates_from_str($this->get_children_str());

        if(!$this->resolve_relations($coordinates)){
        }
    }

    /**
     * XML文字列から座標を取得する
     * @param string $str XML string
     * @return array [x => 0, y => 0, z => 0]
     */
    private function get_coordinates_from_str($str) {
        $result = [];
        preg_match_all('/<koord3d>([\s\S]+?)<\/koord3d>/', $str, $coordinates);

        foreach ( $coordinates[1] as $coordinate) {
            $xyz = explode("\n", $coordinate);
            //ダミーの(-1,-1,-1)は除外する
            if (stripos($xyz[1],'<i16>-1</i16>') === false) {
                $result[] = [
                    'x' => intval($this->trim($xyz[1])),
                    'y' => intval($this->trim($xyz[2])),
                    'z' => intval($this->trim($xyz[3])),
                ];
            }
        }
        return $result;
    }

    /**
     * 座標配列からマッチする駅を探す
     * @param array $coordinates 座標配列
     * @return bool 見つかったか
     */
    private function resolve_relations($coordinates) {
        foreach ($coordinates as $coordinate) {
            if($station = $this->get_station_by_coordinate($coordinate, 'skip_resolved')) {
                $station['coordinates'] = $coordinates;
                $this->set_station_by_coordinate($coordinate, $station);
                return true;
            }
        }
        return false;
    }

    /**
     * プレーヤーを取得する
     */
    private function read_players() {

        //way_type取得用バッファ
        $prev_text = '';

        $end_read_line = false;
        while($this->read()){
            if ($this->is_name_open('simline_t')){
                $this->read_line(intval($prev_text));
            }
            elseif ($this->is_name_close('simlinemgmt_t')) {
                $end_read_line = true;
            }
            elseif ($end_read_line && $this->is_cdata()) {
                $this->add_player($this->read_value());
            }
            elseif ($this->is_name_close('spieler_t')) {
                return;
            }

            if($this->is_element()) {
                $prev_text = $this->get_reader()->readString();
            }
        }
    }

    /**
     * 路線情報を読み取る
     * @param int $way_type way_type_id
     */
    private function read_line($way_type) {
        if($lines_str = $this->get_children_str()){

            $lines = explode("\n", $lines_str);

            if ($name = $this->trim($lines[1])) {
                $this->add_line([
                    'name'        => $name,
                    'id'          => intval($this->trim($lines[2])),
                    'way_type_id' => $way_type,
                    'player_id'   => count($this->players),
                    'coordinates' => $this->get_coordinates_from_str($lines_str),
                ]);
            }
        }
    }



    /**
     * n番目からｘｙ座標を求める
     * @param string $n
     * @return array [x,y]
     */
    private function n_to_xy($n){
        $w = $this->get_map_x();

        if ($n === 0){return ['x' => 0, 'y' => 0];}
        $x = $n % $w;
        $y = ($n - $x) / $w;
        return ['x' => $x, 'y' => $y];
    }

    /**
     * 文字列を美しくトリムる
     * @param string $str トリムる文字列
     * @return string トリムられた文字列
     */
    private function trim($str) {
        $str = trim($str);
        //CDATAの削除
        $str = str_replace(['<![CDATA[', ']]>'], '', $str);
        //XMLタグの削除
        $str = preg_replace(['/<.*?>/', '/<\/.*?>/'], '', $str);
        return $str;
    }

    /**
     * セーブバージョンが対応しているか
     * @param string $version
     * @return bool
     */
    private function check_version($version)
    {
        // xxx.xxx.xxx
        $s = static::SUPPORT_SVE_VERSION_HIGHER;
        $v = explode('.', $version);
        if($v[1] >= $s[1]){
            return true;
        }
        return false;
    }

}

