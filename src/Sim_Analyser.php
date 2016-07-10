<?php

/**
 * Sim_Analyser
 * @author  128Na
 * @version 2016.Jul.10
 * @since   PHP5.6
 * @license WTFPL (http://www.wtfpl.net/txt/copying/)
 */
class Sim_Analyser {
    const APP_NAME = 'Simutrans save data analyser';
    const APP_VERSION = '1.3.0';
    const WAY_TYPES = [
        'unknown',
        'road',
        'track',
        'ship',
        'air',
        'mono',
        'tram',
        'maglev',
        'narrow',
    ];

    private $reader;
    private $version = null;
    private $pak = null;
    private $map_no = 0;
    private $map_x = 0;
    private $map_y = 0;
    private $map_tiles = 0;
    private $players = [];
    private $lines = [];
    private $stations = [];

    //planquadrat_t 要素の個数
    private $planquadrat_count = 0;

    /**
     * Sim_Analyser constructor.
     * @param string $path input file path
     */
    public function __construct($path) {
        try {
            $this->reader = new XMLReader();
            $this->reader->open($path);
        }
        catch (Exception $e) {
            Log::error( 'Cannot open file! : '. $e->getMessage() , true);
            exit;
        }
    }

    /**
     * execute analyser
     */
    public function execute() {
        $detect_simutrans = false;
        while ($this->read()) {
            if ($this->is_element()) {
                if (!$detect_simutrans && $this->is_name_open('Simutrans')){
                    Log::info('Reading header... ', true);

                    $detect_simutrans = $this->read_simuheader();

                    Log::info('pak -> '.$this->get_pak(), true);
                    Log::info('version -> '.$this->get_version(), true);
                }
                if ($detect_simutrans) {
                    if ($this->is_name_open('einstellungen_t')) {
                        Log::info('Reading map info... ', true);

                        $this->read_info();

                        Log::info('map size -> '.$this->get_map_x().'x'.$this->get_map_y(), true);
                        Log::info('map No. -> '.$this->get_map_no(), true);
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
            Log::error( 'Cannot read file! Did you saved the file as "XML" format?' , true);
            exit;
        }
    }

    /**
     * バージョンとpakセット名を取得する
     * @return bool 適切なsimutrans xml save formatか
     */
    private function read_simuheader() {
        $reader = $this->get_reader();
        $version = $reader->getAttribute("version");
        if ($version) {
            $this->set_version($version);
            $this->set_pak($reader->getAttribute("pak"));
            //必要ならバージョンチェックして解析可能ならtrueにする
            return true;
        }
        return false;
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
            Log::error("tile count over!");
            exit;
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
                        Log::info("station found -> {$name}", true);
                    }
                }
            }
        }
        $this->planquadrat_count++;
        // echo "\rReading tile info {$this->planquadrat_count} / {$tiles}";
    }

    /**
     * 各駅の保有座標配列を取得し、駅情報へ組み込む
     */
    private function read_relations() {
        $coordinates = $this->get_coordinates_from_str($this->reader->readInnerXML());

        if(!$this->resolve_relations($coordinates)){
            Log::error('cannot resolved!');
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
     * @param $coordinates 座標配列
     * @return bool 見つかったか
     */
    private function resolve_relations($coordinates) {
        foreach ($coordinates as $coordinate) {
            if($station = $this->get_station_by_coordinate($coordinate)) {
                Log::info('resolved station ->'.$station['name'], true);
                $station['coordinates'] = $coordinates;
                $this->set_station_by_coordinate($coordinate, $station);
                return true;
            }
        }
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
                Log::info('Line found -> '.$name, true);
            }
        }

    }

    /**
     * XML子要素を配列で返す
     * @return array XML strin garray
     */
    private function get_children_arr() {
        return explode("\n", $this->get_children_str());
    }

    /**
     * XML子要素の文字列を返す
     * @return string XML string
     */
    private function get_children_str() {
        return $this->reader->readInnerXML();
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
     * 次の行のXMLデータを読み取る
     * @return bool 読み取りの成否
     */
    private function read() {
        return @$this->reader->read();
    }

    /**
     * XML要素の値を読み取る
     * @return string
     */
    private function read_value() {
        return $this->reader->value;
    }

    /**
     * XMLReader インスタンスを返す
     * @return XMLReader
     */
    private function get_reader() {
        return $this->reader;
    }

    /**
     * 現在のノードが要素か
     * @return bool
     */
    private function is_element() {
        return $this->reader->nodeType === XMLReader::ELEMENT;
    }

    /**
     * 現在のノードがCDATAか
     * @return bool
     */
    private function is_cdata() {
        return $this->reader->nodeType === XMLReader::CDATA;
    }

    /**
     * 指定された名前の要素の開始か
     * @param string $name element name
     * @return bool
     */
    private function is_name_open($name) {
        return ($this->reader->nodeType !== XMLReader::END_ELEMENT) && ($this->reader->localName === $name);
    }

    /**
     * 指定された名前の要素の終了か
     * @param string $name element name
     * @return bool
     */
    private function is_name_close($name) {
        return ($this->reader->nodeType === XMLReader::END_ELEMENT) && ($this->reader->localName === $name);
    }

    /**
     * 取得データを配列でまとめて返す
     * @return array
     */
    public function get_data_by_array() {
        $app = [
            'author'  => '128Na',
            'web'     => 'http://simutrans128.blog26.fc2.com',
            'version' => $this->get_app(),
            'source'  => 'comming soon',
        ];

        $info = [
            'version' => $this->get_version(),
            'pak'     => $this->get_pak(),
            'size'    => $this->get_map_x() . 'x' . $this->get_map_y(),
            'map_no'  => $this->get_map_no(),
        ];

        return [
            'application' => $app,
            'info'        => $info,
            'players'     => $this->get_players(),
            'stations'    => $this->get_stations(),
            'lines'       => $this->get_lines(),
            'way_types'   => $this->get_way_types(),
        ];
    }

    /**
     * 取得データをjson文字列でまとめて返す
     * @return string
     */
    public function get_data_by_json() {
        return json_encode($this->get_data_by_array());
    }

    /**
     * 取得データをcsv文字列でまとめて返す
    @return string
     */
    public function get_data_by_csv() {
        $result = ["generate by {$this->get_app()}"];
        $result[] = "player,way_type,line,stations";

        foreach ($this->get_lines() as $line) {
            $player = $this->get_player_by_id($line['player_id']);
            $way_type = $this->get_way_type_by_id($line['way_type_id']);
            $name = $line['name'];
            $stations = implode(',',$this->find_stations_by_coordinates($line['coordinates']));

            $result[] = "{$player},{$way_type},{$name},{$stations}";
        }
        return implode("\n", $result);
    }

    /**
     * 座標配列を駅名を探し、駅名の配列を返す。
     * @param array $coordinates
     * @return array
     */
    private function find_stations_by_coordinates($coordinates) {
        $result = [];
        foreach ($coordinates as $coordinate) {
            $sta = $this->get_station_by_coordinate($coordinate);
            $result[] = $sta ? $sta['name'] : 'no-name';
        }
        return $result;
    }

    /**
     * アプリ情報を返す
     * @return string
     */
    public function get_app(){
        return static::APP_NAME .' ver'. static::APP_VERSION;
    }

    public function get_way_types(){return static::WAY_TYPES;}
    public function get_way_type_by_id($id){return static::WAY_TYPES[$id];}
    public function get_version(){return $this->version;}
    public function get_pak(){return $this->pak;}
    public function get_map_no(){return $this->map_no;}
    public function get_map_x(){return $this->map_x;}
    public function get_map_y(){return $this->map_y;}
    public function get_map_tiles(){return $this->map_tiles;}
    public function get_stations(){return $this->stations;}
    public function get_station_by_id($id){return $this->stations[$id];}
    public function get_station_by_coordinate($c){
        foreach ($this->get_stations() as $s) {
            foreach ($s['coordinates'] as $p) {
                if(
                    $p['x'] === $c['x'] &&
                    $p['y'] === $c['y'] &&
                    $p['z'] === $c['z']
                ) {
                    return $s;
                }
            }
        }
    }
    public function get_lines(){return $this->lines;}
    public function get_players(){return $this->players;}
    public function get_player_by_id($id){return $this->players[$id];}


    public function set_map_x($x){$this->map_x = intval($x);}
    public function set_map_y($y){$this->map_y = intval($y);}
    public function set_map_no($no){$this->map_no = intval($no);}
    public function set_map_tiles($tiles){$this->map_tiles = intval($tiles);}
    public function set_version($version){$this->version = $version;}
    public function set_pak($pak){$this->pak = $pak;}
    public function set_station_by_id($id, $s){
        $this->stations[$id] = $s;
    }
    public function set_station_by_coordinate($c, $s){
        foreach ($this->get_stations() as $id => $station) {
            foreach ($station['coordinates'] as $p) {
                if(
                    $p['x'] === $c['x'] &&
                    $p['y'] === $c['y'] &&
                    $p['z'] === $c['z']
                ) {
                    $this->set_station_by_id($id, $s);
                }
            }
        }
    }

    public function add_station($s){$this->stations[] = $s;}
    public function add_player($player){$this->players[] = $player;}
    public function add_line($line){$this->lines[] = $line;}
}

