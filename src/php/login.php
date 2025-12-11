<?php
session_start();

$success = '';
if (isset($_GET['registered'])) {
    $success = 'Registrierung erfolgreich! Du kannst dich jetzt einloggen.';
}

// Landingpage wenn bereits eingeloggt
if (isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true) {
    header('Location: logout.php');
    exit;
}

// Datenbankverbindung
require_once 'connect_DB.php';

/*  in tableinit.sql
    CREATE TABLE IF NOT EXISTS user (
        id INT(11) NOT NULL AUTO_INCREMENT,
        firstname TEXT NOT NULL,
        name TEXT NOT NULL,
        mail VARCHAR(100) NOT NULL,
        password VARCHAR(100) NOT NULL,
        username VARCHAR(30) NOT NULL,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
*/

$error = '';
$success = '';

// Cookie-Login prüfen (Remember Me)
if (isset($_COOKIE['remember_user']) && !empty($_COOKIE['remember_user'])) {
    $username = $_COOKIE['remember_user'];

    try {
        $conn = getDBConnection();

        // Prüfen ob Benutzer wirklich existiert
        $stmt = $conn->prepare("SELECT id, username FROM user WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if ($user) {
            // User gefunden, automatisch einloggen
            $_SESSION['loggedIn'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header('Location: logout.php');
            exit;
        } else {
            // User existiert nicht mehr, Cookie löschen
            setcookie('remember_user', '', time() - 3600, '/');
        }
    } catch(PDOException $e) {
        // Cookie löschen bei Fehler
        setcookie('remember_user', '', time() - 3600, '/');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ====LOGIN====
    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validierung
        if (empty($username) || empty($password)) {
            $error = 'Bitte Benutzername und Passwort eingeben!';
        } else {
            try {
                $conn = getDBConnection();

                // Benutzer aus Datenbank holen
                $stmt = $conn->prepare("SELECT id, username, password FROM user WHERE username = :username");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch();

                // username und passwort checken
                if ($user && password_verify($password, $user['password'])) {
                    // Login erfolgreich
                    $_SESSION['loggedIn'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];

                    // neue Session-ID Security und so
                    session_regenerate_id(true);

                    // Cookie remember me zum eingeloggt bleiben
                    if ($remember) {
                        setcookie('remember_user', $username, [
                                'expires' => time() + (30 * 24 * 60 * 60), // 30 Tage
                                'path' => '/',
                                'secure' => false,  // Bei HTTPS auf true setzen!
                                'httponly' => true,  // Schutz gegen XSS
                                'samesite' => 'Strict'  // Schutz gegen CSRF
                        ]);
                    }

                    header('Location: logout.php');
                    exit;
                } else {
                    $error = 'Ungültiger Benutzername oder Passwort!';
                }

            } catch(PDOException $e) {
                $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            }
        }
    }
    else if ($action === 'register') {
        $firstname = trim($_POST['firstname'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $mail = trim($_POST['mail'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';


        // Validierung
        if (empty($firstname) || empty($name) || empty($username) || empty($mail) || empty($password)) {
            $error = 'Bitte alle Felder ausfüllen!';
        } elseif (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ungültige E-Mail-Adresse!';
        } elseif (strlen($password) < 6) {
            $error = 'Passwort muss mindestens 6 Zeichen lang sein!';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwörter stimmen nicht überein!';
        } else {
            try {
                $conn = getDBConnection();

                // Prüfen ob Username oder Email bereits existiert
                $stmt = $conn->prepare("SELECT id FROM user WHERE username = :username OR mail = :mail");
                $stmt->execute([
                        'username' => $username,
                        'mail' => $mail
                ]);

                if ($stmt->fetch()) {
                    $error = 'Benutzername oder E-Mail bereits vergeben!';
                } else {
                    // Neuen User erstellen
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare(
                            "INSERT INTO user (firstname, name, username, mail, password) 
                     VALUES (:firstname, :name, :username, :mail, :password)"
                    );

                    $stmt->execute([
                            'firstname' => $firstname,
                            'name' => $name,
                            'username' => $username,
                            'mail' => $mail,
                            'password' => $hashedPassword
                    ]);

                    header('Location: login.php?registered=1');
                    exit;

                    // Optional: Direkt einloggen nach Registrierung
                    // $_SESSION['loggedIn'] = true;
                    // $_SESSION['user_id'] = $conn->lastInsertId();
                    // $_SESSION['username'] = $username;
                    // header('Location: ../html/index.html');
                    // exit;
                }

            } catch(PDOException $e) {
                $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarStats</title>
	<link rel="icon" type="image/png" href="../../assets/images/fav_icon.png">
    <link rel="stylesheet" href="../../Templates/variables.css">
    <link rel="stylesheet" href="../../Templates/header.css">
    <link rel="stylesheet" href="../../Templates/footer.css">

    <link rel="stylesheet" href="../css/login.css">
    <script src="../js/login_register_script.js" defer></script>
</head>
<body>
<!-- Header -->
<div id="header-placeholder"></div>
<script src="../../assets/header.js"></script>

<main class="auth">
    <section class="auth__card">
        <h1>Willkommen bei CarStats</h1>
        <p>Bitte melde dich an oder registriere dich, um fortzufahren.</p>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Navigation zwischen Login / Registrierung -->
        <nav class="auth__tabs" aria-label="Formularauswahl">
            <button id="login_btn" class="tab active" data-target="login">Anmelden</button>
            <button id="register_btn" class="tab" data-target="register">Registrieren</button>
        </nav>

        <!-- === LOGIN === -->
        <form id="login_mask" class="form active" method="post" action="#">
            <fieldset>
                <legend>Anmeldung</legend>
                <input type="hidden" name="action" value="login">
                <label>
                    Benutzername:
                    <input
                        id="username"
                        name="username"
                        type="text"
                        pattern=".{1,50}"
                        required
                        autofocus
                        placeholder="Dein Benutzername"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); // Absicherung vor Errors und Injections ?>"
                    >
                    <!-- pattern="^[\p{L}\p{N}._-]{3,30}$" führt zu Fehler-->
                </label>

                <label>
                    Passwort:
                    <input id="password" type="password" name="password" required placeholder="••••••••">
                </label>

                <label class="stayloggedin">
                    <input type="checkbox" name="remember">
                    Angemeldet bleiben
                </label>

                <button id="login_submit" type="submit">Anmelden</button>
            </fieldset>
        </form>

        <!-- === REGISTRIERUNG === -->
        <form id="register_mask" class="form" method="post" action="#">
            <fieldset>
                <legend>Registrierung</legend>
                <div class="grid">
                    <input type="hidden" name="action" value="register">
                    <label>
                        Vorname:
                        <input id="first_name_input" name="firstname" type="text" pattern="[A-Za-z]+" required placeholder="Max">
                    </label>
                    <label>
                        Nachname:
                        <input id="name_input" name="name" type="text" pattern="[A-Za-z]+" required placeholder="Mustermann">
                    </label>
                </div>
                <label>
                    E-Mail:
                    <input id="mail_input" name="mail" type="email" required placeholder="max@beispiel.de">
                </label>
                <label>
                    Benutzername:
                    <input id="username_input" name="username" type="text" pattern=".{1,50}" required placeholder="Dein Benutzername">
                </label>
                <label>
                    Passwort:
                    <input id="password_input" name="password" type="password" minlength="6" required placeholder="••••••••">
                </label>
                <label>
                    Passwort nochmal:
                    <input id="password_input_confirm" name="password_confirm" type="password" minlength="6" required placeholder="••••••••">
                </label>
                <button id="register_submit" type="submit">Registrieren</button>
            </fieldset>
        </form>
    </section>
</main>

<!-- Footer -->
<div id="footer-placeholder"></div>
<script src="../../assets/script.js"></script>
</body>
</html>
