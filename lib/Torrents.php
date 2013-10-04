<?
class Torrents extends BaseModel {
    var $_name = "torrents";
    
    public function clearAll($episode_id) {
        $this->db()->query("DELETE FROM `" . $this->_name . "` WHERE `episode_id`='" . $episode_id . "' AND `downloading`='0' AND `ignore`='0'");
    }
    
    public function sortResults($show_id, $episode_id, $results) {
        if(is_array($results))
        foreach($results as $result) {
            $torrent = new Torrent(false, $result[6]);
            $torrent->set('episode_id', $episode_id);
            $torrent->set('show_id'   , $show_id);
            $torrent->set('name'      , $result[0]);
            $torrent->set('size'      , $result[1]);
            $torrent->set('user'      , $result[2]);
            $torrent->set('link'      , $result[3]);
            $torrent->set('seed'      , $result[4]);
            $torrent->set('leech'     , $result[5]);
            $torrent->set('hash'      , $result[6]);
            $torrent->save();
        }
    }
    
    public function getTorrentByEpisode($episode_id, $downloading = false) {
        $sql = "SELECT * FROM `" . $this->_name . "` WHERE `episode_id`='" . $episode_id . "'";

        if($downloading) $sql .= " AND `downloading`='1'";

        $result = $this->db()->query($sql);
        if($result) $id     = $result->fetch_object()->id;
        
        if($this->db()->error) die($this->db()->error);
        if(!empty($id)) return new Torrent($id);
    }
    
    public function getTorrents($episode_id, $downloading = false) {
        $sql = "SELECT * FROM `" . $this->_name . "` WHERE `episode_id`='" . $episode_id . "'";

        if($downloading) $sql .= " AND `downloading`='1'";

        $result = $this->db()->query($sql);
        
        if($this->db()->error) die($this->db()->error);
        
        $results = array();
        while($row = $result->fetch_object()) {
            $results[] = $row;
        }
        
        return $results;
    }

    public function getTorrentsByShow($show_id, $downloading = false) {
        $sql = "SELECT * FROM `" . $this->_name . "` WHERE `show_id`='" . $show_id . "'";

        if($downloading) $sql .= " AND `downloading`='1'";

        $result = $this->db()->query($sql);
        
        if($this->db()->error) die($this->db()->error);
        
        $results = array();
        while($row = $result->fetch_object()) {
            $results[] = $row;
        }
        
        return $results;
    }
    
    public function getTorrent($hash) {
        $torrent = new Torrent(false, $hash);
        if(!empty($torrent->id)) return $torrent;
    }
    
    public function getBestTorrent($episode) {
        // Add points based on factors!
        // Consider: seeds, leeches, ratio, user, keywords (settings), size..
        
        $episode_id     = $episode->id;
        $show           = $this->shows()->getShow();
        $required_keys  = explode(",", $this->getSetting("required_keywords"));
        $preferred_keys = explode(",", $this->getSetting("preferred_keywords"));
        $trusted_users  = explode(",", $this->getSetting("trusted_users"));
        
        $results = $this->getTorrents($episode_id);
        
        $max_size = 0;
        $min_size = 0;
        
        $max_seed = 0;
        $min_seed = 0;
        
        $allTorrents = array();
        foreach($results as $result) {
            $torrent = new Torrent($result->id);
                        
            // Eliminations:
            if($torrent->seed  < 1) continue;
            if(!stristr($torrent->name, $episode->episodeFormatted())) continue;
            if(count($required_keys) > 0) {
                $failed = false;
                
                foreach($required_keys as $key) {
                    $key = trim($key);
                    
                    if(empty($key)) continue;
                    
                    if(!stristr($torrent->name, $key)) {
                        $failed = true;
                        break;
                    }
                }
                
                if($failed) continue;
            }
            
            if($torrent->size > $max_size)                   $max_size = $torrent->size;
            if($torrent->size < $min_size || $min_size == 0) $min_size = $torrent->size;
            
            if($torrent->seed > $max_seed)                   $max_seed = $torrent->seed;
            if($torrent->seed < $min_seed || $min_seed == 0) $min_seed = $torrent->seed;
            
            $allTorrents[] = $torrent;
        }

        //$max_size = $max_size - $min_size;
        //$max_seed = $max_seed - $min_seed;
        
        foreach($allTorrents as $torrent) {            
            $points = 0;
            
            $seed   = $torrent->seed;
            $leech  = $torrent->leech;
            
            if(count($trusted_users) > 0) {
                foreach($trusted_users as $user) {
                    $user = trim($user);
                    
                    if(empty($user)) continue;
                    
                    if(strtolower($torrent->user) == strtolower($user)) {
                        $points += 0.3;
                    }
                }
            }

            if(count($preferred_keys) > 0) {
                foreach($preferred_keys as $key) {
                    $key = trim($key);
                    
                    if(empty($key)) continue;
                    
                    if(stristr($torrent->name, $key)) {
                        $points += 0.5;
                    }
                }
            }
            
            $size    = $torrent->size;
            $size    = $size - $min_size;
            $close   = $size / $max_size;
            $close   = $close / 10;
            $close   = round($close, 2) / 10;
            $points += $close;
            
            $seeds   = $seed - $min_seed;
            $close   = $seeds / $max_seed;
            $close   = $close;
            $close   = round($close, 2);
            $points += $close / 10;

            $torrent->set('points', $points);
            $torrent->save();
        }
       
       
        $result = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `episode_id`='" . $episode_id . "' AND `ignore`='0' ORDER BY `seed` DESC, `size` DESC"); // ORDER BY POINTS!
        if(!$result) return false;
        
        $result = $result->fetch_object();
        
        if(empty($result->id)) return false;
        return new Torrent($result->id);
    }
    
    public function GCD($a, $b) {
        while ( $b != 0) {
            $remainder = $a % $b;
            $a = $b;
            $b = $remainder;
        }
        
        return abs($a);
    } 
    
    public function ratio($a, $b) {
        $var = $this->GCD($a,$b);
        
        return ($a/$var)/($b/$var);
    }
}
?>