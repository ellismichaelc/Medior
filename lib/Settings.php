<?
class Settings extends Database {   
    public $app_name = "Medior";
    public $app_ver  = "1.0";
    
    public function getAllSettings() {
        $all_settings = array();
        
        $result = $this->db()->query("SELECT * FROM `settings`");
        while($row = $result->fetch_array()) {
            $all_settings[$row['name']] = $row['value'];
        }
        
        if($result->num_rows == 0) return false;
    
        return $all_settings;
    }
    
    public function getSetting($setting_name) {
        $result = $this->db()->query("SELECT * FROM `settings` WHERE `name`='" . $setting_name ."'");
        
        if(!$result) die("Couldn't get setting '$setting_name'");
        
        $row    = $result->fetch_assoc();
        
        if($result->num_rows == 0) return false;
    
        return empty($row['value']) ? true : $row['value'];
    }
    
    public function setSetting($name, $value) {
        if(!$this->get($name)) {
            // Create
            
            $name  = mysql_escape_string($name);
            $value = mysql_escape_string($value);
            
            return mysql_affected_rows(mysql_query("INSERT INTO `settings` VALUES('$name', '$value');"));
        } else {
            // Update
        
            return $this->update(array('value'=>$value), array('name = ?' => $name)) > 0 ? true : false;
        }
    }
}
?>