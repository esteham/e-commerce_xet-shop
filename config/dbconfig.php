<?php

class Database 
{
	private $host =  "localhost";
	private $db_name = "u975049586_eshop";
	private $username = "u975049586_eshop";
	private $password = "Dipannita.6jet";

	public $conn;

	public function dbConnection()
	{
		$this->conn = null;

		try 
		{
			$this->conn = new PDO("mysql:host=".$this->host.";dbname=".$this->db_name,$this->username, $this->password);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		} 
		catch (PDOException $exception) 
		{
			echo "Connection Error:".$exception->getMessage();
		}

		return $this->conn;
	} 
}


?>