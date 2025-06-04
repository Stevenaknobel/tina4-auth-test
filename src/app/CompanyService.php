<?php

namespace App;

use Companies;
use Tina4\Debug;
use Tina4\ORM;

/**
 * CompanyService handles company-related functionality
 */
class CompanyService
{
    /**
     * Gets all companies
     *
     * @return array The companies
     */
    public function getAllCompanies(): array
    {
        // Get all companies
        $companies = (new Companies())->select("*")->orderBy("company_name")->asArray();

        return $companies;
    }

    /**
     * Gets a company by ID
     *
     * @param int $companyId The company ID
     * @return array|null The company data or null if not found
     */
    public function getCompanyById(int $companyId): ?array
    {

        $result = (new Companies())->load("company_id = ?", [$companyId]);

        return !empty($result) ? $result->asArray() : null;
    }

    /**
     * Creates a new company
     *
     * @param string $companyName The company name
     * @return array The result
     */
    public function createCompany(string $companyName): array
    {
        $companyName = trim(htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'));

        // Validate input
        if (empty($companyName)) {
            return [
                'success' => false,
                'message' => 'Company name is required'
            ];
        }

        $existingCompany = (new Companies())->load('company_name = ?', [$companyName]);
        if (!empty($existingCompany)) {
            return ["success" => false, "message" => "Company with this name already exists"];
        }

        // Create the company
        $company = new Companies();
        $company->companyName = $companyName;

        if($company->save()){
            $newCompany = (new Companies())->load("company_name = ?", [$companyName])->asArray();
            Debug::message("Company created: {$companyName} (ID: {$newCompany[0]['company_id']})", "CRITICAL");

            return [
                'success' => true,
                'message' => 'Company created successfully',
                'company' => $newCompany[0]
            ];
        }else{
            return [
                'success' => false,
                'message' => 'Failed to create company'
            ];
        }
    }

    /**
     * Updates a company
     *
     * @param int $companyId The company ID
     * @param string $companyName The new company name
     * @return array The result
     */
    public function updateCompany(int $companyId, string $companyName): array
    {
        global $DBA;

        // Validate input
        if (empty($companyName)) {
            return [
                'success' => false,
                'message' => 'Company name is required'
            ];
        }

        // Check if company exists


        $existing = (new Companies())->load('company_id = ?', [$companyId]);
        if (empty($existing)) {
            return [
                'success' => false,
                'message' => 'Company not found'
            ];
        }


        $nameExists = (new Companies())->load('company_name = ? AND company_id != ?', [$companyName, $companyId]);
        if (!empty($nameExists)) {
            return [
                'success' => false,
                'message' => 'Company with this name already exists'
            ];
        }
        $existing->companyName = $companyName;

        if($existing->save()) {
            Debug::message("Company name updated: {$companyName} (ID: {$companyId})", "CRITICAL");

            return [
                'success' => true,
                'message' => 'Company updated successfully'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update company'
            ];
        }
    }

    /**
     * Deletes a company
     *
     * @param int $companyId The company ID
     * @return array The result
     */
    public function deleteCompany(int $companyId): array
    {
        global $DBA;

        // Check if company exists
        $existing = $DBA->fetch(
            "SELECT * FROM companies WHERE company_id = {$companyId}")->asArray();

        if (empty($existing)) {
            return [
                'success' => false,
                'message' => 'Company not found'
            ];
        }

        // Check if company has users
        $users = $DBA->fetch(
            "SELECT COUNT(*) as count FROM users WHERE company_id = {$companyId}")->asArray();

        if (!empty($users) && $users[0]['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete company with users'
            ];
        }

        // Check if company has monitored sites
        $sites = $DBA->fetch(
            "SELECT COUNT(*) as count FROM monitored_sites WHERE company_id = {$companyId}")->asArray();

        if (!empty($sites) && $sites[0]['count'] > 0) {
            return [
                'success' => false,
                'message' => 'Cannot delete company with monitored sites'
            ];
        }

        // Delete the company
        $DBA->exec(
            "DELETE FROM companies WHERE company_id = {$companyId}"
        );

        Debug::message("Company deleted: ID {$companyId}", "CRITICAL");

        return [
            'success' => true,
            'message' => 'Company deleted successfully'
        ];
    }
}
