<?php
class Sim_Analyser_Api {
    const APP_NAME = 'Simutrans save data analyser API';
    const APP_VERSION = '1.0.0';
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

    protected $version = null;
    protected $pak = null;
    protected $map_no = 0;
    protected $map_x = 0;
    protected $map_y = 0;
    protected $map_tiles = 0;
    protected $players = [];
    protected $lines = [];

    public static function forge() {
        return new static();
    }

    public function __construct()
    {
    }

    /**
     * @param string $json_str
     */
    public function import_from_json($json_str) {
        $data = @json_decode($json_str, true);

        $this->set_version(@$data['info']['version']);
        $this->set_pak(@$data['info']['pak']);
        $this->set_map_no(@$data['info']['map_no']);

        $xy = explode('x', @$data['info']['size']);
        $this->set_map_x(array_shift($xy));
        $this->set_map_y(array_shift($xy));

        $this->set_players(@$data['players']);
        $this->set_stations(@$data['stations']);
        $this->set_lines(@$data['lines']);

        return $this;
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
            'source'  => 'https://github.com/128na/sim_analyser',
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
    public function get_data_by_csv($convert_sjis = false) {
        $result = ["generate by {$this->get_app()}"];
        $result[] = "player,way_type,line,stations";

        foreach ($this->get_lines() as $line) {
            $player = $this->get_player_by_id($line['player_id']);
            $way_type = $this->get_way_type_by_id($line['way_type_id']);
            $name = $line['name'];
            $stations = implode(',',$this->find_stations_by_coordinates($line['coordinates']));

            $result[] = "{$player},{$way_type},{$name},{$stations}";
        }
        $str = implode("\n", $result);

        if ($convert_sjis) {
            $str = mb_convert_encoding($str, 'SJIS', 'UTF-8');
        }
        return $str;
    }

    /**
     * 座標配列を駅名を探し、駅名の配列を返す。
     * @param array $coordinates
     * @return array
     */
    protected function find_stations_by_coordinates($coordinates) {
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
    public function get_station_by_coordinate($c, $skip_resolved = false){
        foreach ($this->get_stations() as $s) {
            if ($skip_resolved && count($s['coordinates']) > 1) continue;
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
        return null;
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

    /**
     * @param array $players
     */
    public function set_players($players)
    {
        $this->players = $players;
    }

    /**
     * @param array $stations
     */
    public function set_stations($stations)
    {
        $this->stations = $stations;
    }
    protected $stations = [];

    /**
     * @param array $lines
     */
    public function set_lines($lines)
    {
        $this->lines = $lines;
    }
}