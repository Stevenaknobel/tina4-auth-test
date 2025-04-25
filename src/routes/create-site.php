<?php

// Show the form (GET)
\Tina4\Get::add("/create-site", function (\Tina4\Response $response) {
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    $types = (new MonitoringTypes())->select()->asArray();
    file_put_contents("debug-types.txt", print_r($types, true));
    return $response(\Tina4\renderTemplate("sites/create.twig", [
        "types" => $types
    ]));
});

// Handle the form (POST)
\Tina4\Post::add("/create-site", function (\Tina4\Response $response, \Tina4\Request $request) {
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    $user = (new Users())->load("user_id = ?", [$_SESSION["user_id"]]);

    $siteName = trim($request->params["site_name"]);
    $url = trim($request->params["url"]);
    $typeId = $request->params["type_id"];

    if (!$siteName || !$url) {
        return $response(\Tina4\renderTemplate("sites/create.twig", [
            "error" => "Site name and URL are required!",
            "types" => (new MonitoringTypes())->select()
        ]));
    }

    $site = new MonitoredSites();
    $site->companyId = $user->companyId;
    $site->siteName = $siteName;
    $site->url = $url;
    $site->typeId = $typeId;
    $site->status = "pending";
    $site->save();

    if ($typeId) {
        $monitoring = new Monitoring();
        $monitoring->siteId = $site->siteId;
        $monitoring->typeId = $typeId;
        $monitoring->status = "not yet checked";
        $monitoring->save();
    }

    \Tina4\redirect("/landing-page");
});

