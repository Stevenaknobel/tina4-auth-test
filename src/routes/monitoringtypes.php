<?php


\Tina4\Get::add("/monitoringtypes/landing", function (\Tina4\Response $response){
    return $response (\Tina4\renderTemplate("/monitoringtypes/grid.twig"), HTTP_OK, TEXT_HTML);
});

/**
 * CRUD Prototype MonitoringTypes Modify as needed
 * Creates  GET @ /path, /path/{id}, - fetch,form for whole or for single
            POST @ /path, /path/{id} - create & update
            DELETE @ /path/{id} - delete for single
 */
\Tina4\Crud::route ("/monitoringtypes", new MonitoringTypes(), function ($action, MonitoringTypes $monitoringTypes, $filter, \Tina4\Request $request) {
    switch ($action) {
       case "form":
       case "fetch":
            //Return back a form to be submitted to the create

            if ($action == "form") {
                $title = "Add MonitoringTypes";
                $savePath =  TINA4_SUB_FOLDER . "/monitoringtypes";
                $content = \Tina4\renderTemplate("/monitoringtypes/form.twig", []);
            } else {
                $title = "Edit MonitoringTypes";
                $savePath =  TINA4_SUB_FOLDER . "/monitoringtypes/".$monitoringTypes->typeId;
                $content = \Tina4\renderTemplate("/monitoringtypes/form.twig", ["data" => $monitoringTypes]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#monitoringTypesForm').valid() ) { saveForm('monitoringTypesForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = $filter["where"];
            }

            return $monitoringTypes->select("*", $filter["length"], $filter["start"])
                ->where($where)
                ->orderBy($filter["orderBy"])
                ->asResult();
        break;
        case "create":
            //Manipulate the $object here
        break;
        case "afterCreate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoringTypesGrid.ajax.reload(null, false); showMessage ('MonitoringTypes Created');</script>"];
        break;
        case "update":
            //Manipulate the $object here
        break;    
        case "afterUpdate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoringTypesGrid.ajax.reload(null, false); showMessage ('MonitoringTypes Updated');</script>"];
        break;   
        case "delete":
            //Manipulate the $object here
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>monitoringTypesGrid.ajax.reload(null, false); showMessage ('MonitoringTypes Deleted');</script>"];
        break;
    }
});
