<?
class FileIdentifier extends BaseModel {
    public function __construct($file_path) {
        parent::__construct();
        
        $this->file_path = $file_path;
        $this->file_info = pathinfo($file_path);
        $this->filename  = $this->file_info['filename'];
        
        $this->identified = $this->identify() ? true : false;
    }
    
    public function identify() {
        $patterns = array("'^.+\.S([0-9]+)E([0-9]+).*$'i",
                          "'^.+S([0-9]+)E([0-9]+).*$'i",
                          "'S([0-9]+)E([0-9]+)'i",
                          "'\[([0-9]+)X([0-9]+)\]'i",
                          "'([0-9]+)X([0-9]+)'i");
                          
        $secondary = array("/^E([0-9]{1,2})$/i", "'^.+\.E([0-9]{1,2}).*$'i",
                          "'^.*E([0-9]{1,2}).*$'i",
                          "'E([0-9]{1,2})'i",
                          "'\[([0-9]{1,2})\]'i",
                          "'\(([0-9]{1,2})\)'i");
                          
        $resolutions = array("480i", "480p", "720i", "720p", "1080i", "1080p");
        
        foreach($patterns as $pattern) {
            if (preg_match($pattern, $this->filename, $matches)) {
                $this->season  = intval($matches[1]);
                $this->episode = intval($matches[2]);
                
                foreach($resolutions as $res) {
                    if(stristr($this->filename, $res)) $this->resolution = $res;
                }
                
                if(is_numeric($this->season) && is_numeric($this->episode)) return true;
            }
        }
        
        // Hmm, still couldn't identify.
        // Maybe it's a file with the episode in the name
        // and it's relying on the folder for the season.
        // -- let's be strict about this, it MUST be formatted
        // like "Season X" with X being a valid number 1+ -- to avoid miss IDing shit.
        
        $base_dir = basename($this->file_info['dirname']);
        
        if(preg_match("/season ([0-9]+)/i", $base_dir, $matches)) {
            $season = intval($matches[1]);
            
            if($season > 0) {
                $this->season = $season;
                // Looks legit. Let's see if the episode number is in the filename.
                
                foreach($secondary as $pattern) {
                    if (preg_match($pattern, $this->filename, $matches)) {
                        $this->episode = intval($matches[1]);
                        
                        foreach($resolutions as $res) {
                            if(stristr($this->filename, $res)) $this->resolution = $res;
                        }
                        
                        if(is_numeric($this->season) && is_numeric($this->episode)) return true;
                    }
                }
            }
        }
    }
}
?>