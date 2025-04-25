<?php


\Tina4\Get::add("/monitoringhistory/landing", function (\Tina4\Response $response){
    return $response (\Tina4\renderTemplate("/monitoringhistory/grid.twig"), HTTP_OK, TEXT_HTML);
});
        
/**
 * CRUD Prototype MonitoringHistory Modify as needed
 * Creates  GET @ /path, /path/{id}, - fetch,form for whole or for single
            POST @ /path, /path/{id} - create & update
            DELETE @ /path/{id} - delete for single
 */
\Tina4\Crud::route ("/monitoringhistory", new MonitoringHistory(), function ($action, MonitoringHistory $monitoringHistory, $filter, \Tina4\Request $request) {
    switch ($action) {
       case "form":
       case "fetch":
            //Return back a form to be submitted to the create
             
            if ($action == "form") {
                $title = "Add MonitoringHistory";
                $savePath =  TINA4_SUB_FOLDER . "/monitoringhistory";
                $content = \Tina4\renderTemplate("/monitoringhistory/form.twig", []);
            } else {
                $title = "Edit MonitoringHistory";
                $savePath =  TINA4_SUB_FOLDER . "/monitoringhistory/".$monitoringHistory->historyId;
                $content = \Tina4\renderTemplate("/monitoringhistory/form.twig", ["data" => $monitoringHistory]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#monitoringHistoryForm').valid() ) { saveForm('monitoringHistoryForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = "{$filter["where"]}";
            }
        
            return   $monitoringHistory->select ("*", $filter["length"], $filter["start"])
                ->where("{$where}")
                ->orderBy($filter["orderBy"])
                ->asResult();
        break;
        case "create":
            //Manipulate the $object here
        break;
        case "afterCreate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoringHistoryGrid.ajax.reload(null, false); showMessage ('MonitoringHistory Created');</script>"];
        break;
        case "update":
            //Manipulate the $object here
        break;    
        case "afterUpdate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoringHistoryGrid.ajax.reload(null, false); showMessage ('MonitoringHistory Updated');</script>"];
        break;   
        case "delete":
            //Manipulate the $object here
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>monitoringHistoryGrid.ajax.reload(null, false); showMessage ('MonitoringHistory Deleted');</script>"];
        break;
    }
});