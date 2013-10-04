<?
class uTorrent extends BaseModel {
    var $connected = false;
    
    public function __construct() {
        parent::__construct();
        
    	$this->webui_host    = $this->getSetting("webui_host");
    	$this->webui_user    = $this->getSetting("webui_user");
    	$this->webui_pass    = $this->getSetting("webui_pass");
    	$this->webui_port    = $this->getSetting("webui_port");
    	$this->webui_timeout = 30;
        $this->webui_token   = "";
        $this->webui_cookies = array();
        $this->webui_time    = time();
        
        $this->connect();
    }
    
    public function fatal($message) {
        throw new Exception("uTorrent: " . $message);
    }
    
    public function webui_request($params = false, $url = false, $return_headers = false) {
        $encoded_auth = base64_encode($this->webui_user . ":" . $this->webui_pass);
        
        if(!empty($params) && substr($params, 0, 1) != "&") $params = "&" . $params;
        
        $url .= "?t=" . $this->webui_time;
        if(!empty($this->webui_token)) $url .= "&token=" . $this->webui_token;
        
        $url .= $params;
        
        $data = "";
    	$out  = "GET /gui/" . $url . " HTTP/1.1\r\n";
    	$out .= "Host: " . $this->webui_host . "\r\n";
    	$out .= "Connection: Close\r\n";
        $out .= "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.11 (KHTML, like Gecko) Chrome/23.0.1271.97 Safari/537.11\r\n";        
    	$out .= "Authorization: Basic " . $encoded_auth . "\r\n";
       
        if(count($this->webui_cookies) > 0) {
            foreach($this->webui_cookies as $cookie=>$value) {
                $out .= "Cookie: $cookie=$value\r\n";
            }
        }
        
    	$out .= "\r\n";
    	
    	if (!$fp = @fsockopen($this->webui_host, $this->webui_port, $errno, $errstr, $this->webui_timeout)) {
    		$this->fatal("Couldn't establish connection ($errno => $errstr)");
    	}
     
    	fwrite($fp, $out);
    	
    	while (!feof($fp)) {
    		$data .= fgets($fp, 128);
    	}
        
        fclose($fp);
        
        if(strstr($data, "invalid request")) return false;
        if(strstr($data, "HTTP/1.1 401 Unauthorized")) $this->fatal("Invalid username or password");
        //if(!strstr($data, "HTTP/1.1 200 OK")) $this->fatal($data);
        
 		if(strstr($data,"Set-Cookie")) {
			preg_match("/Set\-Cookie\: ([a-zA-Z0-9]*)\=([a-zA-Z0-9]*).*?/", $data, $matches);
            $this->webui_cookies[$matches[1]] = $matches[2];
		} elseif(count($this->webui_cookies) == 0) $this->fatal("Couldn't retreive cookie");
        
        if($return_headers) return $data;
        
        $data = trim(substr($data, strpos($data, "\r\n\r\n")));
        return $data;
    }
    
    public function connect() {
        $data = $this->webui_request(false, "token.html");
        
        if(!$data) $this->fatal("Server reported invalid request while retrieving token");
        
        preg_match("/<div id=\'token\'.*?>(.*?)<\/div>/", $data, $matches);
        $this->webui_token = trim($matches[1]);

        if(!$this->webui_token) $this->fatal("Couldn't retrieve token");
        
        return true;
    }
    
    public function addTorrent($link, $folder_name) {
        $data = $this->webui_request("action=add-url&s=" . urlencode($link) . "&path=" . urlencode($folder_name));

        if($data) return true;
    }
    
    public function removeTorrent($hash) {
        $data = $this->webui_request("action=removedatatorrent&hash=" . $hash);

        if($data) return true;
    }
    
    public function listTorrents() {
        $data = $this->webui_request("list=1");
        if(!$data) $this->fatal("Couldn't retrieve list of torrents");
        
        $data = json_decode($data);
        $data = $data->torrents;
        
        foreach($data as $key => $t_data) {
            $data[$t_data[0]] = $t_data;
            unset($data[$key]);
        }
        
        if(count($data) > 0) return $data;
    }
}
?>