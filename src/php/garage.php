<?php
session_start();

// Prüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'connect_DB.php';

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

$conn = getDBConnection();
$error = '';
$success = '';

// ===== Fahrzeug hinzufügen=====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = $_POST['year'] ?? '';
    $licenseplate = trim($_POST['licenseplate'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $mileage = $_POST['mileage'] ?? '';
    $lasttuev = $_POST['lasttuev'] ?? '';
    $lastoilchange = $_POST['lastoilchange'] ?? '';
    $lastgreatservice = $_POST['lastgreatservice'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (
        empty($brand) ||
        empty($model) ||
        empty($year) ||
        empty($licenseplate) ||
        empty($type) ||
        empty($mileage) ||
        empty($lasttuev) ||
        empty($lastoilchange) ||
        empty($lastgreatservice)
    ) {
        $error = 'Bitte alle Felder ausfüllen';
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO garage (user_id, brand, model, year, licenseplate, type, mileage, lasttuev, lastoilchange, lastgreatservice, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $brand, $model, $year, $licenseplate, $type, $mileage, $lasttuev, $lastoilchange, $lastgreatservice, $notes]);
            $success = 'Fahrzeug erfolgreich hinzugefügt!';
        } catch(PDOException $e) {
            $error = 'Fehler beim Speichern: ' . $e->getMessage();
        }
    }
}

// ===== Fahrzeug löschen=====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $vehicle_id = $_POST['vehicle_id'] ?? 0;

    try {
        // Nur eigene Fahrzeuge löschen!
        $stmt = $conn->prepare("DELETE FROM garage WHERE id = ? AND user_id = ?");
        $stmt->execute([$vehicle_id, $_SESSION['user_id']]);

        if ($stmt->rowCount() > 0) {
            $success = 'Fahrzeug gelöscht!';
        } else {
            $error = 'Fahrzeug nicht gefunden oder keine Berechtigung!';
        }
    } catch(PDOException $e) {
        $error = 'Fehler beim Löschen: ' . $e->getMessage();
    }
}

