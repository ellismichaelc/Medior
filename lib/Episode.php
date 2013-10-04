<?
class Episode extends BaseModel {
    var $_name  = "episodes";
    var $exists = false;
    
    public function __construct($episode_id = false, $imdb_id = false) {
        parent::__construct();
        
        if($episode_id && !$imdb_id) {
            $this->id = $episode_id;
            $this->db = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `id`='" . $episode_id ."'")->fetch_object();
        }
        
        if($imdb_id) {
            $this->set('imdb', $imdb_id);
            $this->db = $this->db()->query("SELECT * FROM `" . $this->_name . "` WHERE `imdb`='" . $imdb_id ."'")->fetch_object();
        }
        
        if(count($this->db) > 0) {
            $this->exists = true;
            $this->populate($this->db);
        }
    }
    
    public function getShow() {
        if(!isset($this->_show)) {
            $this->_show = new Show($this->show_id);
        }
        
        return $this->_show;
    }
    
    public function getTorrent() {
        $torrent = $this->torrents()->getTorrentByEpisode($this->id, true);
        if(!empty($torrent->id)) return $torrent;
    }
    
    public function moveCompletedDownload() {
        $torrent = $this->getTorrent();
        if(!$torrent) return;

        $move = $this->files()->moveCompletedDownload($this, $torrent);
        if($move) {
            $this->set('state', 'done');
            $this->save();
        }
        
        return $move;
    }
    
    public function addDownload() {
        // This function will call on KAT to
        // search for and add a torrent to the DB
        // that will later be processed and added to utorrent

        $results = $this->downloader()->search($this->getShow()->name, $this->season, $this->episode, $this->episodeFormatted());
        
        if(count($results) == 0) return false;
        
        $this->torrents()->clearAll($this->id);
        $this->torrents()->sortResults($this->show_id, $this->id, $results);
        
        $torrent = $this->torrents()->getBestTorrent($this);
        
        if(!$torrent) return false;
        if($this->utorrent()->addTorrent($torrent->link, $torrent->folderId())) {
            $torrent->set('downloading', '1');
            $torrent->save();
            
            $this->set('state', 'downloading');
            $this->save();
        }

        return $results;
    }
    
    public function exists() {
        return $this->exists;
    }
    
    public function getFile() {
        if(!isset($this->_file)) {
            $this->_file = new File();
            $this->_file = $this->_file->fileFromEpisode($this->id);
        }
        
        return $this->_file;
    }
    
    public function episodeFilename() {
        return $this->episodeFormatted("custom");
    }
    
    public function episodeFormatted($format = null) {
        $season  = $this->season;
        $episode = $this->episode;
        
        if(!empty($format) && !is_numeric($format)) {
            $format = $this->getSetting("episode_format");

            $name = $this->name;
            $name = preg_replace("/[^a-zA-Z0-9\!\'\-\_\+\=\, ]/", "", $name);
            
            $filename = sprintf($format, $this->season, $this->episode, $name);
            return $filename;
        }
        
        if(!$format) $format = 1;
        
        if($format == 1) {
            return sprintf("S%02dE%02d", $season, $episode);
        }
        
        if($format == 2) {
            return sprintf("[%02dx%02d]", $season, $episode);
        }
    }
}
?>