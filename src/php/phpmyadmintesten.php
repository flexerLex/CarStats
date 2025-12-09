<?php

/*
// Verbindung erstellen
$conn = new PDO("mysql:host=localhost;dbname=carstats","root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo $conn;

// Verbindung prüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

echo "Verbindung erfolgreich!<br><br>";

// 1. NEUEN EINTRAG ERSTELLEN (INSERT)
$firstname = "Max";
$name= "Mustermann";
$mail = "max@example.com";
$password = "max";
$username = "max";

$sql_insert = "INSERT INTO user (firstname, name, mail, password, username) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql_insert);
$stmt->execute([
    ':firstname' => $firstname,
    ':name' => $name,
    ':mail' => $mail,
    ':password' => $password,
    ':username' => $username,
]);

if ($stmt->execute()) {
    echo "Neuer Eintrag erfolgreich erstellt!<br>";
    echo "ID des neuen Eintrags: " . $stmt->insert_id . "<br><br>";
} else {
    echo "Fehler: " . $stmt->error . "<br><br>";
}
$stmt->close();

// 2. DATEN AUSLESEN (SELECT)
$sql_select = "SELECT id, firstname, name, mail, username FROM user";
$result = $conn->query($sql_select);

if ($result->num_rows > 0) {
    echo "<h3>Alle Benutzer:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Alter</th></tr>";

    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["name"] . "</td>";
        echo "<td>" . $row["mail"] . "</td>";
        echo "<td>" . $row["username"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Keine Einträge gefunden";
}

// Verbindung schließen
$conn->close();
*/

/*
// Verbindung erstellen
$conn = new PDO("mysql:host=localhost;dbname=carstats", "root", "");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Verbindung erfolgreich!<br><br>";

// 1. NEUEN EINTRAG ERSTELLEN (INSERT)
$firstname = "Max";
$name = "Mustermann";
$mail = "max@example.com";
$password = "max";
$username = "max";

$sql_insert = "INSERT INTO user (firstname, name, mail, password, username)
               VALUES (:firstname, :name, :mail, :password, :username)";

$stmt = $conn->prepare($sql_insert);
$stmt->execute([
    ':firstname' => $firstname,
    ':name' => $name,
    ':mail' => $mail,
    ':password' => $password,
    ':username' => $username,
]);

echo "Neuer Eintrag erfolgreich erstellt!<br>";
echo "ID des neuen Eintrags: " . $conn->lastInsertId() . "<br><br>";

// 2. DATEN AUSLESEN (SELECT)
$sql_select = "SELECT id, firstname, name, mail, username FROM user";
$stmt = $conn->query($sql_select);

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($rows) > 0) {
    echo "<h3>Alle Benutzer:</h3>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Vorname</th><th>Name</th><th>Email</th><th>Username</th></tr>";

    foreach ($rows as $row) {
        echo "<tr>";
        echo "<td>" . $row["id"] . "</td>";
        echo "<td>" . $row["firstname"] . "</td>";
        echo "<td>" . $row["name"] . "</td>";
        echo "<td>" . $row["mail"] . "</td>";
        echo "<td>" . $row["username"] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Keine Einträge gefunden";
}


?>




<?php
session_start();

// Falls bereits eingeloggt, zur Startseite weiterleiten
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
    header('Location: 3_dashboard.php');
    exit;
}

// Cookie-Login prüfen (Remember Me)
if (isset($_COOKIE['remember_user']) && !empty($_COOKIE['remember_user'])) {
    $username = $_COOKIE['remember_user'];

    // Normalerweise hier noch sicherstellen, dass der Benutzer existiert!
    // Für dieses einfache Beispiel werden hier einfach die Session-Variablen gesetzt
    $_SESSION['loggedIn'] = true;
    $_SESSION['username'] = $username;

    header('Location: 3_dashboard.php');
    exit;
}

// Einfache Liste von Benutzern (in der Praxis würde man eine Datenbank verwenden!)
$users = [
    'admin' => password_hash('admin123', PASSWORD_DEFAULT),
    'test' => password_hash('test123', PASSWORD_DEFAULT)
];

$error = '';

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Prüfen ob Benutzer existiert und Passwort stimmt
    if (isset($users[$username]) && password_verify($password, $users[$username])) {
        // Login erfolgreich
        $_SESSION['loggedIn'] = true;
        $_SESSION['username'] = $username;

        // Remember Me Cookie setzen, wenn gewünscht
        if ($remember) {
            // Cookie für 30 Tage setzen
            // Vereinfachtes Beispiel: username im Cookie ist unsicher - in der Praxis würde man einen Token verwenden!
            setcookie('remember_user', $username, time() + (30 * 24 * 60 * 60), '/');
        }

        header('Location: 3_dashboard.php');
        exit;
    } else {
        $error = 'Ungültiger Benutzername oder Passwort!';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
    <style>
        .error { color: red; }
    </style>
</head>
<body>
<h1>Login</h1>

<?php if ($error): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>

<form method="post">
    <div>
        <label for="username">Benutzername:</label>
        <input type="text" id="username" name="username" required>
    </div>
    <div>
        <label for="password">Passwort:</label>
        <input type="password" id="password" name="password" required>
    </div>
    <div>
        <label>
            <input type="checkbox" name="remember"> Angemeldet bleiben (30 Tage)
        </label>
    </div>
    <button type="submit">Einloggen</button>
</form>
</body>
</html>
*/


require_once 'connect_userDB.php';

try {
    $conn = getDBConnection();
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h3>Tabellen in der Datenbank:</h3>";
    echo "<pre>";
    print_r($tables);
    echo "</pre>";

    echo "<h3>Test: SELECT aus 'user' Tabelle:</h3>";
    $stmt = $conn->query("SELECT * FROM user LIMIT 1");
    echo "Erfolgreich!";

} catch (PDOException $e) {
    echo "Fehler: " . $e->getMessage();
}
