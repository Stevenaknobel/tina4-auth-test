<?php


\Tina4\Get::add("/monitoredsites/landing", function (\Tina4\Response $response){
    return $response (\Tina4\renderTemplate("/monitoredsites/grid.twig"), HTTP_OK, TEXT_HTML);
});
        
/**
 * CRUD Prototype MonitoredSites Modify as needed
 * Creates  GET @ /path, /path/{id}, - fetch,form for whole or for single
            POST @ /path, /path/{id} - create & update
            DELETE @ /path/{id} - delete for single
 */
\Tina4\Crud::route ("/monitoredsites", new MonitoredSites(), function ($action, MonitoredSites $monitoredSites, $filter, \Tina4\Request $request) {
    switch ($action) {
       case "form":
       case "fetch":
            //Return back a form to be submitted to the create
             
            if ($action == "form") {
                $title = "Add MonitoredSites";
                $savePath =  TINA4_SUB_FOLDER . "/monitoredsites";
                $content = \Tina4\renderTemplate("/monitoredsites/form.twig", []);
            } else {
                $title = "Edit MonitoredSites";
                $savePath =  TINA4_SUB_FOLDER . "/monitoredsites/".$monitoredSites->siteId;
                $content = \Tina4\renderTemplate("/monitoredsites/form.twig", ["data" => $monitoredSites]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#monitoredSitesForm').valid() ) { saveForm('monitoredSitesForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = "{$filter["where"]}";
            }
        
            return   $monitoredSites->select ("*", $filter["length"], $filter["start"])
                ->where("{$where}")
                ->orderBy($filter["orderBy"])
                ->asResult();
        break;
        case "create":
            //Manipulate the $object here
        break;
        case "afterCreate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoredSitesGrid.ajax.reload(null, false); showMessage ('MonitoredSites Created');</script>"];
        break;
        case "update":
            //Manipulate the $object here
        break;    
        case "afterUpdate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>monitoredSitesGrid.ajax.reload(null, false); showMessage ('MonitoredSites Updated');</script>"];
        break;   
        case "delete":
            //Manipulate the $object here
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>monitoredSitesGrid.ajax.reload(null, false); showMessage ('MonitoredSites Deleted');</script>"];
        break;
    }
});