// =====Alle Fahrzeuge=====
try {
    $stmt = $conn->query("
        SELECT garage.*, user.username 
        FROM garage 
        JOIN user ON garage.user_id = user.id 
        WHERE garage.user_id = ?
        ORDER BY garage.created_at DESC
    ");
    $garage = $stmt->fetchAll();
} catch(PDOException $e) {
    $error = 'Fehler beim Laden der Fahrzeuge: ' . $e->getMessage();
    $garage = [];
}
?>

<!Doctype html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>CarStats | Meine Fahrzeuge</title>
    <link rel="stylesheet" href="../css/garagestyle.css" />

    <link rel="stylesheet" href="../../Templates/variables.css">
    <link rel="stylesheet" href="../../Templates/header.css">
    <link rel="stylesheet" href="../../Templates/footer.css">
</head>
<body>

<!-- Header -->
<div id="header-placeholder"></div>
<script src="../../assets/header.js"></script>

<main>
    <section class="disclaimer">
        <h3>Hinweis</h3>
        <p>Diese Seite dient zur Verwaltung deiner privaten Fahrzeugdaten. Alle Angaben werden zur weiterverarbeitung bis zur manuellen Löschung gespeichert.</p>
    </section>

    <div class="vehicle">
        <header>
            <h2>Meine Fahrzeuge (<?php echo count($garage); ?>)</h2>
        </header>

        <?php if (!empty($garage)): ?>
        <section class="vehicle-cards">
            <?php foreach ($garage as $vehicle): ?>
                <article class="vehicle-card">
                    <div class="vehicle-card-header">
                        <div class="vehicle-card-title">
                            <h3><?php echo htmlspecialchars($vehicle['brand']); ?></h3>
                            <p><?php echo htmlspecialchars($vehicle['model']); ?></p>
                        </div>
                        <span class="vehicle-card-year"><?php echo htmlspecialchars($vehicle['year']); ?></span>
                    </div>

                    <?php if ($vehicle['user_id'] == $_SESSION['user_id']): ?>
                        <span class="badge badge-owner">Mein Fahrzeug</span>
                    <?php endif; ?>

                    <div class="vehicle-card-body">
                        <div class="vehicle-info-row">
                            <span class="vehicle-info-label">Kennzeichen</span>
                            <span class="vehicle-info-value"><?php echo htmlspecialchars($vehicle['licenseplate']); ?></span>
                        </div>
                        <div class="vehicle-info-row">
                            <span class="vehicle-info-label">Kilometerstand</span>
                            <span class="vehicle-info-value"><?php echo htmlspecialchars($vehicle['mileage']); ?></span>
                        </div>
                        <div class="vehicle-info-row">
                            <span class="vehicle-info-label">Antriebsart</span>
                            <span class="vehicle-info-value"><?php echo htmlspecialchars($vehicle['type']); ?></span>
                        </div>
                        <div class="vehicle-info-row">
                            <span class="vehicle-info-label">Letzter TÜV</span>
                            <span class="vehicle-info-value"><?php echo htmlspecialchars($vehicle['lasttuev']); ?></span>
                        </div>
                        <div class="vehicle-info-row">
                            <span class="vehicle-info-label">Letzter Ölwechsel</span>
                            <span class="vehicle-info-value"><?php echo htmlspecialchars($vehicle['lastoilchange']); ?></span>
                        </div>
                        <div class="vehicle-info-row">
                            <span class="vehicle-info-label">Letzter großer Service</span>
                            <span class="vehicle-info-value"><?php echo htmlspecialchars($vehicle['lastgreatservice']); ?></span>
                        </div>
                    </div>

                    <div class="vehicle-card-actions">
                        <button id="btn_edit<?php echo $index; ?>">Bearbeiten</button>
                        <?php if ($vehicle['user_id'] == $_SESSION['user_id']): ?>
                            <form method="POST" action="" style="display: inline;"
                                  onsubmit="return confirm('Fahrzeug wirklich löschen?');">
                                <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                <button class="button-delete" type="submit" name="action" value="delete_vehicle">Löschen</button>
                            </form>
                        <?php else: ?>
                            <span style="color: #999;">—</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Noch keine Fahrzeuge: So könnte es hier aussehen: </p>
                <!-- Fahrzeug-Karten -->
                <section class="vehicle-cards">
                    <!-- Fahrzeug 1: VW Golf -->
                    <article class="vehicle-card">
                        <div class="vehicle-card-header">
                            <div class="vehicle-card-title">
                                <h3>Volkswagen</h3>
                                <p>Golf 7 2.0 TDI</p>
                            </div>
                            <span class="vehicle-card-year">2018</span>
                        </div>

                        <div class="vehicle-card-body">
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Kennzeichen</span>
                                <span class="vehicle-info-value">B-AB-1234</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Kilometerstand</span>
                                <span class="vehicle-info-value">75.342 km</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Antriebsart</span>
                                <span class="vehicle-info-value">Diesel</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Letzter TÜV</span>
                                <span class="vehicle-info-value">06/24</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Letzter Ölwechsel</span>
                                <span class="vehicle-info-value">05/24</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Letzter großer Service</span>
                                <span class="vehicle-info-value">03/22</span>
                            </div>
                        </div>

                        <div class="vehicle-card-actions">
                            <button id="btn_edit0">Bearbeiten</button>
                            <button id="btn_delete0" class="button-delete">Löschen</button>
                        </div>
                    </article>

                    <!-- Fahrzeug 2: BMW -->
                    <article class="vehicle-card">
                        <div class="vehicle-card-header">
                            <div class="vehicle-card-title">
                                <h3>BMW</h3>
                                <p>330i xDrive</p>
                            </div>
                            <span class="vehicle-card-year">2021</span>
                        </div>

                        <div class="vehicle-card-body">
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Kennzeichen</span>
                                <span class="vehicle-info-value">M-XY-2021</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Kilometerstand</span>
                                <span class="vehicle-info-value">35.092 km</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Antriebsart</span>
                                <span class="vehicle-info-value">Super E5</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Letzter TÜV</span>
                                <span class="vehicle-info-value">12/23</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Letzter Ölwechsel</span>
                                <span class="vehicle-info-value">12/23</span>
                            </div>
                            <div class="vehicle-info-row">
                                <span class="vehicle-info-label">Letzter großer Service</span>
                                <span class="vehicle-info-value">10/22</span>
                            </div>
                        </div>

                        <div class="vehicle-card-actions">
                            <button id="btn_edit1">Bearbeiten</button>
                            <button id="btn_delete1" class="button-delete">Löschen</button>
                        </div>
                    </article>
                </section>
        <?php endif; ?>

        <section>
            <h3>Neues Fahrzeug hinzufügen</h3>
            <form>
                <fieldset>
                    <legend>Fahrzeugdaten</legend>
                    <div class="grid">
                        <label>
                            Marke:
                            <input type="text" id="brand" placeholder="z. B. Audi" required>
                        </label>
                        <label>
                            Modell:
                            <input type="text" id="model" placeholder="z. B. A4 Avant" required>
                        </label>
                        <label>
                            Baujahr:
                            <input type="number" id="age" min="1900" max="2099" required>
                        </label>
                        <label>
                            Kennzeichen:
                            <input type="text" id="identification" placeholder="z. B. HH-AB 1234">
                        </label>
                        <label>
                            Kraftstoff / Antriebsart:
                            <select id="type" required>
                                <option value="">Bitte auswählen</option>
                                <option value="petrol">Benzin</option>
                                <option value="diesel">Diesel</option>
                                <option value="electric">Elektro</option>
                                <option value="gas">Gas (LPG/CNG)</option>
                                <option value="other">Sonstige</option>
                            </select>
                        </label>
                        <label>
                            Kilometerstand:
                            <input type="number" id="mileage" min="0" max="1000000" required>
                        </label>
                        <label>
                            Letzter TÜV:
                            <input type="date" id="tuev" placeholder="z. B. 06/24" required>
                        </label>
                        <label>
                            Letzter Ölwechsel:
                            <input type="date" id="oilchange" placeholder="z. B. 05/24" required>
                        </label>
                        <label>
                            Letzter großer Service:
                            <input type="date" id="greatservice" placeholder="z. B. 10/22" required>
                        </label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Zusatzinformationen</legend>
                    <label>
                        Notizen:
                        <textarea id="notes" rows="3" placeholder="z. B. Keilriemen, neuer Turbo, Steuerkette …"></textarea>
                    </label>
                </fieldset>

                <div class="actions">
                    <button id="btn_new_vehicle_submit" type="submit">Fahrzeug speichern</button>
                    <button type="reset">Eingaben löschen</button>
                </div>
            </form>
        </section>
    </div>
</main>

<!-- Footer -->
<div id="footer-placeholder"></div>
<script src="../../assets/script.js"></script>
</body>
</html>
