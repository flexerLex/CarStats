<?php
// Erstellung durch Rico Baur
require_once '../php/connect_DB.php';
$conn = getDBConnection();
$passwordHash = password_hash('fschuetz', PASSWORD_DEFAULT);

$sql = "
    CREATE TABLE IF NOT EXISTS user (
        id INT(11) NOT NULL AUTO_INCREMENT,
        firstname TEXT NOT NULL,
        name TEXT NOT NULL,
        mail VARCHAR(100) NOT NULL,
        password VARCHAR(100) NOT NULL,
        username VARCHAR(30) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

    INSERT INTO user (firstname, name, mail, password, username) 
    VALUES (
    'Franziska',
    'Schuetz',
    'franziska.schuetz@test.de',
    '$passwordHash',
    'schuetz'
    );
    
    INSERT INTO garage (
    user_id,
    brand,
    model,
    year,
    licenseplate,
    type,
    mileage,
    tankvolume,
    lasttuev,
    lastoilchange,
    lastgreatservice,
    notes
    ) VALUES (
    1,
    'Mercedes-Benz',
    'C 220d',
    2019,
    'M-FS-220',
    'Diesel',
    47000,
    66,
    '2024-06-01',
    '2024-03-15',
    '2023-09-10',
    'Langstrecke'
    );

    INSERT INTO expenses (
    car_id,
    date,
    category,
    amount,
    mileage,
    fuel_type,
    quantity,
    full_tank,
    notes
    ) VALUES
    (1, '2024-01-05', 'fuel', 85.40, 43000, 'Diesel', 51.2, 1, 'Pendeln'),
    (1, '2024-01-25', 'fuel', 90.10, 43800, 'Diesel', 54.0, 1, 'Autobahn'),
    (1, '2024-02-15', 'fuel', 87.90, 44500, 'Diesel', 52.3, 1, 'Stadtverkehr'),
    (1, '2024-03-10', 'fuel', 93.60, 45200, 'Diesel', 56.1, 1, 'Langstrecke'),
    (1, '2024-04-02', 'fuel', 91.20, 46000, 'Diesel', 55.0, 1, 'Mischbetrieb'),
    (1, '2024-04-20', 'service', 199.00, 46200, NULL, NULL, 0, 'Ölwechsel');
    ";

try {
    $conn->exec($sql);
    echo "Tabelle 'garage', 'user' und 'expenses' erfolgreich erstellt (oder existierte bereits)!";

} catch(PDOException $e) {
    die("Fehler beim Erstellen der Tabelle: " . $e->getMessage());
}
?>