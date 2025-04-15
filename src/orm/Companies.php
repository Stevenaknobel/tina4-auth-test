<?php
class Companies extends \Tina4\ORM
{
    public $tableName="companies";
    public $primaryKey="companyId"; //set for primary key
    //public $fieldMapping = ["companyId" => "company_id","companyName" => "company_name","createdAt" => "created_at"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $companyId;
	public $companyName;
	public $createdAt;
}