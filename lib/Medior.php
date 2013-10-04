<?
// BACK END FOR MEDIOR ONLY
// THIS CLASS IS FOR THE SERVER,
// NOT THE FRONT END CLIENT.

class Medior extends BaseModel {
    var $debug_indent = 0;
    var $debug_spacer = "=";
    
    public function start() {        
        $this->debugSpacer();
        $this->debug(str_pad(" " . strtoupper($this->app_name) . " V" . $this->app_ver . " INITIALIZED ", 70, $this->debug_spacer, STR_PAD_BOTH));
        $this->debugSpacer();
        
        $this->debug("SETTINGS:");
        
        $dieAfter = false;
        $settings = $this->getAllSettings();
        
        foreach($settings as $key=>$val) {
            $this->debug(str_pad("+ '" . $key . "' ", 25, " ", STR_PAD_RIGHT) . "= '" . $val . "'", 1, 2);
        }
        
        $check_dirs = array('path_movies', 'path_shows');
        
        foreach($check_dirs as $setting) {
            if(!is_dir($settings[$setting])) {
                $this->debug("*FATAL: Setting '" . $setting . "' is not a valid directory!", 1, false, true);
                $dieAfter = true;
            } elseif(!is_writable($settings[$setting])) {
                $this->debug("*FATAL: Setting '" . $setting . "' is not accessible!", 1, false, true);
                $dieAfter = true;
            }
        }
        
        $this->debugSpacer(2);
        if($dieAfter) exit;
        
        $show_list = $this->shows()->selectAll();
        foreach($show_list as $key=>$show) {
            $show = new Show($show);
            $this->shows()->setShow($show->id);
            
            $this->debugIndent(2);
            $this->debug("* " . strToUpper($show->name));
            $this->debugIndent(4);
            
            $this->debug("- Checking for iMDB update.. ", false);
            
            if($show->imdbUpdateNeeded()) {
                $this->debug("Update neccessary.", 1, 0);
                $this->debug("- Starting iMDB info update. ", false);
                
                $update = $show->imdbUpdate();
                
                $this->debug($update[1], 1, 0);
                if($update[0] == false) {
                    $this->debug("* WARNING: iMDB update failed. Skipping update.", 2, false, true);
                    //continue;
                }
            } else {
                $this->debug("Not needed.", 1, 0);
            }

            $this->debug("- Synchronizing DB with torrent client.");
            $show->syncWithDB();
            
            $this->debug("- Checking for completed downloads.");
            $completed_downloads = $show->getCompletedEpisodes();
            
            if($completed_downloads > 0) {
                foreach($completed_downloads as $episode) {
                    $episode = new Episode($episode->id);
                    
                    $this->debug("+ " . $show->name . " " . $episode->episodeFormatted(2) . " [ MOVING .. ]", false, 8);
                    $move = $episode->moveCompletedDownload();
                    
                    if($move) $this->debug(" [ DONE! ]", 1, 0);
                    else      $this->debug(" [ FAILED ]", 1, 0);
                    
                    if($move) {
                        $show->set('last_download', time());
                        $show->save();
                        
                        // TODO: NOTIFY THE USER THAT THE SHOW IS READY TO WATCH!
                    }
                }
                
                $this->debug();
            }
            
            $this->debug("- Indexing all files.. ", false);
            $index_files = $show->indexExistingFiles();
            
            $this->debug($index_files[1], 1, 0);
            if($index_files[0] == false) {
                $this->debug("* FATAL: File indexing failed. Skipping show.", 2, false, true);
                continue;
            }
            
            $unidentified = $show->unidentifiedFilesCount();
            if($unidentified > 0) {
                $this->debug("- Identifying $unidentified non-indexed files.. ", false);
                
                $id_files = $show->identifyFiles();
                
                $this->debug($id_files[1], 1, 0);
                if($id_files[0] == false) {
                    $this->debug("* FATAL: File identification failed. Skipping show.", 2, false, true);
                    continue;
                }
            }
            
            $missing = $show->missingEpisodeCount();
            if($missing > 0) {
                $this->debug("- Starting torrent search for $missing missing episodes.");
                
                $this->debugIndent(8);
                
                $dl_files = $show->missing_episodes;
                foreach($dl_files as $ep) {
                    $episode = new Episode($ep);
                    
                    if($episode->state == 'downloaded') continue;
                    
                    $this->debug("+ " . $show->name . " " . $episode->episodeFormatted(2) . " ", 0);
                    
                    if($episode->state == 'downloading') {
                        $this->debug("[ DOWNLOADING ]", 1, 0);
                    } else {
                        $add = $episode->addDownload();
                        if(!$add) {
                            $this->debug("[ RESULTS: 0 ]", 0, 0);
                            $this->debug();
                        } else {
                            $this->debug("[ RESULTS: " . str_pad(count($add), 2, " ", STR_PAD_LEFT) . " ] [ ADDED TORRENT ]", 0, 0);
                            $this->debug();
                        }
                    }
                }
            }
            
            // Dont touch:
            if($key != count($show_list)-1) $this->debug(false, 2);
            else                            $this->debug();
        }
        
        $this->debugIndent();
        $this->debugSpacer();
    }
    
    public function debugIndent($count=0) {
        $this->debug_indent = $count;
    }
    
    public function debugSpacer($new_lines = 1) {
        $this->debug(str_pad("", 70, $this->debug_spacer), $new_lines);
    }
    
    public function debug($text = false, $new_lines = 1, $indent_override = false, $new_line_before = false) {
        if($new_line_before) {
            if(!$this->debug_new_line) echo "\n";
            $this->debug_new_line = true;
        } else {
            $this->debug_new_line = false;
        }
        
        if(!is_numeric($indent_override)) for($i=0; $i<$this->debug_indent; $i++) echo " ";
        else                              for($i=0; $i<$indent_override; $i++)    echo " ";
        
        echo $text;
        
        for($i=0; $i<$new_lines; $i++) echo "\n";
        
        //echo str_repeat("\0", 256);
        flush();
        ob_flush();
        flush();
        ob_flush();
        flush();
        ob_flush();
        flush();
        ob_flush();
        flush();
        ob_flush();
        
    }
}
?>