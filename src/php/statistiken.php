<?php
session_start();
if (!isset($_SESSION['loggedIn'])) {
    header('Location: login.php');
    exit;
}

require_once 'connect_DB.php'; 
require_once 'StatistikService.php';

$conn = getDBConnection();
$service = new StatistikService($conn);

// 1. Get filter parameters
$user_id = $_SESSION['user_id'];
$currentUnit = filter_input(INPUT_GET, 'unit') ?: 'month';
if (!in_array($currentUnit, ['day', 'month', 'year'])) $currentUnit = 'month';

// 2. Get vehicle list and select vehicle
$cars = $service->getUserCars($user_id);
$car_id = $service->determineSelectedCar($cars);

// 3. Scraping statistical results
$results = $car_id ? $service->getCarStatistics($car_id, $user_id, $currentUnit) : $service->initializeEmptyResults();

include 'statistiken_view.php';