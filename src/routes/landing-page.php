<?php

/**
 * @description Landing page after login
 */

\Tina4\Get::add("/landing-page", function (\Tina4\Response $response, \Tina4\Request $request ) {

    \Tina4\Debug::message("Landing page route hit!", TINA4_LOG_CRITICAL);
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");  // Redirect to login if not logged in
    }

    $user = (new Users())->load("user_id = ?", [$_SESSION["user_id"]]);

    global $DBA;

    // Interpolate directly (safe here because it's an integer from session-loaded user object)
    $companyId = (int)$user->companyId;

    $monitoredSites = $DBA->fetch("SELECT * FROM monitored_sites WHERE company_id = {$companyId}", 1000)->asArray();

    \Tina4\Debug::message("User company ID: " . $user->companyId);
    \Tina4\Debug::message("Raw monitoredSites output:");
    //\Tina4\Debug::message("Company has " . count($monitoredSites) . " monitored sites.");
    \Tina4\Debug::message("Raw site data: " . json_encode($monitoredSites));
    \Tina4\Debug::message("Raw SQL: select * from monitored_sites where company_id = {$user->companyId}");

    // Attach latest monitoring result & type name per site
    $sitesWithMonitoring = [];

    foreach ($monitoredSites as $site) {
        //Fetch the latest monitoring result for each site
        $latestMonitoring = (new Monitoring())->load("site_id = ? ORDER BY created_at DESC", [$site["site_id"]]);

         // Debug the monitoring result
         \Tina4\Debug::message("Latest monitoring data for site {$site['site_name']} (ID: {$site['site_id']}): " . json_encode($latestMonitoring));

        $monitoringType = null;
        if ($latestMonitoring && $latestMonitoring->typeId) {
            $monitoringType = (new MonitoringTypes())->load("type_id = ?", [$latestMonitoring->typeId]);
        }

        $sitesWithMonitoring[] = [
            "siteId" => $site["site_id"],
            "siteName" => $site["site_name"],
            "url" => $site["url"],
            "status" => $latestMonitoring ? $latestMonitoring->status : "N/A",
            "type" => $monitoringType ? $monitoringType->typeName : "N/A",
            "typeId" => $latestMonitoring ? $latestMonitoring->typeId : 0,
            "checkedAt" => $latestMonitoring ? $latestMonitoring->createdAt : "N/A"
        ];
    }

  \Tina4\Debug::message("Sites with monitoring data: " . json_encode($sitesWithMonitoring));
    return $response(\Tina4\renderTemplate("landing-page.twig", [
        "username" => $_SESSION["username"],
        "sites" => $sitesWithMonitoring
    ]));
});

    /*
    // Get user information from session
    $username = $_SESSION["username"];

    // Render the landing page with the user's information
    return $response(\Tina4\renderTemplate("landing-page.twig", [
        "username" => $username // Pass the username to the template
    ]));
});
*/