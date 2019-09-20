<?php
/**
* App class 
* 
* Central class of the system
* 
* @file			App.class.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class App {
	
	// Config property
	protected $_config = null;
	
	// Database class property
	protected $_db = null;
	
	// Loader class property
	protected $_loader = null;
	
	// App Class properties
	protected $url = null;
    protected $urlArray = array();
	
	public function __construct($config = [])
	{
		$this->_config = $config;
		
		// Make custom URL each time
		$this->url = $_SERVER['REQUEST_SCHEME'] .'://'. $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . '' . $_SERVER['REQUEST_URI'];
		
        if($this->url == '' || !filter_var($this->url, FILTER_VALIDATE_URL)){
			
			header("HTTP/1.1 400 URL is missing or is invalid.");
			
            return;
        }else{
            $this->urlArray = explode('/', $this->url);
        }
	}
	
	public function db($db = null)
	{
		if($db){
			$this->_db = $db;
		}
		
		return $this->_db;
	}
	
	public function loader($loader = null)
	{
		if($loader){
			$this->_loader = $loader;
		}
		
		return $this->_loader;
	}
	
	public function run()
	{
		// Setup enviroment
		switch($this->urlArray[3]){
			
			case 'api':
				$this->_loader->env('api')->core('API');
			
				$api = new API($this->urlArray);
				$api
					->db($this->_db)
                    ->load($this->_loader)
				->respond();
			break;
			
			default:
				$this->_loader->env('web')->core('WEB');
				
				$web = new WEB($this->urlArray);
				$web
					->db($this->_db)
					->load($this->_loader)
				->respond();
			break;
		}
	}
}