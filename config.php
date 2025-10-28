<?php
declare(strict_types=1);

$DB_HOST = '127.0.0.1';
$DB_NAME = 'playground';
$DB_USER = 'root';    // or 'play_user'
$DB_PASS = '';        // or 'secret' if you created play_user

$pdo = new PDO(
  "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
  $DB_USER,
  $DB_PASS,
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]
);
