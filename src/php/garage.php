<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Falls nötig

try {
    // Daten empfangen
    $data = json_decode(file_get_contents('php://input'), true);

    // Validierung
    if (!$data || !isset($data['brand']) || !isset($data['model'])) {
        throw new Exception('Unvollständige Daten');
    }

    // Datenbankverbindung mit Fehlerbehandlung
    $pdo = new PDO(
        'mysql:host=localhost;dbname=garage;charset=utf8mb4',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // SQL mit Prepared Statement
    $stmt = $pdo->prepare("
        INSERT INTO vehicles (
            brand, model, year, identification, type,
            mileage, lasttuev, lastoilchange, lastgreatservice, additiveinfo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $success = $stmt->execute([
        $data['brand'],
        $data['model'],
        $data['year'],
        $data['identification'],
        $data['type'],
        $data['mileage'],
        $data['lasttuev'],
        $data['lastoilchange'],
        $data['lastgreatservice'],
        $data['additiveinfo'] ?? ''
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Fahrzeug erfolgreich gespeichert',
        'id' => $pdo->lastInsertId()
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>