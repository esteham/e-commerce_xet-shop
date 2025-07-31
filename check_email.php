<?php
require_once __DIR__ . '/config/classes/user.php';
$AUTH_user = new USER();

if ($_POST) {
    $email = strip_tags($_POST['email']);
    $stmt = $AUTH_user->runQuery('SELECT userEmail FROM users WHERE userEmail = :email');
    $stmt->execute(array(':email' => $email));
    $count = $stmt->rowCount();

    if ($count > 0) {
        echo '<span style="color:red;">Sorry, this email already exists</span>';
    } else {
        echo '<span style="color:green;">Email is available</span>';
    }
}
?>