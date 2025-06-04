<?php
class Users extends \Tina4\ORM
{
    public $tableName="users";
    public $primaryKey="userId"; //set for primary key
    public $fieldMapping = ["userId" => "user_id","companyId" => "company_id","username" => "username","email" => "email","password" => "password","createdAt" => "created_at","twofaSecret" => "twofa_secret","twofaEnabled" => "twofa_enabled","role" => "role"];
    //public $genPrimaryKey=false; //set to true if you want to set the primary key
    //public $ignoreFields = []; //fields to ignore in CRUD
    //public $softDelete=true; //uncomment for soft deletes in crud 

	public $userId;
	public $companyId;
	public $username;
	public $email;
	public $password;
	public $createdAt;
	public $twofaSecret;
	public $twofaEnabled;
	public $role;
}
