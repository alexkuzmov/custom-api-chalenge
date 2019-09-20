<?php
/**
* API class 
* 
* API control class, chooses proper endpoint
* 
* @file			API.class.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class API {
    protected $_defaultPath = 'api/';

    protected $_db = null;
	protected $_loader = null;
	
    protected $_urlArray = array();
    protected $_rqMethod = '';
    protected $_version = '';
    protected $_endPoint = '';
    protected $_params = array();

    public function __construct($_urlArray) {
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header("Content-Type: application/json");
		
		$this->_urlArray = $_urlArray;

        $this->_rqMethod = $_SERVER['REQUEST_METHOD'];

        if ($this->_rqMethod == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->_rqMethod = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->_rqMethod = 'PUT';
            } else {
                $this->_rqMethod = '';
            }
        }

        if($this->_rqMethod == ''){
            $this->_response('', 400, 'request_method_unknown');
            return;
        }

        $this->_version = (isset($this->_urlArray[4]) ? $this->_urlArray[4] : '');

        if($this->_version == ''){
            $this->_response('', 400, 'api_version_missing');
            return;
        }

        $this->_endPoint = (isset($this->_urlArray[5]) ? $this->_urlArray[5] : '');

        if($this->_endPoint == ''){
            $this->_response('', 400, 'api_endpoint_missing');
            return;
        }

        $l = count($this->_urlArray);

        for($i = 6; $i < $l; $i++){
            if($i + 1 < $l) {
                if ($this->_urlArray[$i] != '' && $this->_urlArray[$i + 1] != null){
                    $this->_params[$this->_urlArray[$i]] = $this->_urlArray[$i + 1];
                }
            }

            if($i + 1 == $l) {
                if (
                    $this->_urlArray[$i] != ''
                    && (floatval($this->_urlArray[$i]) > 0 || intval($this->_urlArray[$i]) > 0)
                ){
                    $this->_params['id'] = $this->_urlArray[$i];
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
        // Load manin endpoint class
        require_once ROOT_PATH . $this->_defaultPath . 'Main.EndPoint.php';
        
        $endPointName = ucfirst(strtolower($this->_endPoint));
        $endPointClass = $endPointName . 'EndPoint';
        $endPointPath = ROOT_PATH . $this->_defaultPath . $this->_version . '/' . $endPointName . '.EndPoint.php';

        if(file_exists($endPointPath)){
            require_once $endPointPath;

            if(class_exists($endPointClass)){
                $endPoint = new $endPointClass($this->_rqMethod, $this->_params);
                $endPoint
                    ->load($this->_loader)
                    ->db($this->_db)
                ->processRequest();
            }else{
                $this->_response('', 400, '', 'Requested endpoint file found, endpoint class ['.$endPointClass.'] is missing.');
            }
        }else{
            $this->_response('', 400, '', 'Requested endpoint ['.$endPointClass.'] missing.');
        }
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

    protected function _rqStatus($code, $type = ''){
        $status = array(
            200 => 'OK',
            400 => array(
                'url_invalid' => 'URL is missing or is invalid.',
                'request_method_unknown' => 'Request method unknown.',
                'api_version_missing' => 'API version missing or incorrect.',
                'api_endpoint_missing' => 'API end point missing or incorrect.',
            ),
        );

        if(is_array($status[$code])){
            return $status[$code][$type];
        }else{
            return $status[$code];
        }
    }
}