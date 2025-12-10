<?php
session_start();

// User muss eingeloggt sein
if (!isset($_SESSION['loggedIn']) || $_SESSION['loggedIn'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'connect_DB.php';
$conn   = getDBConnection();
$userId = $_SESSION['user_id'] ?? null;

if ($userId === null) {
    die('Kein Benutzer gefunden.');
}

//  Fahrzeuge des Users laden
try {
    $stmtCars = $conn->prepare("
        SELECT id, brand, model, licenseplate
        FROM garage
        WHERE user_id = :uid
        ORDER BY brand, model, licenseplate
    ");
    $stmtCars->execute(['uid' => $userId]);
    $cars = $stmtCars->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Fehler beim Laden der Fahrzeuge: ' . $e->getMessage());
}

// ausgewähltes Auto bestimmen
$selectedCarId = null;
if (!empty($cars)) {
    if (isset($_GET['car_id'])) {
        $selectedCarId = (int)$_GET['car_id'];
    } else {
        $selectedCarId = (int)$cars[0]['id']; // erstes Auto als Standard
    }
}

// ---------------------------------------------------------
//  Für ausgewähltes Auto: letzte Daten + zukünftige Fälligkeiten
//    (nur bis maximal 2 Jahre in der Zukunft)
// ---------------------------------------------------------
$pastEvents     = []; // letzte echte Aktionen
$futureEvents   = []; // alle zukünftigen Fälligkeiten innerhalb 2 Jahre ab heute
$nextReminder   = null; // nächster Termin (Anstehend)
$inAussichtList = [];   // weitere Termine (In Aussicht)

if ($selectedCarId !== null) {
    try {
        $stmtCar = $conn->prepare("
            SELECT id, brand, model, licenseplate,
                   lasttuev, lastgreatservice, lastoilchange
            FROM garage
            WHERE id = :car_id AND user_id = :uid
            LIMIT 1
        ");
        $stmtCar->execute([
            'car_id' => $selectedCarId,
            'uid'    => $userId
        ]);
        $car = $stmtCar->fetch(PDO::FETCH_ASSOC);

        if ($car) {
            $today = new DateTime();
            $limit = (clone $today)->modify('+2 years'); // nur Termine bis max. 2 Jahre ab heute

            /**
             * Hilfsfunktion:
             * - fügt das letzte Ereignis zu $pastEvents hinzu
             * - berechnet ALLE zukünftigen Fälligkeiten innerhalb der nächsten 2 Jahre
             */
            $addFutureEvents = function (
                ?string $lastDate,
                string $label,
                string $interval,
                array &$past,
                array &$future,
                DateTime $today,
                DateTime $limit
            ) {
                if (!$lastDate) {
                    return;
                }

                try {
                    $last = new DateTime($lastDate);
                } catch (Exception $e) {
                    return; // ungültiges Datum
                }

                // letztes tatsächliches Ereignis → "Letzte Ausgaben"
                $past[] = [
                    'label' => $label,
                    'date'  => $last,
                ];

                // zukünftige Fälligkeiten ab heute bis Limit (2 Jahre)
                $current = clone $last;

                while (true) {
                    $current->modify($interval); // z.B. "+2 years"

                    // Wenn wir über dem 2-Jahres-Limit sind → abbrechen
                    if ($current > $limit) {
                        break;
                    }

                    // Nur Termine, die ab heute liegen, übernehmen
                    if ($current >= $today) {
                        $future[] = [
                            'label' => $label,
                            'date'  => clone $current,
                        ];
                    }
                }
            };

            // TÜV: alle 2 Jahre
            $addFutureEvents(
                $car['lasttuev'] ?? null,
                'TÜV',
                '+2 years',
                $pastEvents,
                $futureEvents,
                $today,
                $limit
            );

            // Service: alle 1 Jahr (typisch)
            $addFutureEvents(
                $car['lastgreatservice'] ?? null,
                'Service',
                '+1 year',
                $pastEvents,
                $futureEvents,
                $today,
                $limit
            );

            // Ölwechsel: hier auch alle 1 Jahr (kannst du später z.B. auf '+6 months' ändern)
            $addFutureEvents(
                $car['lastoilchange'] ?? null,
                'Ölwechsel',
                '+1 year',
                $pastEvents,
                $futureEvents,
                $today,
                $limit
            );

            // Vergangene Events nach Datum sortieren (neueste zuerst)
            usort($pastEvents, function ($a, $b) {
                return $b['date'] <=> $a['date'];
            });

            // Zukünftige Events nach Datum sortieren (frühestes zuerst)
            usort($futureEvents, function ($a, $b) {
                return $a['date'] <=> $b['date'];
            });

            if (!empty($futureEvents)) {
                // der nächste Termin
                $nextReminder   = array_shift($futureEvents);
                // restliche innerhalb 2 Jahren
                $inAussichtList = $futureEvents;
            }
        }
    } catch (PDOException $e) {
        die('Fehler beim Laden der Fahrzeugdaten: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CarStats | Erinnerungen</title>
    <link rel="stylesheet" href="../../Templates/variables.css">
    <link rel="stylesheet" href="../../Templates/header.css">
    <link rel="stylesheet" href="../../Templates/footer.css">
    <link rel="stylesheet" href="../css/erinnerungen.css">
</head>

<body>
    <!-- Header -->
    <div id="header-placeholder"></div>
    <script src="../../assets/header.js"></script>

    <!-- Fahrzeug-Auswahl -->
    <section class="car-filter" style="grid-column: 1 / -1; margin-bottom: 1rem;">
        <?php if (empty($cars)): ?>
            <p>Du hast noch keine Fahrzeuge in deiner Garage.</p>
        <?php else: ?>
            <form method="get" action="erinnerungen.php">
                <label for="car_id">Fahrzeug wählen:</label>
                <select id="car_id" name="car_id" onchange="this.form.submit()">
                    <?php foreach ($cars as $carRow): ?>
                        <?php
                        $cid   = (int)$carRow['id'];
                        $label = $carRow['brand'] . ' ' . $carRow['model'] . ' (' . $carRow['licenseplate'] . ')';
                        ?>
                        <option value="<?= $cid ?>" <?= $cid === $selectedCarId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit">Anzeigen</button></noscript>
            </form>
        <?php endif; ?>
    </section>

    <main class="board">

        <!-- Letzte Ausgaben -->
        <article class="column">
            <h2 class="column__title">Letzte Ausgaben</h2>
            <div class="column__content">
                <?php if ($selectedCarId === null): ?>
                    <p>Kein Fahrzeug ausgewählt.</p>
                <?php elseif (empty($pastEvents)): ?>
                    <p>Keine Daten vorhanden.</p>
                <?php else: ?>
                    <?php foreach ($pastEvents as $event): ?>
                        <div class="expense-item">
                            <span class="expense-item__label">
                                <?= htmlspecialchars($event['label']) ?>
                            </span>
                            <span class="expense-item__value">
                                <?= $event['date']->format('d.m.Y') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

        <!-- Anstehend -->
        <article class="column">
            <h2 class="column__title">Anstehend</h2>
            <div class="column__content">
                <?php if ($selectedCarId === null): ?>
                    <p>Kein Fahrzeug ausgewählt.</p>
                <?php elseif ($nextReminder === null): ?>
                    <p>Aktuell steht nichts an (innerhalb der nächsten 2 Jahre).</p>
                <?php else: ?>
                    <div class="expense-item">
                        <span class="expense-item__label">
                            <?= htmlspecialchars($nextReminder['label']) ?>
                        </span>
                        <span class="expense-item__value">
                            <?= $nextReminder['date']->format('d.m.Y') ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <!-- In Aussicht -->
        <article class="column">
            <h2 class="column__title">In Aussicht</h2>
            <div class="column__content">
                <?php if ($selectedCarId === null): ?>
                    <p>Kein Fahrzeug ausgewählt.</p>
                <?php elseif (empty($inAussichtList)): ?>
                    <p>Keine weiteren Termine in Aussicht (innerhalb der nächsten 2 Jahre).</p>
                <?php else: ?>
                    <?php foreach ($inAussichtList as $event): ?>
                        <div class="expense-item">
                            <span class="expense-item__label">
                                <?= htmlspecialchars($event['label']) ?>
                            </span>
                            <span class="expense-item__value">
                                <?= $event['date']->format('d.m.Y') ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </article>

    </main>

    <!-- Footer -->
    <div id="footer-placeholder"></div>
    <script src="../../assets/script.js"></script>
</body>

</html>
