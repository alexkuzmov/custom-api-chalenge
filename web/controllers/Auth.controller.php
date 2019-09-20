<?php
/**
* Auth Controller class
* 
* Controll class for the home page
* 
* @file			Auth.controller.php
* @author		Alex Kuzmov <alexkuzmov@gmail.com>
*   	
*/
class AuthController extends MainController {
	
	public function __construct()
	{
        parent::__construct();
	}
    
	public function loginAction()
	{
        
        // Check if the email is already registered
        if(
            isset($_POST['email']) && strlen($_POST['email']) > 0
            && isset($_POST['password']) && strlen($_POST['password']) > 0
        ){
            
            $this->load()->model('User');
            $userModel = new UserModel($this->_db);
            
            $checkUser = $userModel->getUser([
                'email' => $_POST['email'],
            ]);
            
            // If the user is found we can start the session
            if($checkUser){
                
                $_SESSION['user'] = [
                    'id' => $checkUser['id'],
                    'email' => $checkUser['email'],
                ];
                
                header('Location: /');
                exit();
            }else{
                $this->_smarty->assign('loginError', 'Email already taken');
            }
        }else{
            
            // Check data integrity
            if(!isset($_POST['password']) || strlen($_POST['password']) <= 0){
                $this->_smarty->assign('loginError', 'Password must be provided');
            }
            
            if(!isset($_POST['email']) || strlen($_POST['email']) <= 0){
                $this->_smarty->assign('loginError', 'Email must be provided');
            }
        }
        
        $this->_smarty->display('index.html');
	}
    
	public function logoutAction()
	{
        unset($_SESSION['user']);
        header('Location: /');
        exit();
	}
    
	public function registerAction()
	{
        
        // Check if the email is already registered
        if(
            isset($_POST['email']) && strlen($_POST['email']) > 0
            && isset($_POST['password']) && strlen($_POST['password']) > 0
        ){
            
            $this->load()->model('User');
            $userModel = new UserModel($this->_db);
            
            $checkUser = $userModel->getUser([
                'email' => $_POST['email'],
            ]);
            
            // If the user is found we must return an error
            if(!$checkUser){
                
                $userID = $userModel->addUser([
                    'email' => trim(addslashes($_POST['email'])),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                ]);
                
                // If the user is inserted we can start the session
                if($userID){
                    $_SESSION['user'] = [
                        'id' => $userID,
                        'email' => $_POST['email'],
                    ];
                    
                    header('Location: /');
                    exit();
                }else{
                    $this->_smarty->assign('registerError', 'Unexpected errors');
                }
            }else{
                $this->_smarty->assign('registerError', 'Email already taken');
            }
        }else{
            
            // Check data integrity
            if(!isset($_POST['email']) || strlen($_POST['email']) <= 0){
                $this->_smarty->assign('registerError', 'Email must be provided');
            }
            
            if(!isset($_POST['password']) || strlen($_POST['password']) <= 0){
                $this->_smarty->assign('registerError', 'Password must be provided');
            }
        }
        
        $this->_smarty->display('index.html');
	}
}