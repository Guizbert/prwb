<?php
require_once 'model/User.php';
require_once 'framework/View.php';
require_once 'framework/Controller.php';
require_once 'model/tricounts.php';
require_once 'model/participations.php';
require_once 'model/repartitions.php';
require_once 'model/Repartition_templates.php';
require_once 'model/Repartition_template_items.php';

class ControllerOperation extends Controller
{

    public function index(): void
    {
        if (isset($_GET["param1"])) {
            $this->redirect("operation", 'expenses', $_GET['param1']);
        }
    }

    public function expenses()
    {
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error");
        } else {
            $userId = $user->getUserId();
            $checkTricount = Tricounts::exists($_GET['param1']);
            if ($checkTricount <= 0) {
                $this->redirect('main', "error");
            }
            $tricount = Tricounts::get_by_id($_GET['param1']);
            $tricountID = $tricount->get_id();
            $expenses_json = $tricount->get_expenses_as_json();
            $operations_of_tricount = Operation::get_operations_by_tricount($tricountID);
            $participants = Tricounts::number_of_friends($tricountID);
            $amounts[] = Operation::get_operations_by_tricount($tricountID);
            $nbOperations = Operation::getNbOfOperations($tricountID);
            $totalExp = Tricounts::get_total_amount_by_tric_id($tricountID);
            $mytot = Tricounts::get_my_total($userId);
        }
        (new View("expenses"))->show(
            array(
                "user" => $user,
                "tricount" => $tricount,
                "amounts" => $amounts,
                "totalExp" => $totalExp,
                "mytot" => $mytot,
                "participants" => $participants,
                "nbOperations" => $nbOperations,
                "expenses_json" => $expenses_json
            )
        );
    }

    public function balance()
    {
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error");
        }
        $checkTricount = Tricounts::exists($_GET['param1']);
        if ($checkTricount <= 0) {
            $this->redirect('main', "error");
        } else {
            $tricount = Tricounts::get_by_id($_GET['param1']);
            $users = Participations::get_by_tricount($tricount->get_id());
            $operations_of_tricount = Operation::get_operations_by_tricount($tricount->get_id());
            if (is_null($operations_of_tricount)) {
                $this->redirect('operation', "index", $tricount->get_id());
            }
            //var_dump($tricount->get_id()); die();
            $weights = Repartitions::get_by_operation($tricount->get_id());
            $total = Tricounts::get_total_amount_by_tric_id($tricount->get_id());
        }
        (new View("tricount_balance"))->show(
            array(
                "total" => $total,
                "users" => $users,
                "operations_of_tricount" => $operations_of_tricount,
                "user" => $user,
                "tricount" => $tricount,
                "weights" => $weights
            )
        );
    }

    public function detail_expense()
    {
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error");
        }
        $checkId = Operation::exists($_GET['param1']); //check si l'operation existe dans le tricount
        if (empty($checkId)) {
            $this->redirect('main', "error");
        } else {
            $userId = $user->getUserId();
            $operationId = $_GET['param1'];
            $tricount = Tricounts::get_tricount_by_operation_id($operationId);
            $operationUsers = Operation::get_users_from_operation($operationId);
            $debt = Operation::get_dette_by_operation($operationId, $userId);
            $participants = Operation::getNumberParticipantsByOperationId($operationId);
            $operation_data = Operation::getOperationByOperationId($operationId);
            $usr = $operation_data->getInitiator();
        }

        (new View("detail_expense"))->show(
            array(
                "user" => $user,
                "operationUsers" => $operationUsers,
                "debt" => $debt,
                "operation_data" => $operation_data,
                "participants" => $participants,
                "tricount" => $tricount,
                "usr" => $usr
            )
        );

    }

    public function get_template_service(){
        if(isset($_GET['templateId'])){
            $template = $_GET['templateId'];
            $data = Repartition_template_items::get_template_data_service($template);
            echo $data;
        }
    }

    public function getTemplateDataById() {
        // Get the template ID from the request
        $templateId = isset($_GET['templateId']) ? intval($_GET['templateId']) : null;
    
        // Fetch the template data from the model
        $templateData = Repartition_template_items::get_template_data_service($templateId);
    
        // Check if the template data is valid
        if ($templateData !== null) {
            // Return the template data as JSON
            echo json_encode($templateData);
        } else {
            // Return an error message if the template data is not found
            echo json_encode(['error' => 'Template data not found.']);
        }
    }
    
    

    public function refreshBtnHandler($user)
    {
        $errors = [];
        // TODO :       -----------------> IL FAUT GERER LE RTI. SI C'EST OPTION-DEFAULT -> METTRE ERREUR
        if (isset($_POST["refreshBtn"])) {
            $requiredFields = ["title", "tricId", "amount", "operation_date", "initiator"];
            $allFieldsExist = true;
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $_POST)) {
                    $allFieldsExist = false;
                    Tools::abort("pouet pouet");
                }
            }
            if ($allFieldsExist) {
                $action = $_GET['action'];
                $userId = $user->getUserId();
                $title = Tools::sanitize($_POST["title"]);
                $tricId = $_POST["tricId"];
                $tricount = Tricounts::get_by_id($tricId);
                $amount = Tools::sanitize(floatval($_POST["amount"]));
                $operation_date = $_POST["operation_date"];
                $initiator = $_POST["initiator"];
                $users = Participations::get_by_tricount($tricId);
                $init = User::get_by_id($initiator);
                $rti = Repartition_template_items::get_by_user_and_tricount($userId, $tricId); // récup les templates ou le user en fait partie.
                
                $template = Repartition_templates::get_by_id($_POST['rti']);

                $info = [$title, $amount, $operation_date, $initiator];
                //$it = Participations::get_user_weight_in_items($template->get_id(),$userId);
                //isset($_GET['param1']) ? $repartitions = Repartitions::get_by_operation($_GET['param1']) : null;
                //$repartitions = Repartitions::get_by_operation($_GET['param1'] ? $_GET['param1'] : null);
                // var_dump($template);
                if(!is_null($template))
                    $templateId = $template->id;

                if ($template === null) {
                    $this->redirect("operation", "expenses/" . $tricount->get_id());
                }
                isset($_GET['param1']) ? $operationId = $_GET['param1'] : null;
                $ListUsers = Participations::get_by_tricount($tricId);
                $items = Repartition_template_items::get_user_by_repartition($template->get_id());
                if(isset($_POST['operationId']))
                    $operation = Operation::get_by_id($_POST['operationId']);
                else
                    $operation = null;
                
            }

        }
        (new View("add_expense"))->show(
            array(
                "user" => $user,
                "operation" => $operation,
                "rti" => $rti,
                "templateId" => $templateId,
                "users" => $users,
                "tricount" => $tricount,
                "template" => $template,
                "ListUsers" => $ListUsers,
                "info" => $info,
                "items" => $items,
                "init"=>$init,
                "errors" => $errors,
                "action" => $action
            )
        );
    }


    public function saveOperation($user)
    {
        $action = $_GET['action'];
        $title = $_POST["title"];
        $tricountId = $_POST["tricId"];
        $amount = $_POST["amount"];
        $operation_date = $_POST["operation_date"];
        $initiator = $_POST["initiator"];
        $created_at = date('y-m-d h:i:s');
        $errors = [];

        if (!$title || !$tricountId || !$amount || !$operation_date || !$initiator) {
            // Handle missing fields error
            return;
        }
        

        $title = Tools::sanitize($title);
        $tricount = Tricounts::get_by_id($tricountId);
        $amount = Tools::sanitize(floatval($amount));
        $init = User::get_by_name($initiator);
        $users = Participations::get_by_tricount($tricountId);
        $rti = Repartition_template_items::get_by_user_and_tricount($initiator, $tricountId);
        $template = Repartition_templates::get_by_id($_POST['rti']);
        // var_dump($_POST); die();
        //$ListUsers = Participations::get_by_tricount($tricountId);
        // var_dump($tricount);
        // var_dump($initiator); die();
        if (!$tricount || !$initiator) {
            $this->redirect("main", "error", "save");
            return;
        }

        
        if(isset($_POST['c']) && isset($_POST['w'])){
            $checkedUsers = $_POST['c']; $weights = $_POST['w'];
        }
        //$errors = $operation->validateTitle($title);
        $info = [$title, $amount, $operation_date, $initiator];

        // si l'user met pas de weight, la fonction qui va save la repartition mettra le weight a 1 par défaut.
        if (isset($_POST['c']) || !empty($_POST['c'])) { // check s'il y a au moins un user checked
            $operation = new Operation($title, $tricountId, $amount, $operation_date, $initiator, $created_at);
            $errors = $operation->validate();
        }else{
            $errors[] = "you must check one user";
        }
        if(empty($errors)){ // check si l'opération est correcte.
            $operation->insert();
            if(isset($_POST['save_template']) && $_POST['tempalte_name'] !== ""){
                $this->save_Template($operation,$checkedUsers,$weights, $_POST['tempalte_name']);
            }
            if($this->saveOperationRepartition($operation,$checkedUsers,$weights)){
                $this->redirect("operation", "expenses", $_POST["tricId"]);
            }
        }else
            (new View("add_expense"))->show(
                array(
                    "user" => $user,
                    "info"=>$info,
                    "rti" => $rti,
                    "users" => $users,
                    "tricount" => $tricount,
                    "template" => $template,
                    "errors" => $errors,
                    "action" => $action,
                )
            );
    }

    public function add()
    {
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error", "add");
        } else {
            $userId = $user->getUserId();
            $tricount = Tricounts::get_by_id($_GET['param1']);
            $users = Participations::get_by_tricount($_GET['param1']);
            $rti = Repartition_template_items::get_by_user_and_tricount($userId, $_GET['param1']);
            $action = $_GET['action'];
            $repartitions = [];
        }
        (new View("add_expense"))->show(
            array(
                "user" => $user,
                "tricount" => $tricount,
                "rti" => $rti,
                "users" => $users,
                "action" => $action,
                "repartitions" => $repartitions
            )
        );

    }
    //TODO garder les weights en cas d'erreur ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    public function add_expense()
    {
        $user = $this->get_user_or_redirect();
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error", "add2");
        }
        //if i choose a template from the templates list
        if (isset($_POST["refreshBtn"])) {
            $this->refreshBtnHandler($user);
        } else if ($_SERVER['REQUEST_METHOD'] === 'POST' ) {
          $this->saveOperation($user);
            //if i make a custom template => need save name checked
        } 
    }

    public function edit()
    {
        
        $user = $this->get_user_or_redirect();
        $user = User::get_by_id($user->getUserId());
        $checkOperation = Operation::exists($_GET['param1']);
        
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error", "aici1");
        }
        if ($checkOperation <= 0) {
            $this->redirect('main', "error", "aici2");
        } else {
            
            $action = $_GET['action'];
            $userId = $user->getUserId();
            $tricount = Tricounts::get_tricount_by_operation_id($_GET['param1']);
            $operationId = $_GET['param1'];
            $operation = Operation::getOperationByOperationId($operationId);
            $users = Participations::get_by_tricount($tricount->get_id());
            $repartitions = Repartitions::get_by_operation($operationId);
            $rti = Repartition_template_items::get_by_user_and_tricount($userId, $tricount->get_id());
        }

        (new View("add_expense"))->show(
            array(
                "user" => $user,
                "action" => $action,
                "operation" => $operation,
                "users" => $users,
                "rti" => $rti,
                "tricount" => $tricount,
                "repartitions" => $repartitions,
                "operationId" => $operationId
                
            )

        );

    }

    public function edit_expense()
    {
        $user = $this->get_user_or_redirect();
        $action = $_GET['action'];
        $operationId = isset($_POST['operationId']) ? $_POST['operationId'] : null ;
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', 'error', 'aici3');
        }
        //$operationId = $_POST['operationId'];

        //$operation = Operation::getOperationByOperationId($operationId);
        // var_dump($operation);
        // if (!$operation) {
        //     $this->redirect('main', 'error','aici4');
        // }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (isset($_POST['save_template'])) {
            $save_template = $_POST['save_template'] === 'on';
        } else {
            $save_template = false;
        }

        if (isset($_POST["refreshBtn"])) {
            $this->refreshBtnHandler($user);

            
        } else {
            $title = Tools::sanitize($_POST['title']);
            $operation = Operation::get_by_id($operationId);
            $tricountId = $operation->tricount;
            $tricount = Tricounts::get_by_id($tricountId);
            $amount = Tools::sanitize(floatval($_POST['amount']));
            $operation_date = $_POST['operation_date'];
            $initiator = User::get_by_id($_POST['initiator']);

            $created_at = date('y-m-d h:i:s');
            // var_dump($amount); die();
          

            $operation->setTitle($title);
            $operation->setTricount($tricountId);
            $operation->setAmount($amount);
            $operation->setOperation_date($operation_date);
            $operation->setInitiator($initiator->getUserId());
            $operation->setCreated_at($created_at);
            $operation->setId($operationId);

            $errors = $operation->validateForEdit($title);
            if(empty($_POST['c'])){
                $errors[] = "you've to check at least one user";
            }else if(empty($_POST['w'])){
                $errors[] = "you have to put some weights";
            }
            
            if (empty($errors)) {
                $operation->update();
                // Update repartition
                if($this->saveOperationRepartition($operation, $_POST['c'], $_POST['w'])){
                    $this->redirect("operation", "expenses", $tricountId);
                }
            } else{
                $users = Participations::get_by_tricount($tricount->get_id());
                (new View("add_expense"))->show(
                    array(
                        "title" => $title,
                        "amount" => $amount,
                        "initiator" => $initiator,
                        "tricount" => $tricount,
                        "operation" => $operation,
                        "users"=>$users,
                        // "template" => $template,
                        "errors" => $errors,
                        "action" => $action
                    )
                );
            }
        }
            
    }

    private function saveOperationRepartition($operation, $checkedUsers, $weights) : bool{
        $ok = false;
        $combine_array = Repartition_template_items::combine_array($checkedUsers, $weights);
        if(!is_null($operation)){
            if(!is_null(Repartitions::get_by_operation($operation->get_id())))
                Operation::deleteRepartition($operation->get_id());
            foreach($combine_array as $user_id => $weight) {                        
                if($weight ==="" || $weight === "0" )
                    $weight = 1;
                Operation::insertRepartition($operation->get_id(), $weight, $user_id); 
            };
            $ok = true;
        }
        return $ok;
    }

    private function save_Template( $operation, $checkedUsers, $weights, $template_name){
        $combine_array = Repartition_template_items::combine_array($checkedUsers, $weights);
        $template = new Repartition_templates(null, $template_name, $operation->getTricount());
        $template->newTemplate($template_name, $operation->getTricount());
        if($template !== null){
            foreach($combine_array as $user_id => $weight){
                if($weight === "" || $weight ==="0"){
                    $weight = 1;
                }
                Repartition_template_items::addNewItems($user_id, $template->get_id(), $weight);
            }
        }
    }


    public function delete_confirm()
    {
        $user = $this->get_user_or_redirect();
        $errors = [];
        $operationId = $_GET['param1'];
        $checkOperation = Operation::exists($_GET['param1']);
        if ($checkOperation <= 0) {
            $this->redirect('main', "error");
        }
        $operation_data = Operation::getOperationByOperationId($operationId);
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error");
        } else {
            $userId = $user->getUserId();
        }
        if (isset($_GET["param1"])) {

            (new View("delete_operation"))->show(array("user" => $user, "operationId" => $operationId, "operation_data" => $operation_data));
        }
    }

    public function delete_operation()
    {
        $user = $this->get_user_or_redirect();
        $errors = [];
        $operationId = $_GET['param1'];
        if (isset($_GET['param1']) && !is_numeric($_GET['param1'])) {
            $this->redirect('main', "error");
        } else {
            $userId = $user->getUserId();
            $operation = Operation::getOperationByOperationId($operationId);
            if ($operation === null) {
                $this->redirect("main", "error");
            }
        }
        if (isset($_POST['submitted'])) {
            if ($_POST['submitted'] === "Cancel") {
                $this->redirect("operation", "add_expense", $operationId);
            } else if ($_POST['submitted'] === "Delete") {
                $tricount = Tricounts::get_tricount_by_operation_id($operationId);
                $tricountId = $tricount->get_id();
                $operation = $operation->delete();
                $this->redirect("operation", "expenses", $tricountId);
            }
        }

        (new View("add_expense"))->show(array("user" => $user, "tricount" => $tricount));

    }


    public function next_expense()
    {
        if (isset($_POST["tricount_id"]) && isset($_POST["operation"])) {
            $idTricount = $_POST["tricount_id"];
            $tricount = Tricounts::get_by_id($idTricount);
            $idOperation = $_POST["operation"];
            $operation = Operation::get_by_id($idOperation);
            if ($_POST["submit"] === "Next")
                $nextOperation = $operation->get_next_operation_by_tricount($idOperation, $tricount->get_id());
            else if ($_POST["submit"] === "Previous") {
                $nextOperation = $operation->get_previous_operation_by_tricount($idOperation, $tricount->get_id());
            }
            if ($nextOperation) {
                $this->redirect("operation", "detail_expense", $nextOperation->get_id());
            } else {
                $this->redirect("operation", "detail_expense", $_POST["operation"]);
            }
        }
    }
// /**      idée de fonction si on doit mettre une fonction a part pour previous_experience
//  * public function previous_experience(){
//     if(isset($_POST["tricount_id"])&& isset($_POST["operation"]) ){
//         $idTricount = $_POST["tricount_id"];
//         $tricount = Tricounts::get_by_id($idTricount);
//         $idOperation = $_POST["operation"];
//         $operation = Operation::get_by_id($idOperation);


//         $prevOperation = $operation->get_prev_operation_by_tricount($idOperation,$tricount->get_id());
//         if($prevOperation){
//             $this->redirect("operation", "detail_expense", $prevOperation->get_id());
//         }
//         else{
//             $this->redirect("operation", "detail_expense", $_POST["operation"]);
//         }
//     }
// }
// */
}

?>