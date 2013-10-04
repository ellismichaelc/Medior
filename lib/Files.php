<?
class Files extends BaseModel {
    var $_name = "files";
    
    public function identifyFiles($show_id) {
        // We need to figure out:
        // Season, Episode

        // We need to update:
        // episode_id, identified
        
        $this->show_id = $show_id;
        
        $probe_good = 0;
        $probe_bad  = 0;
        $id_good    = 0;
        $id_bad     = 0;
        
        $result = $this->db()->query("SELECT `id` FROM `" . $this->_name . "` WHERE `show_id`='" . $show_id ."' AND (`identified`=FALSE OR `video_identified`=FALSE)");
        while($row = $result->fetch_object()) {
            $file   = new File($row->id);
            
            if($file->identified == false) {
                $file_id = new FileIdentifier($file->path);
                
                if($file_id->identified) {
                    $episode = $this->episodes()->findEpisode($file->show_id, $file_id->season, $file_id->episode);
                    
                    if(!empty($episode->id)) {
                        $id_good ++;
                        
                        $file->set('season'    , $file_id->season);
                        $file->set('episode'   , $file_id->episode);
                        $file->set('episode_id', $episode->id);
                        $file->set('identified', '1');
                        $file->save();
                        
                        $episode->set('state', 'NULL');
                        $episode->save();
                    } else $id_bad ++;
                } else $id_bad ++;
            }
            
            if($file->video_identified == false) {
                $ffmpeg = new FFmpeg($file->fullPath());
                $probe  = $ffmpeg->getInfo();
                
                if($probe) {
                    $probe_good ++;
                    
                    $file->set('format'          , $probe['format_name']);
                    $file->set('bit_rate'        , $probe['bit_rate']);
                    $file->set('duration'        , $probe['duration']);
                    $file->set('size'            , $probe['size']);
                    $file->set('frame_height'    , $probe['height']);
                    $file->set('frame_width'     , $probe['width']);
                    $file->set('codec'           , $probe['codec_name']);
                    $file->set('json_info'       , $ffmpeg->json);
                    $file->set('video_identified', '1');
                    
                    $file->save(true);
                } else $probe_bad ++;
            }
        }
        
        $id_total    = $id_good + $id_bad;
        $probe_total = $probe_good + $probe_bad;
        
        if($id_total > 0) {
            $msg = sprintf("Identified: %d/%d files", $id_good, $id_total);
        }
        
        if($probe_total > 0) {
            $msg = (isset($msg) ? $msg . ", " : "") . sprintf("Probed: %d/%d", $probe_good, $probe_total);
        }
        
        if(!isset($msg)) {
            return array(false, "Error: No error");
        }
        
        return array(true, $msg);
    }
    
    public function unidentifiedFilesCount($show_id) {
        $this->show_id = $show_id;
        
        $result = $this->db()->query("SELECT count(*) as `count` FROM `" . $this->_name . "` WHERE `show_id`='" . $show_id ."' AND (`identified`=FALSE OR `video_identified`=FALSE)")->fetch_object();
        return $result->count;
    }   
    
    public function indexExistingFiles($show_id, $sub_dir) {
        $dir = $this->path_shows;
        
        $this->sub_dir = $sub_dir;
        $this->show_id = $show_id;
        
        $dir = $dir . "/" . $sub_dir;
        if(!is_dir($dir)) @mkdir($dir, 0777, true);
        
        $dir = realpath($dir);
        if(!is_writable($dir)) return array(false, "Error: Cannot access folder");
        if(!is_dir($dir)) return array(false, "Error: Can't find folder, and php fucking thinks it's writable..");
        
        $list = $this->recursiveListDir($dir);
        $algo = $this->getSetting("hash_algo");
        $exts = explode(",", $this->getSetting("extensions_videos"));
        
        $dup_folder     = $this->getSetting("path_duplicates");
        
        $new_files      = 0;
        $existing_files = 0;
        $dup_files      = 0;
        $my_time        = time();
        $hash_cache     = array();
        
        foreach($list as $l) {
            $file_path = realpath($dir . "/" . $l);
            $rel_path  = $l;
            $ext       = pathinfo($file_path, PATHINFO_EXTENSION);
            
            if(!in_array($ext, $exts)) continue;
            
            $file = new File();
            $hash = $this->fileHash($file_path);
            //$hash = hash_file($algo, $file_path);
            $file = $file->fileFromHash($hash);
            
            if(isset($hash_cache[$hash])) {
                // This file must be moved to the duplicates folder
                if(!is_dir($dup_folder)) @mkdir($dup_folder, 0777, true);
                $dup_folder = realpath($dup_folder);
        
                $dup_files++;
            
                $dest   = pathinfo($dup_folder . "/" . $sub_dir . "/" . $l);
                $folder = $dest['filename'];
                
                //die($dest);
                
                if(!is_dir($folder)) @mkdir($folder, 0777, true);
                if(!is_writable($folder)) return array(false, "Error: Cannot create/write '" . $folder . "'");
                
                $folder = realpath($folder);
                $rn     = @rename($file_path, $folder . "/" . $dest['basename']);
                
                if(!$rn) return array(false, "Error: Cannot move '$l' to '$folder'");
                else {
                    $fp = fopen($folder . "/" . $dest['filename'] . ".txt", "w");
                    fwrite($fp, "Duplicate of: " . $hash_cache[$hash]);
                    fclose($fp);
                }
            } else {
                $hash_cache[$hash] = $file_path;
            }
            
            if(empty($file->id)) {
                $new_files ++;
                
                $file->set('show_id', $show_id);
                $file->set('type'   , $this->mime_type($file_path));
                $file->set('added'  , time());
                $file->set('path'   , $rel_path);
                $file->set('updated', $my_time);
        
                $file->save();
                
            } else {
                $existing_files ++;
                
                $file->set('updated', $my_time);
                $file->set('path'   , $rel_path);
                $file->save();
            }
        }
        
        $started  = $this->db()->query("SELECT COUNT(*) AS `count` FROM `" . $this->_name . "` WHERE `show_id`='" . $this->show_id . "' AND `updated` <> '" . $my_time . "'")->fetch_object();
        $delete   = $this->db()->query("DELETE FROM `" . $this->_name . "` WHERE `show_id`='" . $this->show_id . "' AND `updated` <> '" . $my_time . "'");
        $finished = $this->db()->query("SELECT COUNT(*) AS `count` FROM `" . $this->_name . "` WHERE `show_id`='" . $this->show_id . "' AND `updated` <> '" . $my_time . "'")->fetch_object();
        
        $deleted = round($started->count - $finished->count);
        
        return array(true, "Existing: $existing_files" . (($new_files > 0) ? ", Newly Indexed: $new_files" : "") . (($deleted > 0) ? ", Dead Indexes: $deleted" : "") . (($dup_files > 0) ? ", Duplicates: $dup_files" : ""));
        
        return;
    }
    
