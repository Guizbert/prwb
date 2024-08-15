<?php
require_once 'model/Repartition_templates.php';
require_once 'model/Repartition_template_items.php';
require_once 'model/User.php';
require_once 'model/Operation.php';
require_once 'model/Tricounts.php';
require_once 'model/Participations.php';
require_once 'framework/View.php';
require_once 'framework/Controller.php';

class ControllerAdmin extends Controller{


    public function index(): void{
        $this->redirect('admin');
    }

    public function admin(){
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        if($user->isAdmin()){
            $listOtherTricount =[];
            $listTricount=[];
            $listofUser = User::get_all();
            $userSelected = User::get_by_id($_POST["select"] ?? null);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $listTricount = Tricounts::list($userSelected->getUserId());

                $listOtherTricount = Tricounts::getOther($userSelected->getUserId());
               
            }
            (new View("admin"))->show(
                array(
                    "user" => $user,
                    "users" => $listofUser,
                    "userSelected" => $userSelected,
                    "userTricount" => $listTricount,
                    "otherTricount" => $listOtherTricount
                )
            );
        }else{
            $this->redirect("main","error");
        }
    }

    public function addUser(){
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        //var_dump($_POST);
        if($user->isAdmin()){
           if ((isset($_POST["names"]) && $_POST["names"]!="") && (isset($_GET["param1"]) && $_GET["param1"]!="")) {
            $idUser = $_POST["names"];
            $idTricount = $_GET['param1'];
            var_dump($idTricount);
            var_dump($idUser);
            $newSubscriber = new Participations($idTricount , $idUser );
            if($newSubscriber == NULL){
              $this->redirect("tricount","index");
            }
            $newSubscriber->add();
            $this->redirect("admin","admin");die();
          }
        }
    }

    public function admin2(){
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        if($user->isAdmin()){
            $listTricount= Tricounts::get_all();
            //$listofUser = User::get_all();
            $tricountSelected = Tricounts::get_by_id($_POST["select"] ?? null);
            $operations = [];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                
                $operations = Operation::get_operations_by_tricount($tricountSelected->get_id());
               
               
            }
            (new View("admin2"))->show(
                array(
                    "user" => $user,
                    "tricount" => $listTricount,
                    "tricountSelected"=> $tricountSelected,
                    "operations"=> $operations
                )
            );
        }else{
            $this->redirect("main","error");
        }
    }
    
    public function delete_service(){
        if(isset($_GET['param1']) && $_GET['param1'] !== ""){
            $operation = Operation::getOperationByOperationId($_GET['param1']);
            $operation = $operation->delete();
        }
    }

}

?>