<?php
/**
* Loader class 
* 
* Includes files and loads classes from the system
* Work simialr to Factory pattern
* 
* @file			Loader.class.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class Loader {
	
	protected $env = '';
	protected $namespace = '';
	
	public function __construct()
	{
		
	}
	
	public function env($env = null)
	{
		if($env){
			$this->env = $env;
		}
		
		return $this;
	}
	
	public function core($class = '')
	{
		require_once ROOT_PATH . 'core' . DIRECTORY_SEPARATOR . $class . '.class.php';
	}
	
	public function controller($class = '')
	{
		require_once ROOT_PATH . $this->env . DIRECTORY_SEPARATOR . 'controllers' . DIRECTORY_SEPARATOR . $class . '.controller.php';
	}
	
	public function model($class = '')
	{
		require_once ROOT_PATH . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . $class . '.model.php';
	}
}