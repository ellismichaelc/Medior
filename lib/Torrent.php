<?
class Torrent extends BaseModel {
    var $_name = "torrents";
    
    public function __construct($id = false, $hash = false) {
        parent::__construct();
        
        if($id) {
            $this->id = $id;
            $this->db = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `id`='" . $id ."'")->fetch_object();
        }

        if($hash) {
            $this->hash = $hash;
            $this->db   = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `hash`='" . $hash ."'")->fetch_object();
        }
        
        if(count($this->db) > 0) {
            $this->exists = true;
            $this->populate($this->db);
        }
    }
    
    public function folderId() {
        return $this->app_name . "_" . $this->id;
    }
    
    public function getEpisode() {
        return new Episode($this->episode_id);
    }
}
?>