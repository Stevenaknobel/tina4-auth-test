<?php


\Tina4\Get::add("/monitoredsites/landing", function (\Tina4\Response $response, \Tina4\Request $request){
    // Check if user is logged in
    if (!isset($_SESSION["user_id"])) {
        return \Tina4\redirect("/login");  // Redirect to login if not logged in
    }

    $user = (new Users())->load("user_id = ?", [$_SESSION["user_id"]]);

    // Check if user is a global admin
    $isGlobalAdmin = (isset($_SESSION["role"]) && $_SESSION["role"] === "global_admin");

    // Interpolate directly (safe here because it's an integer from session-loaded user object)
    $companyId = (int)$user->companyId;

    // If global admin, show all sites, otherwise filter by company
    if ($isGlobalAdmin) {
        $monitoredSites = (new MonitoredSites())->select()->asArray();
    } else {
        $monitoredSites = (new MonitoredSites())->select()->where("company_id = ?", [$companyId])->asArray();
    }

    \Tina4\Debug::message("User company ID: " . $user->companyId);
    \Tina4\Debug::message("Company has " . count($monitoredSites) . " monitored sites.");

    // Attach latest monitoring result & type name per site
    $sitesWithMonitoring = [];

    foreach ($monitoredSites as $site) {
        $latestMonitoring = (new Monitoring())->select()->where("site_id = ?", [$site['siteId']])->asArray();

        // If we have monitoring results, get the first/latest one
        $monitoring = !empty($latestMonitoring) ? $latestMonitoring[0] : null;

        $monitoringType = null;
        if ($monitoring && isset($monitoring['typeId'])) {
            $monitoringType = (new MonitoringTypes())->load("type_id = ?", [$monitoring['typeId']]);
        }

        // Get company information if global admin
        $companyInfo = null;
        if ($isGlobalAdmin && isset($site['companyId'])) {
            $companyInfo = (new Companies())->load("company_id = ?", [$site['companyId']]);
        }

        $sitesWithMonitoring[] = [
            "siteId" => $site['siteId'],
            "siteName" => $site['siteName'],
            "url" => $site['url'],
            "status" => $monitoring ? $monitoring['status'] : "N/A",
            "type" => $monitoringType ? $monitoringType->typeName : "N/A",
            "typeId" => $monitoring ? $monitoring['typeId'] : 0,
            "checkedAt" => $monitoring ? $monitoring['createdAt'] : "N/A",
            "companyId" => $site['companyId'] ?? null,
            "companyName" => $companyInfo ? $companyInfo->companyName : null,
            "tags" => $site['tags'] ?? null
        ];
    }

    // Count statuses
    $upCount = $downCount = $pendingCount = 0;
    foreach ($sitesWithMonitoring as $site) {
        switch (strtolower($site['status'])) {
            case "up":
                $upCount++;
                break;
            case "down":
                $downCount++;
                break;
            default:
                $pendingCount++;
        }
    }

    \Tina4\Debug::message("Sites with monitoring data: " . json_encode($sitesWithMonitoring));

    return $response(\Tina4\renderTemplate("landing-page.twig", [
        "username" => $_SESSION["username"],
        "sites" => $sitesWithMonitoring,
        "upCount" => $upCount,
        "downCount" => $downCount,
        "pendingCount" => $pendingCount,
        "user" => $user,
        "isGlobalAdmin" => $isGlobalAdmin
    ]));
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

                // Check if status parameter is present in the request
                if (isset($request->params["status"])) {
                    $monitoredSites->status = $request->params["status"];
                    $monitoredSites->save();
                    return \Tina4\redirect("/landing-page?success=Site status updated successfully");
                }

                $content = \Tina4\renderTemplate("/monitoredsites/form.twig", ["data" => $monitoredSites]);
            }

            return \Tina4\renderTemplate("components/modalForm.twig", ["title" => $title, "onclick" => "if ( $('#monitoredSitesForm').valid() ) { saveForm('monitoredSitesForm', '" .$savePath."', 'message'); $('#formModal').modal('hide');}", "content" => $content]);
       break;
       case "read":
            //Return a dataset to be consumed by the grid with a filter
            $where = "";
            if (!empty($filter["where"])) {
                $where = $filter["where"];
            }

            return $monitoredSites->select("*", $filter["length"], $filter["start"])
                ->where($where)
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
            // Check for related records in the Monitoring table
            global $DBA;
            $siteId = $monitoredSites->siteId;

            // Check if there are related records in the monitoring table
//            $monitoringRecords = $DBA->fetch(
//                "SELECT COUNT(*) as count FROM monitoring WHERE site_id = ?",
//                [$siteId],
//                1
//            )->asArray();
            $monitoringRecords = (new Monitoring())->select()->where("site_id = ?", [$siteId])->asArray();

            if ($monitoringRecords) {
                // Delete related monitoring records first
//                $DBA->exec(
//                    "DELETE FROM monitoring WHERE site_id = ?",
//                    [$siteId]
//                );
                $monitoringRecords = (new Monitoring())->delete("site_id = ?", [$siteId]);
                \Tina4\Debug::message("Deleted related monitoring records for site ID: {$siteId}", "INFO");
            }

            // Check if there are related records in the monitoring_history table
//            $historyRecords = $DBA->fetch(
//                "SELECT COUNT(*) as count FROM monitoring_history WHERE site_id = ?",
//                [$siteId],
//                1
//            )->asArray();

             $historyRecords = (new MonitoringHistory())->select()->where("site_id = ?", [$siteId])->asArray();
            if ($historyRecords) {
                // Delete related monitoring_history records first
                $monitoringRecords = (new MonitoringHistory())->delete("site_id = ?", [$siteId]);

                \Tina4\Debug::message("Deleted related monitoring history records for site ID: {$siteId}", "INFO");
            }
        break;
        case "afterDelete":
            //return needed 
            return (object)["httpCode" => 200, "message" => "<script>monitoredSitesGrid.ajax.reload(null, false); showMessage ('MonitoredSites Deleted');</script>"];
        break;
    }
});
