<?php
try 
{
	$conn = new PDO("mysql:host=localhost;dbname=e_shopdb","root","");
	$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$password = password_hash('manager@123', PASSWORD_DEFAULT);

	$sql = "INSERT INTO users (userName, userEmail, userPass, status, tokenCode, user_type, is_active) VALUES (:uname, :email, :pass, 'active','','manager',1)";

	$stmt = $conn->prepare($sql);
	$stmt->execute([

		':uname' => 'manager',
		':email' => 'manager@gmail.com',
		':pass' => $password
	]);

	echo "Manager created successfully";
} 
catch (PDOException $e) 
{
	echo "DB Error:".$e->getMessage();
}

?>