<?php
class MonitoredSites extends \Tina4\ORM
{
    public $tableName="monitored_sites";
    public $primaryKey="siteId"; //set for primary key
    //public $fieldMapping = ["siteId" => "site_id","companyId" => "company_id","url" => "url","siteName" => "site_name","status" => "status","createdAt" => "created_at"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $siteId;
	public $companyId;
	public $url;
	public $siteName;
	public $status;
	public $createdAt;
}