<?php
/**
* WEB class 
* 
* Control class for web controllers
* 
* @file			WEB.class.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class WEB {
    
    protected $_db = null;
    protected $_loader = null;
    
	protected $_defaultController = 'Index';
	protected $_defaultAction = 'index';
    
    protected $_urlArray = array();
    protected $_controller = '';
    protected $_action = '';
    protected $_params = array();
    
    public function __construct($_urlArray) {
        
        $this->_urlArray = $_urlArray;
        
        // Set controller
        $this->_controller = ((isset($this->_urlArray[3]) && strlen($this->_urlArray[3]) > 0) ? ucfirst($this->_urlArray[3]) : $this->_defaultController);
        
        // Set action
        $this->_action = ((isset($this->_urlArray[4]) && strlen($this->_urlArray[4]) > 0) ? $this->_urlArray[4] : $this->_defaultAction);
        
        $l = count($this->_urlArray);

        for($i = 5; $i < $l; $i++){
            if($i + 1 < $l) {
                if ($this->_urlArray[$i] != '' && $this->_urlArray[$i + 1] != null){
                    $this->_params[$this->_urlArray[$i]] = $this->_urlArray[$i + 1];
                }
            }
            
            //move to next pair, hence the double increment
            $i++;
        }
    }

    public function db($db = null){
        if($db){
            $this->_db = $db;
            return $this;
        }

        return $this->_db;
    }
	
    public function load($loader = null){
        if($loader){
            $this->_loader = $loader;
            return $this;
        }

        return $this->_loader;
    }

    public function respond(){
        
		// Load the main controller
		$this->_loader->controller('Main');
		$this->_loader->controller($this->_controller);
		
        $controllerClass = $this->_controller . 'Controller';
		$controller = new $controllerClass;
        
		// Check if init method exists and execute it first if it exists
		if (method_exists($controller, 'init')) {
			$controller->init();
		}
        
		$actionName = $this->_action . "Action";
		$controller
            ->db($this->_db)
            ->load($this->_loader)
        ->$actionName();
    }
}