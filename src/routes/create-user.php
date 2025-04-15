<?php

\Tina4\Get::add("/create-user", function (\Tina4\Response $response) {
    return $response(\Tina4\renderTemplate("auth/create-user.twig"));
});

\Tina4\Post::add("/create-user", function (\Tina4\Response $response, \Tina4\Request $request) {
    $username = $request->params['username'];
    $rawPassword = $request->params['password'];
    $companyId = $request->params['company_id']; // Assuming company is selected or known

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
