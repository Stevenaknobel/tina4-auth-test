<?php


\Tina4\Get::add("/companies/landing", function (\Tina4\Response $response){
    return $response (\Tina4\renderTemplate("/companies/grid.twig"), HTTP_OK, TEXT_HTML);
});

/**
 * CRUD Prototype Companies Modify as needed
 * Creates  GET @ /path, /path/{id}, - fetch,form for whole or for single
            POST @ /path, /path/{id} - create & update
            DELETE @ /path/{id} - delete for single
 */
\Tina4\Crud::route ("/companies", new Companies(), function ($action, Companies $companies, $filter, \Tina4\Request $request) {
    switch ($action) {
       case "form":
       case "fetch":
            //Return back a form to be submitted to the create

            if ($action == "form") {
                $title = "Add Companies";
                $savePath =  TINA4_SUB_FOLDER . "/companies";
                $content = \Tina4\renderTemplate("/companies/form.twig", []);
            } else {
                $title = "Edit Companies";
                $savePath =  TINA4_SUB_FOLDER . "/companies/".$companies->companyId;
                $content = \Tina4\renderTemplate("/companies/form.twig", ["data" => $companies]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#companiesForm').valid() ) { saveForm('companiesForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = $filter["where"];
            }

            return $companies->select("*", $filter["length"], $filter["start"])
                ->where($where)
                ->orderBy($filter["orderBy"])
                ->asResult();
        break;
        case "create":
            //Manipulate the $object here
        break;
        case "afterCreate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>companiesGrid.ajax.reload(null, false); showMessage ('Companies Created');</script>"];
        break;
        case "update":
            //Manipulate the $object here
        break;    
        case "afterUpdate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>companiesGrid.ajax.reload(null, false); showMessage ('Companies Updated');</script>"];
        break;   
        case "delete":
            //Manipulate the $object here
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>companiesGrid.ajax.reload(null, false); showMessage ('Companies Deleted');</script>"];
        break;
    }
});
