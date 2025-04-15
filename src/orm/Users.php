<?php
class Users extends \Tina4\ORM
{
    public $tableName="users";
    public $primaryKey="userId"; //set for primary key
    //public $fieldMapping = ["userId" => "user_id","companyId" => "company_id","username" => "username","password" => "password","createdAt" => "created_at"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 
    
	public $userId;
	public $companyId;
	public $username;
	public $password;
	public $createdAt;
}