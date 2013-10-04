<?
class Show extends BaseModel {
    var $_name = "shows";
    
    public function __construct($show_id = false) {
        parent::__construct();
        
        if($show_id) {
            $this->id = $show_id;
            $this->db = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `id`='" . $show_id ."'")->fetch_object();
            
            $this->populate($this->db);
            $this->exists = true;
        }
    }
    
    public function resetStates() {
        $this->episodes()->resetStates($this->id);
    }
    
    public function syncWithDB() {
        $this->resetStates();

        $downloads = $this->utorrent()->listTorrents();
        $torrents  = $this->torrents()->getTorrentsByShow($this->id, true);
        
        // address the problem of if i remove a torrent
        // from the client while the DB has it marked as DLing

        if(count($torrents) > 0)
        foreach($torrents as $torrent) {
            $set_dl  = true;
            $torrent = new Torrent($torrent->id);
            
            if(!$torrent) continue;
            if($torrent->show_id != $this->id) $set_dl = false;
            
            if(!isset($downloads[$torrent->hash])) {
                // Torrent was removed from client..
                
                $set_dl = false;
            }
            
            if(!$set_dl) {
                $torrent->set('downloading', '0');
            } else {
                $torrent->set('downloading', '1');
            }
            
            $torrent->save();
            
            $episode = new Episode($torrent->episode_id);
            $episode->set('state', 'NULL');
            $episode->save();
        }

        if(count($downloads) > 0)
        foreach($downloads as $hash=>$download) {
            $set_dl  = true;
            $torrent = $this->torrents()->getTorrent($hash);
            
            if(!$torrent) continue;
            if($torrent->show_id != $this->id) continue;
            
            if($torrent->ignore == 1 || $torrent->getEpisode()->ignore == 1) {
                // Torrent was flagged as ignore,
                // but is downloading. Remove from client!
                
                $set_dl = false;
                
                $this->utorrent()->removeTorrent($torrent->hash);
                unset($downloads[$hash]);
            }
            
            if(!$set_dl) {
                $torrent->set('downloading', '0');
            } else {
                $torrent->set('downloading', '1');
            }

            $torrent->save();
            
            $episode = new Episode($torrent->episode_id);
            $episode->set('state', 'NULL');
            $episode->save();
            
        }
        
        if(count($downloads) > 0)
        foreach($downloads as $torrent) {
            $hash  = $torrent[0];
            $state = strtolower($torrent[21]);
            $path  = $torrent[26];
            
            $torrent = $this->torrents()->getTorrent($hash);
            if($torrent) {
                $episode = new Episode($torrent->episode_id);
                if($episode->show_id != $this->id) continue;
                
                $torrent->set('path', $path);
                $file = $episode->getFile();
            
                if($file) {
                    $episode->set('state', 'done');
                    $torrent->set('downloading', '1');
                    
                    if($state != "finished") {
                        $this->utorrent()->removeTorrent($torrent->hash);
                        unset($downloads[$hash]);
                    }
                } else {
                    if($state == "finished") {
                        $episode->set('state', 'downloaded');
                        $torrent->set('downloading', '1');
                    } else {
                        $episode->set('state', 'downloading');
                        $torrent->set('downloading', '1');
                    }
                }
                
                $episode->save();
                $torrent->save();
            }
        }
    }
    
    public function getCompletedEpisodes() {
        return $this->episodes()->findEpisodes($this->id, array("state"), array("downloaded"));
    }
    
    public function missingEpisodeCount() {
        $list = $this->episodes()->findEpisodes($this->id, array('aired'), array('1'));

        $this->missing_episodes = array();
        
        if(count($list) == 0 || !is_array($list)) return 0;
        foreach($list as $ep) {
            if($this->start_season > $ep->season) continue;
            elseif($ep->season == $this->start_season && ($ep->episode < $this->start_episode && $this->start_episode > 0)) continue;
            
            $episode = new Episode($ep->id);
            
            $file    = $episode->getFile();
            //if($file) echo "\n$ep->season $ep->episode " . print_r($file,true);
            if(!$file && $episode->ignore == 0) $this->missing_episodes[] = $ep->id;      
        }
        
        return count($this->missing_episodes);
    }
    
    public function verifyIndexedFiles() {
        return $this->files()->verifyIndexedFiles($this->id);
    }
    
    public function indexExistingFiles() {
        return $this->files()->indexExistingFiles($this->id, $this->name);
    }
    
    public function unidentifiedFilesCount() {
        return $this->files()->unidentifiedFilesCount($this->id);
    }
    
    public function identifyFiles() {
        return $this->files()->identifyFiles($this->id);
    }
    
    public function imdbUpdateNeeded() {
        $update_interval = $this->getSetting("imdb_update_interval") * 60 * 60;
        $last_update     = $this->last_update;
        $time_between    = time() - $last_update;
        
        if($time_between > $update_interval) return true;
        else                                 return false;
    }
    
    public function imdbUpdate() {
        $imdb         = new iMDB($this->imdb);
        $title        = $imdb->title();

        $episode_list = $imdb->episodes();
        
        if(!$episode_list || count($episode_list) == 0) return array(false, "iMDB returned an empty result.");
        
        $ep_new = 0;
        $ep_old = 0;
   
        $not_aired = array();
        
        foreach($episode_list as $season) {
        
        	/*
        	$last = end($season);
			for($i=1; $i<=$last['episode']; $i++) {
				if(!isset($season[$i])) {
					// Insert a blank episode I guess, bro.

					$season[$i] = array('imdbid' => null,
										'airdate'=> 00-00-0000,
										'aired'  => 1,
										'plot'   => '',
										'title'  => 'Unknown Episode',
										'season' => $last['season'],
										'episode'=> $i);
										
				}	
			}*/
			
			//ksort($season);
	        
            foreach($season as $ep) {
            	//for($i=1; $i<key(end($ep)); $i++) {
            
                $episode  = new Episode(null, $ep['imdbid']);
                
                if(strlen($ep['airdate']) == 4)                 $air_date = mkTime(0, 0, 0, 0, 0, $ep['airdate']);
                elseif(strtolower($ep['airdate']) == "unknown") $air_date = 0;
                else                                            $air_date = strToTime($ep['airdate']);
                
                $aired    = time() >= $air_date ? '1' : '0';
                
                if($aired == '0') {
                    $not_aired[] = $episode;
                } else {
                    // Check if any before it HAVENT aired, and set them as aired!
                    // God damn iMDB.
                    
                    foreach($not_aired as $key=>$na_ep) {
                        $na_ep->set('aired', '1');
                        $na_ep->save();
                        
                        //echo "Fixed $na_ep->season x $na_ep->episode\n";
                        
                        unset($not_aired[$key]);
                    }
                }
                
                $episode->set('show_id', $this->id);
                $episode->set('name'   , $ep['title']);
                $episode->set('airdate', $air_date);
                $episode->set('aired'  , $aired);
                $episode->set('desc'   , $ep['plot']);
                $episode->set('season' , $ep['season']);
                $episode->set('episode', $ep['episode']);
                
                $type = $episode->save();
                
                if($type == 1) $ep_new ++;
                if($type == 2) $ep_old ++;
            }
        }
        
        //$title = html_entity_decode($title);
        //$title = str_replace("&#x27;", "", $title);
        $title = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $title = preg_replace("/[^a-zA-Z0-9\-\_ ]*/", "", $title);

        if(!empty($title)) $this->set("name", $title);
        
        $this->set("last_update", time());
        $this->save();
        
        return array(true, "Added $ep_new new, $ep_old existing.");
    }
}
?>