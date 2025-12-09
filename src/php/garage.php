<?php

if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === false) {

    exit;
}

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

    /*
    CREATE TABLE IF NOT EXISTS garage (
        id INT(11) NOT NULL AUTO_INCREMENT,
        brand VARCHAR(255) NOT NULL,
        model VARCHAR(255) NOT NULL,
        year YEAR(4) NOT NULL,
        licenseplate VARCHAR(255) NOT NULL,
        type VARCHAR(255) NOT NULL,
        mileage INT(11) NOT NULL,
        lasttuev DATE,
        lastoilchange DATE,
        lastgreatservice DATE,
        notes TEXT,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
     */

    // SQL mit Prepared Statement
    $stmt = $pdo->prepare("
        INSERT INTO garage (
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