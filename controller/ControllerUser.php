<?php

require_once 'framework/View.php';
require_once 'framework/Controller.php';
require_once 'model/User.php';

class ControllerUser extends Controller
{

    //page d'accueil. 
    public function index() :void
    {
        if (isset($_GET["param1"])) {
            $this->redirect('profile');
        }
    }

    public function logout() :void
    {
        Controller::logout();
    }

    public function profile() {
        $user = $this->get_user_or_redirect();
        
        (new View("profile"))->show(array("user" => $user));//show may throw Exception
    }

}