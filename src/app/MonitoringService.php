<?php

namespace App;

use Tina4\Debug;

/**
 * MonitoringService handles monitoring-related functionality
 */
class MonitoringService
{
    /**
     * Checks a site's status
     *
     * @param int $siteId The site ID
     * @param int $typeId The monitoring type ID
     * @param int $companyId The company ID (for tenant separation)
     * @return array The monitoring result
     */
    public function checkSite(int $siteId, int $typeId, int $companyId): array
    {
        global $DBA;

        // Validate the site belongs to the company
        $siteResults = $DBA->fetch(
            "SELECT * FROM monitored_sites WHERE site_id = ? AND company_id = ?",
            [$siteId, $companyId],
            1
        )->asArray();

        if (empty($siteResults)) {
            Debug::message("Site not found or unauthorized: {$siteId} for company {$companyId}", "CRITICAL");
            return [
                'success' => false,
                'message' => 'Site not found or unauthorized'
            ];
        }

        $site = $siteResults[0];
        $url = $site["url"];
        $status = "unknown";
        $httpCode = 0;

        Debug::message("Checking site ID: {$siteId}, URL: {$url}, Type: {$typeId}", "CRITICAL");

        // Monitoring Logic
        try {
            if ($typeId === 1) { // Ping
                $host = parse_url($url, PHP_URL_HOST);
                $host = preg_replace('#^https?://#', '', $host);

                $pingResult = exec("ping -c 1 -W 1 " . escapeshellarg($host), $output, $returnVar);
                $status = ($returnVar === 0) ? "up" : "down";
            } else {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);

                if ($typeId === 3) { // POST
                    curl_setopt($ch, CURLOPT_POST, true);
                } elseif ($typeId === 2) { // GET
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                } elseif ($typeId === 4) { // HTTP
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                } else {
                    throw new \Exception("Unsupported type_id: {$typeId}");
                }

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $status = ($httpCode >= 200 && $httpCode < 400) ? "up" : "down";
                curl_close($ch);
            }
        } catch (\Exception $e) {
            $status = "error";
            Debug::message("Monitoring check failed: " . $e->getMessage(), "CRITICAL");
        }

        // Save the monitoring result
        $this->saveMonitoringResult($siteId, $typeId, $status);

        return [
            'success' => true,
            'status' => $status,
            'httpCode' => $httpCode,
            'message' => "Site checked successfully"
        ];
    }

    /**
     * Saves a monitoring result
     *
     * @param int $siteId The site ID
     * @param int $typeId The monitoring type ID
     * @param string $status The status
     * @return void
     */
    private function saveMonitoringResult(int $siteId, int $typeId, string $status): void
    {
        global $DBA;

        $createdAt = date("Y-m-d H:i:s");

        // Check if there's already a monitoring entry for this site and type
        $existingMonitoring = $DBA->fetch(
            "SELECT monitoring_id FROM monitoring WHERE site_id = ? AND type_id = ? LIMIT 1",
            [$siteId, $typeId],
            1
        )->asArray();

        if (!empty($existingMonitoring)) {
            // Update existing record
            $DBA->exec(
                "UPDATE monitoring SET status = ?, created_at = ? WHERE site_id = ? AND type_id = ?",
                [$status, $createdAt, $siteId, $typeId]
            );
        } else {
            // Insert new record
            $DBA->exec(
                "INSERT INTO monitoring (site_id, type_id, status, created_at) VALUES (?, ?, ?, ?)",
                [$siteId, $typeId, $status, $createdAt]
            );
        }

        // Always insert into monitoring_history
        $DBA->exec(
            "INSERT INTO monitoring_history (site_id, type_id, status, created_at) VALUES (?, ?, ?, ?)",
            [$siteId, $typeId, $status, $createdAt]
        );

        // Update the monitored_sites table
        $DBA->exec(
            "UPDATE monitored_sites SET status = ? WHERE site_id = ?",
            [$status, $siteId]
        );

        Debug::message("Monitoring result saved: Site {$siteId}, Type {$typeId}, Status {$status}", "CRITICAL");
    }

    /**
     * Gets monitored sites for a company
     *
     * @param int $companyId The company ID
     * @return array The monitored sites
     */
    public function getMonitoredSites(int $companyId): array
    {
        global $DBA;

        $monitoredSites = $DBA->fetch(
            "SELECT * FROM monitored_sites WHERE company_id = ?",
            [$companyId],
            1000
        )->asArray();

        // Attach latest monitoring result & type name per site
        $sitesWithMonitoring = [];

        foreach ($monitoredSites as $site) {
            // Fetch the latest monitoring result for each site
            $latestMonitoring = $DBA->fetch(
                "SELECT m.*, mt.type_name 
                FROM monitoring m 
                JOIN monitoring_types mt ON m.type_id = mt.type_id 
                WHERE m.site_id = ? 
                ORDER BY m.created_at DESC 
                LIMIT 1",
                [$site["site_id"]],
                1
            )->asArray();

            $monitoring = !empty($latestMonitoring) ? $latestMonitoring[0] : null;

            $sitesWithMonitoring[] = [
                "siteId" => $site["site_id"],
                "siteName" => $site["site_name"],
                "url" => $site["url"],
                "status" => $monitoring ? $monitoring["status"] : "N/A",
                "type" => $monitoring ? $monitoring["type_name"] : "N/A",
                "typeId" => $monitoring ? $monitoring["type_id"] : 0,
                "checkedAt" => $monitoring ? $monitoring["created_at"] : "N/A"
            ];
        }

        return $sitesWithMonitoring;
    }

    /**
     * Gets monitoring statistics for a company
     *
     * @param int $companyId The company ID
     * @return array The statistics
     */
    public function getMonitoringStats(int $companyId): array
    {
        $sites = $this->getMonitoredSites($companyId);

        // Count how many are up/down/pending
        $upCount = 0;
        $downCount = 0;
        $pendingCount = 0;

        foreach ($sites as $site) {
            switch (strtolower($site["status"])) {
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

        return [
            'upCount' => $upCount,
            'downCount' => $downCount,
            'pendingCount' => $pendingCount,
            'totalCount' => count($sites)
        ];
    }
}
