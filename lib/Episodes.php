<?
class Episodes extends BaseModel {
    var $_name = "episodes";
    
    public function findEpisode($show_id, $season, $episode) {
        $result = $this->db()->query("SELECT `id` FROM `" . $this->_name . "` WHERE `show_id`='" . $show_id . "' AND `season`='" . $season . "' AND `episode`='" . $episode . "'");
        
        if($result->num_rows == 0) return false;
        else return new Episode($result->fetch_object()->id);
    }
    
    public function resetStates($show_id) {
        $this->db()->query("UPDATE `episodes` SET `state` = NULL WHERE `show_id`='" . $show_id . "'");
    }
    
    public function findEpisodes($show_id, $fields, $values) {
        $sql = "SELECT * FROM `" . $this->_name . "` WHERE `show_id`='" . $show_id . "'";
        
        foreach($fields as $key=>$field) {
            $sql .= " AND `" . $field . "` = '" . $values[$key] . "'";
        }
        
        $result = $this->db()->query($sql);
        
        if($this->db()->error) die($this->db()->error);
        
        $results = array();
        while($row = $result->fetch_object()) {
            $results[] = $row;
        }
        
        if(count($results) == 0) return false;
        return $results;
    }
}
?>