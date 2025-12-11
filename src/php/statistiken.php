<?php
// ----------- I. Sicherheits- und Initialisierungsprüfung -----------------------------------
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id === null) {
    die('Keine Benutzer-ID in der Session gefunden.');
}

require_once 'connect_DB.php'; 
require_once 'StatistikService.php';

$conn = getDBConnection();
$statistikService = new StatistikService($conn);

$unit = filter_input(INPUT_GET, 'unit', FILTER_DEFAULT); 
if (!in_array($unit, ['day', 'month', 'year'])) {
    $unit = 'month';
}
$currentUnit = $unit; 
$cars = $statistikService->getUserCars($user_id);
$car_id = $statistikService->determineSelectedCar($cars);

// ----------------- II. Daten abruf und Berechnung -----------------
if ($car_id) { 
    $results = $statistikService->getCarStatistics($car_id, $user_id, $currentUnit);
} else {
    $results = $statistikService->initializeEmptyResults();
}

// ----------------- III. Die Daten werden für das Frontend in eine JS-Variable kodiert -----------------
$jsData = json_encode($results);
// -------------------------------------------------------------------------------------------------------

include 'statistiken_view.php'; 

?>