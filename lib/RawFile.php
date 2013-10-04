<?
class RawFile extends BaseModel {
    var $path;
    
    public function __construct($file) {
        $this->path = realPath($file);
        echo $this->path;
    }
}
?>