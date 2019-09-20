<?php
/**
* AuthEndPoint class 
* 
* Handles API calls to auth users
* 
* @file			Auth.EndPoint.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class UserModel {
    
    protected $_db;
    
	public function __construct($db)
	{
        $this->_db = $db;
	}
    
    public function getUser($where = [])
    {
        $user = $this->_db
            ->fields(['*'], 'u')
            ->table('users', 'u')
            ->where($where)
        ->fetchRow();

        return $user;
    }
    
    public function addUser($params)
    {
        $id = $this->_db
            ->table("users")
            ->fields($params)
        ->insert();
        
        // Return id of the inserted row
        return $id;
    }
}