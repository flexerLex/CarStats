<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

session_start();

if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.php');
    exit;
}

require_once '../php/connect_DB.php';
$conn = getDBConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $car_id = filter_input(INPUT_POST, 'car_id', FILTER_VALIDATE_INT);
    $date = filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
    $mileage = filter_input(INPUT_POST, 'km', FILTER_VALIDATE_INT);
    $notes = trim($_POST['note'] ?? '');
    $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING) ?? null;
    $quantity = filter_input(INPUT_POST, 'fuel_amount', FILTER_VALIDATE_FLOAT) ?? 0;

    if ($car_id && $date && $category && $amount > 0) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO expenses (car_id, date, category, amount, mileage, notes, fuel_type, quantity)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$car_id, $date, $category, $amount, $mileage, $notes, $fuel_type, $quantity]);

            $successMessage = "Kosten erfolgreich gespeichert!";
        } catch (PDOException $e) {
            $errorMessage = "Fehler beim Speichern: " . $e->getMessage();
        }
    } else {
        $errorMessage = "Bitte füllen Sie alle erforderlichen Felder aus.";
    }
}

try {
    // Loading user cars from the garage table
    $stmt = $conn->prepare("SELECT id, brand, model, year, licenseplate FROM garage WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Fehler beim Laden der Fahrzeuge: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CarStats – Kostenübersicht</title>
    <link rel="icon" type="image/png" href="../../assets/images/fav_icon.png">
    <link rel="stylesheet" href="../../Templates/variables.css">
    <link rel="stylesheet" href="../../Templates/header.css">
    <link rel="stylesheet" href="../../Templates/footer.css">
    <link rel="stylesheet" href="../css/kosteneingabe.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
</head>

<body>
<div id="header-placeholder"></div>
<script src="../../assets/header.js"></script>

<!-- Car selection block -->
<header class="kosteneingabe-header">
    <div class="kosteneingabe-carselect">
        <label for="car-select" class="kosteneingabe-carselect__label">Wählen Sie ein Auto:</label>
        <select id="car-select" class="kosteneingabe-carselect__select">
            <?php foreach ($cars as $car): ?>
                <?php
                $carLabel = htmlspecialchars($car['brand'] . ' ' . $car['model'] . ' (' . $car['licenseplate'] . ')');
                ?>
                <option value="<?php echo htmlspecialchars($car['id']); ?>">
                    <?php echo $carLabel; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
</header>

<div class="kosteneingabe-gridcontainer">

    <!-- Main content -->
        <main class="kosteneingabe-center">
            <!-- Success or error messages -->
            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success animate__animated animate__fadeInDown" id="success-message">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="alert alert-error animate__animated animate__fadeInDown" id="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <div class="kosteneingabe-expense-filter">
                <span class="kosteneingabe-expense-filter__label">Vergangene Ausgaben:</span>
                <div class="kosteneingabe-expense-filter__slider">
                    <button class="kosteneingabe-expense-filter__btn active" data-filter="Sprit">
                        Sprit
                    </button>
                    <button class="kosteneingabe-expense-filter__btn" data-filter="Service">
                        Service
                    </button>
                    <button class="kosteneingabe-expense-filter__btn" data-filter="Reparatur">
                        Reparatur
                    </button>
                    <button class="kosteneingabe-expense-filter__btn" data-filter="Versicherung">
                        Versicherung
                    </button>
                    <button class="kosteneingabe-expense-filter__btn" data-filter="Anschaffung">
                        Anschaffung
                    </button>
                    <button class="kosteneingabe-expense-filter__btn" data-filter="Zubehör">
                        Zubehör
                    </button>
                </div>
            </div>
            <form class="kosteneingabe-form animate__animated animate__fadeInUp" id="cost-form" method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" id="car_id" name="car_id" value="">
                <input type="hidden" id="category" name="category" value="Sprit">
                <h2 class="kosteneingabe-form__title">Neue Kosten eintragen</h2>
                <div id="dynamic-fields-container"></div>
                <div class="kosteneingabe-form__input-row">
                    <label for="date" class="kosteneingabe-form__label">Datum</label>
                    <input type="date" id="date" name="date" class="kosteneingabe-form__input" required />
                </div>
                <div class="kosteneingabe-form__input-row">
                    <label for="km" class="kosteneingabe-form__label">Kilometerstand</label>
                    <input type="number" id="km" name="km" min="0" step="1" class="kosteneingabe-form__input" required />
                </div>
                <div class="kosteneingabe-form__input-row">
                    <label for="amount" class="kosteneingabe-form__label">Betrag (€)</label>
                    <input type="number" id="amount" name="amount" min="0" step="0.01" class="kosteneingabe-form__input" required>
                </div>
                <div class="kosteneingabe-form__input-row">
                    <label for="note" class="kosteneingabe-form__label">Kommentar (optional)</label>
                    <textarea id="note" name="note" rows="2" class="kosteneingabe-form__input"></textarea>
                </div>
                <div class="kosteneingabe-form__input-row">
                    <label for="receipt-photo" class="kosteneingabe-form__label">
                        Foto vom Beleg / Tankquittung
                        <span class="tooltip-container">
                <span class="tooltip-icon">?</span>
                <span class="tooltip-text">
                    Optional: Lade ein Foto hoch oder mache ein Foto vom Beleg. Die Daten werden später
                    automatisch erkannt und eingetragen.
                </span>
            </span>
                    </label>
                    <input type="file" id="receipt-photo" name="receipt-photo" accept="image/*" capture="environment"
                           class="kosteneingabe-form__input-file" />
                </div>
                <button class="kosteneingabe-form__submit" type="submit">Speichern</button>
            </form>
        </main>
</div>

<div id="footer-placeholder"></div>
<script src="../../assets/script.js"></script>
<script src="../js/kosteneingabe.js"></script>
</body>
</html>