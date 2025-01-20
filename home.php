<?php 
require_once './config/database.php';
require_once './includes/auth.php';

// Initialize GymDatabase connection

$GymDatabase = new GymDatabase();
$db = $GymDatabase->getConnection();
$auth = new Auth($db);

// Get the current route

$route = isset($_GET['route'])? $_GET['route'] : 'home';
$routes = explode('/', $route);
$page = $routes[0]?? 'home';

// Define protected routes

$protected_routes = ['dashboard'];


include 'hero.php';
include 'gyms.php';
include 'membership-plans.php';

?>