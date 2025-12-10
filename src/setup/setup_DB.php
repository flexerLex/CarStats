<?php
require_once '../php/connect_DB.php';
$conn = getDBConnection();

$sql = "
    CREATE TABLE IF NOT EXISTS garage (
        id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
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
        fuel_description VARCHAR(255), -- new
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    
    CREATE TABLE IF NOT EXISTS user (
        id INT(11) NOT NULL AUTO_INCREMENT,
        firstname TEXT NOT NULL,
        name TEXT NOT NULL,
        mail VARCHAR(100) NOT NULL,
        password VARCHAR(100) NOT NULL,
        username VARCHAR(30) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

try {
    $conn->exec($sql);

    // if and add fuel_description
    $checkColumnSql = "SHOW COLUMNS FROM garage LIKE 'fuel_description'";
    $stmt = $conn->prepare($checkColumnSql);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        $addColumnSql = "ALTER TABLE garage ADD COLUMN fuel_description VARCHAR(255)";
        $conn->exec($addColumnSql);
        echo "Spalte 'fuel_description' wurde zur Tabelle 'garage' hinzugefügt!";
    }

    echo "Tabelle 'garage' und 'user' erfolgreich erstellt (oder existierten bereits)!";
} catch (PDOException $e) {
    die("Fehler beim Erstellen der Tabelle: " . $e->getMessage());
}
?>