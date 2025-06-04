<?php

// Show the form (GET)
\Tina4\Get::add("/create-site", function (\Tina4\Response $response) {
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    $types = (new MonitoringTypes())->select()->asArray();

    // If user is global admin, get all companies for the dropdown
    $companies = [];
    if (isset($_SESSION["role"]) && $_SESSION["role"] === "global_admin") {
        $companies = (new Companies())->select()->orderBy("company_name")->asArray();
    }

    return $response(\Tina4\renderTemplate("sites/create.twig", [
        "types" => $types,
        "companies" => $companies
    ]));
});

// Handle the form (POST)
\Tina4\Post::add("/create-site", function (\Tina4\Response $response, \Tina4\Request $request) {
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");
    }

    $user = (new Users())->load("user_id = ?", [$_SESSION["user_id"]]);
    $isGlobalAdmin = (isset($_SESSION["role"]) && $_SESSION["role"] === "global_admin");

    $siteName = trim($request->params["site_name"]);
    $url = trim($request->params["url"]);
    $typeId = (int)$request->params["type_id"];
    $tags = trim($request->params["tags"] ?? "");

    // Determine company ID
    if ($isGlobalAdmin && !empty($request->params["company_id"])) {
        $companyId = (int)$request->params["company_id"];
    } else {
        $companyId = $user->companyId;
    }

    if (!$siteName || !$url) {
        // Get companies for global admin if form validation fails
        $companies = [];
        if ($isGlobalAdmin) {
            $companies = (new Companies())->select()->orderBy("company_name")->asArray();
        }

        return $response(\Tina4\renderTemplate("sites/create.twig", [
            "error" => "Site name and URL are required!",
            "types" => (new MonitoringTypes())->select(),
            "companies" => $companies
        ]));
    }

    // Create the monitored site
    $site = new MonitoredSites();
    $site->companyId = $companyId;
    $site->siteName = $siteName;
    $site->url = $url;
    $site->typeId = $typeId;
    $site->status = "pending";
    $site->tags = $tags;
    $site->save();

    // Create initial monitoring record
    if ($typeId) {
        $monitoring = new Monitoring();
        $monitoring->siteId = $site->siteId;
        $monitoring->typeId = $typeId;
        $monitoring->status = "not yet checked";
        $monitoring->save();
    }

    // Save type-specific configuration for API monitoring types
    if ($typeId == 2 || $typeId == 3 || $typeId == 4) { // API GET, POST, or HTTP
        $config = new MonitoringConfig();
        $config->siteId = $site->siteId;

        // Process request headers (convert to JSON if not empty)
        if (!empty($request->params["request_headers"])) {
            $config->requestHeaders = $request->params["request_headers"];
        }

        // Process request body (only relevant for POST)
        if ($typeId == 3 && !empty($request->params["request_body"])) {
            $config->requestBody = $request->params["request_body"];
        }

        // Process expected response
        if (!empty($request->params["expected_response"])) {
            $config->expectedResponse = $request->params["expected_response"];
        }

        // Process expected status code
        if (!empty($request->params["expected_status_code"])) {
            $config->expectedStatusCode = (int)$request->params["expected_status_code"];
        }

        $config->save();
    }

    \Tina4\redirect("/landing-page");
});
