<?php


\Tina4\Get::add("/monitoring/landing", function (\Tina4\Response $response){
    return $response (\Tina4\renderTemplate("/monitoring/grid.twig"), HTTP_OK, TEXT_HTML);
});

/**
 * CRUD Prototype Monitoring Modify as needed
 * Creates  GET @ /path, /path/{id}, - fetch,form for whole or for single
            POST @ /path, /path/{id} - create & update
            DELETE @ /path/{id} - delete for single
 */
\Tina4\Crud::route ("/monitoring", new Monitoring(), function ($action, Monitoring $monitoring, $filter, \Tina4\Request $request) {
    switch ($action) {
       case "form":
       case "fetch":
            //Return back a form to be submitted to the create

            if ($action == "form") {
                $title = "Add Monitoring";
                $savePath =  TINA4_SUB_FOLDER . "/monitoring";
                $content = \Tina4\renderTemplate("/monitoring/form.twig", []);
            } else {
                $title = "Edit Monitoring";
                $savePath =  TINA4_SUB_FOLDER . "/monitoring/".$monitoring->monitoringId;
                $content = \Tina4\renderTemplate("/monitoring/form.twig", ["data" => $monitoring]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#monitoringForm').valid() ) { saveForm('monitoringForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = $filter["where"];
            }

            return $monitoring->select("*", $filter["length"], $filter["start"])
                ->where($where)
                ->orderBy($filter["orderBy"])
                ->asResult();
        break;
        case "create":
            //Manipulate the $object here
        break;
        case "afterCreate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoringGrid.ajax.reload(null, false); showMessage ('Monitoring Created');</script>"];
        break;
        case "update":
            //Manipulate the $object here
        break;    
        case "afterUpdate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoringGrid.ajax.reload(null, false); showMessage ('Monitoring Updated');</script>"];
        break;   
        case "delete":
            //Manipulate the $object here
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>monitoringGrid.ajax.reload(null, false); showMessage ('Monitoring Deleted');</script>"];
        break;
    }
});
