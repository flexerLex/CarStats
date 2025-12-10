<?php
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
    $notes = filter_input(INPUT_POST, 'note', FILTER_SANITIZE_STRING);
    $fuel_type = filter_input(INPUT_POST, 'fuel_type', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'fuel_amount', FILTER_VALIDATE_FLOAT);

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
    $stmt = $conn->prepare("SELECT id, brand, model, year FROM garage WHERE user_id = ?");
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
                    <input type="number" id="fuel-amount" name="fuel_amount" min="0" step="0.01" class="kosteneingabe-form__input" placeholder="Letzte Menge">
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const carSelect = document.getElementById('car-select');
        const dynamicFieldsContainer = document.getElementById('dynamic-fields-container');
        const mileageField = document.getElementById('km');
        const fuelTypeSelect = document.createElement('select');
        fuelTypeSelect.id = 'fuel-type';
        fuelTypeSelect.name = 'fuel_type';
        fuelTypeSelect.classList.add('kosteneingabe-form__input');

        const fuelAmountField = document.getElementById('fuel-amount');
        const dateField = document.getElementById('date');

        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');

        if (!carSelect || !dynamicFieldsContainer) {
            console.error('Required elements not found in DOM');
            return;
        }

        carSelect.addEventListener('change', function () {
            const carId = carSelect.value;
            const carIdField = document.getElementById('car_id');

            if (carIdField) {
                carIdField.value = carId
            }

            if (carId) {
                fetch(`../php/get_car_data.php?car_id=${carId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }

                        updateFormFields(data);
                    })
                    .catch(error => console.error('Error fetching car data:', error));
            }
        });

        const expenseBtns = document.querySelectorAll('.kosteneingabe-expense-filter__btn');
        expenseBtns.forEach((btn) => {
            btn.addEventListener('click', function () {
                expenseBtns.forEach((b) => b.classList.remove('active'));
                this.classList.add('active');
                const currentCategory = this.dataset.filter;

                const categoryField = document.getElementById('category');
                if (categoryField) {
                    categoryField.value = currentCategory;
                }

                updateDynamicFields(currentCategory);
            });
        });

        const currentCategory = 'Sprit';
        updateDynamicFields(currentCategory);

        function updateDynamicFields(category) {
            let html = '';
            switch (category) {
                case 'Sprit':
                    html = `
            <div class="kosteneingabe-form__input-row">
                <label class="kosteneingabe-form__label">Kraftstoffart:</label>
                <span id="fuel-type-info" class="kosteneingabe-form__info">Keine Daten verfügbar</span>
            </div>
            <div class="kosteneingabe-form__input-row">
                <label class="kosteneingabe-form__label" for="fuel-amount">Getankte Menge (Liter/kWh)</label>
                <input type="number" id="fuel-amount" name="fuel-amount" min="0" step="0.01" class="kosteneingabe-form__input" placeholder="Letzte Menge">
            </div>
            `;
                    dynamicFieldsContainer.innerHTML = html;

                    fetch('../php/get_car_data.php?car_id=' + carSelect.value)
                        .then(response => response.json())
                        .then(data => {
                            const fuelTypeInfo = document.getElementById('fuel-type-info');
                            if (fuelTypeInfo) {
                                fuelTypeInfo.textContent = data.car.fuel_description || '';
                            }
                        })
                        .catch(error => console.error('Error fetching car data:', error));
                    break;

                case 'Service':
                case 'Reparatur':
                    html = `
            <div class="kosteneingabe-form__input-row">
                <label class="kosteneingabe-form__label" for="service-type">Art der Leistung</label>
                <input type="text" id="service-type" name="service-type" class="kosteneingabe-form__input" placeholder="z.B. Ölwechsel, Bremsen">
            </div>
            `;
                    dynamicFieldsContainer.innerHTML = html;
                    break;

                case 'Versicherung':
                    html = `
            <div class="kosteneingabe-form__input-row">
                <label class="kosteneingabe-form__label" for="insurance-type">Versicherungsart</label>
                <input type="text" id="insurance-type" name="insurance-type" class="kosteneingabe-form__input" placeholder="z.B. Haftpflicht, Vollkasko">
            </div>
            `;
                    dynamicFieldsContainer.innerHTML = html;
                    break;

                case 'Anschaffung':
                case 'Zubehör':
                    html = `
            <div class="kosteneingabe-form__input-row">
                <label class="kosteneingabe-form__label" for="purchase-desc">Beschreibung</label>
                <input type="text" id="purchase-desc" name="purchase-desc" class="kosteneingabe-form__input" placeholder="z.B. Dachbox, Navi">
            </div>
            `;
                    dynamicFieldsContainer.innerHTML = html;
                    break;

                default:
                    dynamicFieldsContainer.innerHTML = '';
            }

            if (dateField) {
                const today = new Date().toISOString().split('T')[0];
                dateField.value = today;
            }

            dynamicFieldsContainer.classList.remove('kosteneingabe-form__fields-animate');
            void dynamicFieldsContainer.offsetWidth; // trigger reflow
            dynamicFieldsContainer.classList.add('kosteneingabe-form__fields-animate');
        }

        if (successMessage) {
            successMessage.classList.add('show');
            setTimeout(() => {
                successMessage.classList.remove('show');
            }, 5000);
        }

        if (errorMessage) {
            errorMessage.classList.add('show');
            setTimeout(() => {
                errorMessage.classList.remove('show');
            }, 5000);
        }
    });
</script>
<script src="../js/kosteneingabe.js"></script>
</body>
</html>