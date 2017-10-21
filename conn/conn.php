<?php
$server = 'localhost';
$user = 'dev';
$pwd = 'dev123456';

$conn = new PDO("mysql:host=$server;dbname=douban_OOP", $user, $pwd);

if ($conn)
    echo "mysql connected...\n";

$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



?>