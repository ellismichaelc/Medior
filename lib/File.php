<?
class File extends BaseModel {
    var $_name = "files";
    
    public function __construct($id = false) {
        parent::__construct();
        
        if($id) {
            $this->id = $id;
            $this->db = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `id`='" . $id ."'")->fetch_object();
            
            $this->exists = true;
            $this->populate($this->db);
        }
    }
    
    public function fullPath() {
        return realpath($this->path_shows . "/" . $this->getShow()->name . "/" . $this->path);
    }
    
    public function getShow() {
        if(!isset($this->show)) $this->show = new Show($this->show_id);
        return $this->show;
    }
    
    public function fileFromHash($hash) {
        $this->set('hash', $hash);
        
        $file_id = $this->findFile("hash", $hash);
        
        if($file_id) {
            return new File($file_id);
        } else return $this;
    }

    public function fileFromEpisode($id) {
        $this->set('episode_id', $id);
        
        $file_id = $this->findFile("episode_id", $id);
        
        if($file_id) {
            return new File($file_id);
        } else return false;
    }
    
    public function findFile($field, $value) {
        $result = $this->db()->query("SELECT `id` FROM `" . $this->_name . "` WHERE `" . $field . "` = '" . $this->db()->escape_string($value) . "'");
        
        if($obj = $result->fetch_object()) return $obj->id;

    }
}
?>