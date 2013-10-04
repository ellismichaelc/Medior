<?
class FFmpeg extends BaseModel {
    var $_name = "files";
    
    public function __construct($file_path) {
        parent::__construct();
        
        $this->file_path = $file_path;
        $this->ffmpeg    = $this->getSetting("path_ffmpeg");
        $this->ffprobe   = $this->getSetting("path_ffprobe");
        
        if(!is_file($this->file_path)) {
            throw new Exception("Cannot locate: '" . $this->file_path . "'");
        }
        
        if(!is_file($this->ffmpeg)) {
            throw new Exception("Cannot find FFmpeg binary!");
        }
        
        if(!is_file($this->ffprobe)) {
            throw new Exception("Cannot find FFprobe binary!");
        }
    }
    
    public function FFprobe() {
        if(isset($this->probe)) return $this->probe;
        
        $cmd = realpath($this->ffprobe) . " -loglevel quiet -show_format -show_streams -print_format json " . escapeShellArg($this->file_path);
        $cmd = `$cmd`;
        
        $this->json = $cmd;
        $this->probe = json_decode($cmd);
        return $this->probe;
    }
    
    public function getInfo() {
        $this->FFprobe();
        if(count($this->probe) == 0) return false;
        
        $info = array();
        $info['codec_name']           = $this->probe->streams[0]->codec_name;
        $info['codec_long_name']      = $this->probe->streams[0]->codec_long_name;
        $info['width']                = $this->probe->streams[0]->width;
        $info['height']               = $this->probe->streams[0]->height;
        $info['sample_aspect_ratio']  = $this->probe->streams[0]->sample_aspect_ratio;
        $info['display_aspect_ratio'] = $this->probe->streams[0]->display_aspect_ratio;
        $info['pix_fmt']              = $this->probe->streams[0]->pix_fmt;
        $info['format_name']          = $this->probe->format->format_name;
        $info['format_long_name']     = $this->probe->format->format_long_name;
        $info['duration']             = $this->probe->format->duration;
        $info['size']                 = $this->probe->format->size;
        $info['bit_rate']             = $this->probe->format->bit_rate;

        return $info;
    }
}
?>