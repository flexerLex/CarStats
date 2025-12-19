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
        tankvolume FLOAT NOT NULL,
        lasttuev DATE,
        lastoilchange DATE,
        lastgreatservice DATE,
        notes TEXT,
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

    CREATE TABLE IF NOT EXISTS expenses (
        id INT(11) NOT NULL AUTO_INCREMENT,
        car_id INT(11) NOT NULL,
        date DATE NOT NULL,
        category VARCHAR(50) NOT NULL, 
        amount DECIMAL(10, 2) NOT NULL,
        mileage INT(11),
        notes TEXT,
        fuel_type VARCHAR(20),  
        quantity DECIMAL(8, 3),
        full_tank BOOLEAN NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        FOREIGN KEY (car_id) REFERENCES garage(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";

try {
    $conn->exec($sql);
    echo "Tabelle 'garage', 'user' und 'expenses' erfolgreich erstellt (oder existierte bereits)!";

} catch(PDOException $e) {
    die("Fehler beim Erstellen der Tabelle: " . $e->getMessage());
}
?>