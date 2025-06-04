<?php


\Tina4\Get::add("/tokens/landing", function (\Tina4\Response $response){
    return $response (\Tina4\renderTemplate("/tokens/grid.twig"), HTTP_OK, TEXT_HTML);
});

/**
 * CRUD Prototype Tokens Modify as needed
 * Creates  GET @ /path, /path/{id}, - fetch,form for whole or for single
            POST @ /path, /path/{id} - create & update
            DELETE @ /path/{id} - delete for single
 */
\Tina4\Crud::route ("/tokens", new Tokens(), function ($action, Tokens $tokens, $filter, \Tina4\Request $request) {
    switch ($action) {
       case "form":
       case "fetch":
            //Return back a form to be submitted to the create

            if ($action == "form") {
                $title = "Add Tokens";
                $savePath =  TINA4_SUB_FOLDER . "/tokens";
                $content = \Tina4\renderTemplate("/tokens/form.twig", []);
            } else {
                $title = "Edit Tokens";
                $savePath =  TINA4_SUB_FOLDER . "/tokens/".$tokens->tokenId;
                $content = \Tina4\renderTemplate("/tokens/form.twig", ["data" => $tokens]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#tokensForm').valid() ) { saveForm('tokensForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = $filter["where"];
            }

            return $tokens->select("*", $filter["length"], $filter["start"])
                ->where($where)
                ->orderBy($filter["orderBy"])
                ->asResult();
        break;
        case "create":
            //Manipulate the $object here
        break;
        case "afterCreate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>tokensGrid.ajax.reload(null, false); showMessage ('Tokens Created');</script>"];
        break;
        case "update":
            //Manipulate the $object here
        break;    
        case "afterUpdate":
           //return needed 
           return (object)["httpCode" => 200, "message" => "<script>tokensGrid.ajax.reload(null, false); showMessage ('Tokens Updated');</script>"];
        break;   
        case "delete":
            //Manipulate the $object here
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>tokensGrid.ajax.reload(null, false); showMessage ('Tokens Deleted');</script>"];
        break;
    }
});
