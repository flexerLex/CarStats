<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../php/connect_DB.php';
$conn = getDBConnection();
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Load fuel types
$fuelTypes = include '../php/fuel_types.php';

if (isset($_GET['car_id'])) {
    $car_id = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
    if (!$car_id) {
        echo json_encode(['error' => 'Invalid car ID']);
        exit;
    }
    try {
        // Fetch car data
        $stmt = $conn->prepare("SELECT * FROM garage WHERE id = ? AND user_id = ?");
        $stmt->execute([$car_id, $_SESSION['user_id']]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$car) {
            echo json_encode(['error' => 'Car not found']);
            exit;
        }

        // Add fuel description
        $car['fuel_description'] = $fuelTypes[$car['type']] ?? '';

        // Fetch last fuel entry
        $stmt = $conn->prepare("SELECT quantity, date FROM expenses WHERE car_id = ? AND category = 'Sprit' ORDER BY date DESC LIMIT 1");
        $stmt->execute([$car_id]);
        $lastFuel = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch last MAX mileage entry
        $stmt = $conn->prepare("SELECT MAX(mileage) FROM expenses WHERE car_id = ?");
        $stmt->execute([$car_id]);
        $lastMileage = $stmt->fetchColumn();
        // Build response
        $response = [
            'car' => $car,
            'lastFuel' => $lastFuel,
            'lastMileage' => $lastMileage
        ];

        echo json_encode($response);

    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => 'Database error']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>