<?php
// Temp script — get WP admin users
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/WPTEST/';
require_once 'D:/xampp/htdocs/WPTEST/wp-load.php';
global $wpdb;
$users = $wpdb->get_results("SELECT user_login, user_email FROM {$wpdb->users} LIMIT 5");
foreach ($users as $u) {
    echo $u->user_login . ' | ' . $u->user_email . PHP_EOL;
}
