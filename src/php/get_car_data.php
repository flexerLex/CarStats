<?php
session_start();

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

// Загрузка типов топлива
$fuelTypes = include '../php/fuel_types.php';

if (isset($_GET['car_id'])) {
    $car_id = filter_input(INPUT_GET, 'car_id', FILTER_VALIDATE_INT);
    if (!$car_id) {
        echo json_encode(['error' => 'Invalid car ID']);
        exit;
    }
    try {
        // Получение данных автомобиля
        $stmt = $conn->prepare("SELECT * FROM garage WHERE id = ? AND user_id = ?");
        $stmt->execute([$car_id, $_SESSION['user_id']]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$car) {
            echo json_encode(['error' => 'Car not found']);
            exit;
        }
        // Добавление описания топлива
        if ($car && isset($fuelTypes[$car['type']])) {
            $car['fuel_description'] = $fuelTypes[$car['type']];
        }else {
            $car['fuel_description'] = '';
        }
        // Получение последнего пополнения топлива
        $stmt = $conn->prepare("SELECT quantity, date FROM expenses WHERE car_id = ? AND category = 'Sprit' ORDER BY date DESC LIMIT 1");
        $stmt->execute([$car_id]);
        $lastFuel = $stmt->fetch(PDO::FETCH_ASSOC);


        // Получение последнего километража
        $stmt = $conn->prepare("SELECT mileage FROM expenses WHERE car_id = ? ORDER BY date DESC LIMIT 1");
        $stmt->execute([$car_id]);
        $lastMileage = $stmt->fetchColumn();

        // Формирование ответа
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