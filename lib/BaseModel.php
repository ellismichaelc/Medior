<?
class BaseModel extends Settings {
    var $data;
    var $id;
    
    public function __construct() {
        parent::__construct();
        
        $this->path_shows = $this->getSetting("path_shows");
    }
    
    public function utorrent() {
        if(!isset($_SESSION['utorrent'])) $_SESSION['utorrent'] = new uTorrent();
        return $_SESSION['utorrent'];
    }
    
    public function downloader() {
        if(!isset($_SESSION['downloader'])) $_SESSION['downloader'] = new KAT();
        return $_SESSION['downloader'];
    }

    public function torrents() {
        if(!isset($_SESSION['torrents'])) $_SESSION['torrents'] = new Torrents();
        return $_SESSION['torrents'];
    }
    
    public function episodes() {
        if(!isset($_SESSION['episodes'])) $_SESSION['episodes'] = new Episodes();
        return $_SESSION['episodes'];
    }
    
    public function shows() {
        if(!isset($_SESSION['shows'])) $_SESSION['shows'] = new Shows();
        return $_SESSION['shows'];
    }
    
    public function files() {
        if(!isset($_SESSION['files'])) $_SESSION['files'] = new Files();
        return $_SESSION['files'];
    }
    
    public function __get($name) {
        if(isset($this->$name)) return $this->$name;
        if(isset($this->data[$name])) return $this->data[$name];
    }
    
    public function get($name) {
        return $this->data[$name];
    }
    
    public function set($name, $value) {
        $this->data[$name] = $value;
    }
    
    public function settings() {
        return $this->_settings;
    }
    
    public function populate($values) {        
        foreach($values as $key=>$data) {
            $this->data[$key] = $data;
        }
        
        if(!empty($this->data['id'])) $this->id = $this->data['id'];
    }
    
    public function saveField($field, $value) {
        $result = $this->db()->query("UPDATE `" . $this->_name . "` SET `" . $field . "`='" . $this->db()->escape_string($value) . "'");
        
        if($result) {
            $this->data[$field] = $value;
            
            return true;
        } else {
            return false;
        }
    }

    public function save($show=false) {
        if(!is_array($this->data) || count($this->data) == 0) return false;
        
        //if($show) print_r($this->data);
        
        if(!$this->exists) {
            $format = "INSERT INTO `" . $this->_name . "` SET %s";
        } else {
            $format = "UPDATE `" . $this->_name . "` SET %s WHERE `id`='" . $this->data['id'] . "'";
        }
        
        
        $pairs = "";
        foreach($this->data as $key=>$val) {
            $val    = $this->db()->escape_string($val);
        
            if($val == "\$EMPTY\$") {
                $val  = "";
            }
            
            $pair = ", `$key` = '$val'";
            
            if($val == "NULL")  $pair = ", `$key` = NULL";
            if($val == "TRUE")  $pair = ", `$key` = TRUE";
            if($val == "FALSE") $pair = ", `$key` = FALSE";
            
            if(!empty($val) || $val == '0') $pairs .= $pair;
        }
        
        $pairs = substr($pairs, 1);
        $sql   = sprintf($format, $pairs);
        
        $result = $this->db()->query($sql);
        
        //if(!$result)                   return false;
        if(!empty($this->db()->error)) throw new Exception("MySQL: " . $this->db()->error . " => " . $sql);
        
        if(!$this->exists) {
            $id = $this->db()->insert_id;
            
            $this->__construct($id);
            
            return 1;
        } else {
            $ar = $this->db()->affected_rows;
            
            return 2;
        }
    }
}
?>