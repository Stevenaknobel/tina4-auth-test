<?php

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

global $DBA;
$DBA = new \Tina4\DataMySQL(
    $_ENV["DB_HOST"] . ":" . $_ENV["DB_NAME"],
    $_ENV["DB_USER"],
    $_ENV["DB_PASS"],
    "d-m-Y"
);

// Run the scheduled monitoring
runScheduledMonitoring();

// Clean history older than 30 days
$DBA->exec("DELETE FROM monitoring_history WHERE created_at < NOW() - INTERVAL 30 DAY");

function runScheduledMonitoring(): void
{
    global $DBA;

    // Get all monitored sites
    $monitoredSites = $DBA->fetch("SELECT * FROM monitored_sites")->asArray();

    foreach ($monitoredSites as $site) {
        $siteId = (int)$site["site_id"];
        $typeId = (int)$site["type_id"]; // assumes site now has a `type_id` column
        $url = $site["url"];

        $status = "unknown";
        $httpCode = 0;
        $createdAt = date("Y-m-d H:i:s");

        try {
            if ($typeId === 1) { // Ping
                $host = parse_url($url, PHP_URL_HOST);
                $pingResult = exec("ping -c 1 -W 1 " . escapeshellarg($host), $output, $returnVar);
                $status = ($returnVar === 0) ? "up" : "down";
            } else {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                if ($typeId === 3) {
                    curl_setopt($ch, CURLOPT_POST, true);
                } elseif ($typeId === 2) {
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                } else {
                    throw new Exception("Unsupported type_id: {$typeId}");
                }

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $status = ($httpCode >= 200 && $httpCode < 400) ? "up" : "down";
                curl_close($ch);
            }
        } catch (Exception $e) {
            $status = "error";
        }

        // Check if there's an existing entry in monitoring
        $existingMonitoring = $DBA->fetch(
            "SELECT monitoring_id FROM monitoring WHERE site_id = {$siteId} AND type_id = {$typeId} LIMIT 1",
            1
        )->asArray();

        if (!empty($existingMonitoring)) {
            // Update existing
            $DBA->exec(
                "UPDATE monitoring SET status = ?, created_at = ? WHERE site_id = ? AND type_id = ?",
                $status, $createdAt, $siteId, $typeId
            );
        } else {
            // Insert new
            $DBA->exec(
                "INSERT INTO monitoring (site_id, type_id, status, created_at) VALUES (?, ?, ?, ?)",
                $siteId, $typeId, $status, $createdAt
            );
        }

        // Always insert into monitoring_history
        $DBA->exec(
            "INSERT INTO monitoring_history (site_id, type_id, status, created_at) VALUES (?, ?, ?, ?)",
            $siteId, $typeId, $status, $createdAt
        );

        // Update monitored_sites table
        $DBA->exec(
            "UPDATE monitored_sites SET status = ? WHERE site_id = ?",
            $status, $siteId
        );
    }

    echo "Monitoring complete at " . date("Y-m-d H:i:s") . "\n";
}
