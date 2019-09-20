<?php
/**
* MainEndPoint class 
* 
* Handles API calls to auth users
* 
* @file			Auth.EndPoint.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
abstract class MainEndPoint {
    
    protected $_rqMethod;
    protected $_params;
    
	public function __construct($rqMethod, $params)
	{
        $this->_rqMethod = $rqMethod;
        $this->_params = $params;
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
    
    protected function _response($data, $code = 200, $type = '', $message = '') {
        if($message != ''){
            header("HTTP/1.1 " . $code . " " . $message);
        }else{
            header("HTTP/1.1 " . $code . " " . $this->_rqStatus($code, $type));
        }

        echo json_encode($data);
        
		// Prevent any after response
		exit();
    }
    
    abstract protected function processRequest();
    abstract protected function _rqStatus($code, $type);
}