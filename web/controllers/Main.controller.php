<?php
/**
* Main Controller class
* 
* Abstract controller class
* 
* @file			Main.controller.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class MainController {
	
    protected $_smarty;
    
	public function __construct()
	{
        // Init template engine
        $this->_smarty = new Smarty();
        $this->_smarty->template_dir = ROOT_PATH . 'web/views/';
        $this->_smarty->compile_dir = '/tmp/views_c/';
        $this->_smarty->config_dir = '/tmp/views_c/';
        $this->_smarty->cache_dir = '/tmp/cache/';
        $this->_smarty->force_compile = true;
        $this->_smarty->compile_check = false;
        $this->_smarty->debugging = false;
        $this->_smarty->caching = true;
        $this->_smarty->cache_lifetime = 3600;
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
}