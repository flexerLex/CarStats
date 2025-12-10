<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_submit'])) {
    // Session-Variablen löschen
    $_SESSION = array();

    // Session-Cookie löschen
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Remember-Me-Cookie löschen, wenn vorhanden
    if (isset($_COOKIE['remember_user'])) {
        setcookie('remember_user', '', time() - 3600, '/');
    }

    // Session zerstören
    session_destroy();

    // Zur Login-Seite weiterleiten
    header('Location: login.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarStats</title>

    <link rel="stylesheet" href="../../Templates/variables.css">
    <link rel="stylesheet" href="../../Templates/header.css">
    <link rel="stylesheet" href="../../Templates/footer.css">

    <link rel="stylesheet" href="../css/login.css">
    <script src="../js/login_register_script.js" defer></script>
</head>

<body>
<div id="header-placeholder"></div>
<script src="../../assets/header.js"></script>

<main class="auth">
    <section class="auth__card">
        <h1>Du bist bereits angemeldet</h1>
        <p>Hier kannst du dich abmelden</p>

        <!-- Ausloggen-->
        <form id="logout_mask" class="form active" method="post" action="#">
            <fieldset>
                <legend>Abmeldung</legend>
                <button id="logout_submit" name="logout_submit" type="submit">Ausloggen</button>
            </fieldset>
        </form>
        <h1>Bis bald!</h1>
    </section>
</main>

<div id="footer-placeholder"></div>
<script src="../../assets/script.js"></script>
</body>
</html>
