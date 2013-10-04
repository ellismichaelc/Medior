<?
class KAT extends BaseModel {
    var $_name = "downloads";
    var $_host = "kickass.to";
    
    public function __construct() {
        parent::__construct();

    }
    
    public function Search($show_name, $season, $episode, $formatted) {
        //$url = "http://" . $this->_host . "/usearch/" . rawurlencode($show_name) . "%20seeds%3A1%20verified%3A1%20season%3A" . $season . "%20episode%3A" . $episode . "/?field=seeders&sorder=desc";
        
        $url = "http://" . $this->_host . "/usearch/" . rawurlencode($show_name . " 720p " . $formatted) . "%20seeds%3A1%20verified%3A1/?field=seeders&sorder=desc";
        $data = $this->get_url($url);
        
        if(!$data) return false;
        
        preg_match_all("/<tr class=\".*?\" id=\"torrent_.*?\">(.*?)<\/tr>/is", $data, $matches);
        $results = $matches[1];
        
        foreach($results as $result) {
            preg_match("/href=\"(magnet\:.*?)\"/", $result, $matches);
            $link = $matches[1];
            
            preg_match("/btih\:([a-zA-Z0-9]*)\&/", $link, $matches);
            $hash = strtoupper($matches[1]);
            
            preg_match("/class=\"normalgrey font12px plain bold\">(.*?)<\/a>/", $result, $matches);
            $name = strip_tags($matches[1]);
            
            preg_match("/<td class=\"green center\">([0-9]*?)<\/td>/", $result, $matches);
            $seed = intval($matches[1]);
            
            preg_match("/<td class=\"red lasttd center\">([0-9]*?)<\/td>/", $result, $matches);
            $leech = intval($matches[1]);
            
            preg_match("/<td class=\"nobr center\">(.*?)<\/td>/", $result, $matches);
            $size = strip_tags($matches[1]);
            $unit = substr($size, strpos($size, " ")+1);
            $size = substr($size, 0, strpos($size, " "));
            $unit = strtolower($unit);
            
            if($unit == "mb") $size = $size * 1024;
            if($unit == "gb") $size = $size * 1024 * 1024;
            if($unit == "tb") $size = $size * 1024 * 1024;
            
            preg_match("/\/user\/(.*?)\//", $result, $matches);
            $user = @strtolower($matches[1]);
            
            $return[] = array($name, $size, $user, $link, $seed, $leech, $hash);
        }
        
        return $return;
    }
    
    
    public function get_url($url) {
    	fwrite(fopen("log.txt","w"), $url);
        $data = @file_get_contents($url);
        if(!$data) return false;
        
        return gzdecode($data);
    }
}
?>