    public function moveCompletedDownload($episode, $torrent) {
        if(!is_readable($torrent->path)) return false;
        
        $list      = $this->files()->recursiveListDir($torrent->path);
        $can_clean = @explode(",", $this->getSetting("extensions_delete"));
        $video_ext = @explode(",", $this->getSetting("extensions_videos"));
        
        if(!is_array($can_clean)) $can_clean = array();
        
        foreach($list as $file) {
            $file_path = $torrent->path . "/" . $file;
            $file_info = pathinfo($file_path);
            $file_name = $file_info['filename'];
            $file_orig = $file_info['basename'];
            $file_ext  = $file_info['extension'];
            
            $dirs = explode("\\", $file_info['dirname']);
            $sub_dir = $dirs[count($dirs) - 1];
            
            if(in_array(strtolower($file_ext), $can_clean) || strtolower($sub_dir) == "sample" || substr(strtolower($file_name), 0, 6) == "sample") {
                @unlink($file_path);
                continue;
            }
            
            if(in_array(strtolower($file_ext), $video_ext)) {
                // This is probably the file.
                
                if(isset($the_file)) {
                    // TODO: NOTIFY THE USER THAT
                    // THEY MUST RESOLVE THIS CONFLICT MANUALLY.
                    // THERE IS MORE THAN ONE VIDEO FILE PRESENT
                    // THAT COULD BE THE ONE.
                    
                    echo " [ MULTIPLES ]";
                    return false;
                }
                
                $the_file = array($file_path,$file_info);
            }
        }
        
        if(isset($the_file)) {
            $dest = ($this->getSetting("path_shows") . "/" . $episode->getShow()->name . "/" . "Season " . $episode->season);
            if(!is_dir($dest)) {
                if(!mkdir($dest, 0777, true)) throw new Exception("Couldn't make dest dir: $dest");
            }
            
            $dest .= "/" . $episode->episodeFileName() . "." . $the_file[1]['extension'];

            if(!file_exists($dest)) {        
                if(!rename($the_file[0], $dest)) {                
                    echo " [ RENAME FAIL ]";
                    // TODO: NOTIFY THE USER THAT THE
                    // FILE WAS UNABLE TO BE MOVED
                    // AND THIS MUST BE RESOLVED MANUALLY
                    
                    return false;
                }
            }
            
            // Delete whole directory.
            $this->recursiveDeleteDir($torrent->path);
        } else {
            echo " [ ID FAIL ]";
            // TODO: NOTIFY THE USER THAT
            // THE FILE COULDN'T BE LOCATED
            // AND THIS MUST BE RESOLVED MANUALLY
            
            return false;
        }
    }

    public function recursiveDeleteDir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") $this->recursiveDeleteDir($dir."/".$object); else @unlink($dir."/".$object);
                }
            }
            
            @reset($objects);
            @rmdir($dir);
        }
    }

    public function recursiveListDir($dir, $prefix = '') {
    	if(!is_dir($dir) || !is_readable($dir)) {
	    	return false;
    	}
    	
      $dir = rtrim($dir, '\\/');
      $result = array();
    
        foreach (scandir($dir) as $f) {
          if ($f !== '.' and $f !== '..') {
            if (is_dir("$dir/$f")) {
              $result = array_merge($result, $this->recursiveListDir("$dir/$f", "$prefix$f/"));
            } else {
              $result[] = $prefix.$f;
            }
          }
        }
    
      return $result;
    }
    
    public function fileHash($file_path) {
        // Well.. not really hash..

        //$t1 = microtime(true);
        $seek = round(fileSize($file_path) / 2);


        $fp = fopen($file_path, "r");
        fseek($fp, $seek);
        
        $hash = fread($fp, 16);
        $hash = hash($this->getSetting("hash_algo"), $hash);
        
        fclose($fp);
        //$t2 = microtime(true);
        
        return $hash;
    }
    
    public function mime_type($file_path) {
		$info = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($info, $file_path);
				
		finfo_close($info);
		
		return $mime;
    }
}
?>