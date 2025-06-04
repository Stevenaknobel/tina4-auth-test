<?php

\Tina4\Get::add("/create-user", function (\Tina4\Response $response) {
    $companies = (new Companies())->select("*")->where("company_id" > 0)->orderBy("company_name")->asArray();

    if( empty($companies)) {
        $baseCompany = (new Companies());
        $baseCompany->company_name = "eComplete";
        $baseCompany->save();

        $companies = (new Companies())->select("*")->where("company_id" > 0)->orderBy("company_name")->asArray();

    }

    return $response(\Tina4\renderTemplate("auth/create-user.twig", [
        "companies" => $companies
    ]));
});

\Tina4\Post::add("/create-user", function (\Tina4\Response $response, \Tina4\Request $request) {
    $username = $request->params['username'];
    $rawPassword = $request->params['password'];
    $companyId = $request->params['companyId']; // Assuming a company is selected or known

    // Hash the password before saving
    $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

    $user = new Users();
    $user->username = $username;
    $user->password = $hashedPassword;
    $user->companyId = $companyId;

    if ($user->save()) {
        \Tina4\redirect("/login?success=User created, please login.");
    } else {
        \Tina4\redirect("/create-user?error=Could not create user.");
    }
});
