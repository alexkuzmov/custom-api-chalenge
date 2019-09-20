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
class AuthEndPoint extends MainEndPoint {
    
	public function __construct($rqMethod, $params)
	{
        parent::__construct($rqMethod, $params);
	}
    
    public function processRequest()
    {
        switch($this->_rqMethod){
            case 'POST':
                
                // Check data integrity
                if(!isset($_POST['email']) || strlen($_POST['email']) <= 0){
                    $this->_response('', 400, 'email_missing');
                }
            
                if(!isset($_POST['password']) || strlen($_POST['password']) <= 0){
                    $this->_response('', 400, 'password_missing');
                }
                
                // Check if the email is already registered
                if(isset($_POST['email']) && strlen($_POST['email']) > 0){
                    
                    $this->load()->model('User');
                    $userModel = new UserModel($this->_db);
                    
                    $checkUser = $userModel->getUser([
                        'email' => $_POST['email'],
                    ]);
                    
                    // If the user is found we must return an error
                    if($checkUser){
                        $this->_response('', 400, 'email_already_taken');
                    }else{
                        $this->_response(['success' => true]);
                    }
                }
                
            break;
            
            default:
                $this->_response('', 400, '', 'Request method not supported');
            break;
        }
    }
    
    protected function _rqStatus($code, $type = ''){
        $status = array(
            200 => 'OK',
            400 => array(
                'email_missing' => 'Email must be provided',
                'password_missing' => 'Password must be provided',
                'email_already_taken' => 'Email already taken',
            ),
        );

        if(is_array($status[$code])){
            return $status[$code][$type];
        }else{
            return $status[$code];
        }
    }
}