<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CarStats | Statistiken</title>
	<link rel="icon" type="image/png" href="../../assets/images/fav_icon.png">
  <link rel="stylesheet" href="../css/statistiken.css" />
  <link rel="stylesheet" href="../../Templates/variables.css" />
  <link rel="stylesheet" href="../../Templates/header.css" />
  <link rel="stylesheet" href="../../Templates/footer.css" />
</head>

<body class="page-statistics">
  <div id="header-placeholder"></div>
  <script src="../../assets/header.js"></script>

  <div class="dashboard-container">

    <header class="dashboard-header">
      <div class="statistik-carselect">
        <label for="car-select" class="statistik-carselect__label">Wählen Sie ein Auto:</label>
        <select id="car-select" class="statistik-carselect__select" onchange="window.location.href='statistiken.php?car_id=' + this.value + '&unit=<?= $currentUnit ?>'">
            <?php if (empty($cars)): ?>
                <option value="">Keine Fahrzeuge gefunden</option>
            <?php else: ?>
                <?php foreach ($cars as $carRow): ?>
                    <?php
                    $cid   = (int)$carRow['id'];
                    $label = $carRow['brand'] . ' ' . $carRow['model'] . ' (' . $carRow['licenseplate'] . ')';
                    ?>
                    <option value="<?= $cid ?>" <?= $cid === $car_id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($label) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
        </div>
    </header>

    <main class="dashboard-grid">
      <div class="card kpi-card average-consumption">
        <div class="kpi__header">
          <svg class="kpi__icon icon__property" viewBox="0 0 16 16">
            <path
              d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1z" />
          </svg>
          <span class="kpi__title">Verbrauch</span>
        </div>
        <div class="kpi__value__row">
          <div class="kpi__value"><?= htmlspecialchars(number_format($results['kpis']['averageConsumption'] ?? 0, 2, ',', '.')) ?></div>
          <div class="kpi__footer">
            <span class="kpi__unit">(L/100km)</span>
          </div>
        </div>
      </div>
      
      <div class="card kpi-card fuel-costs">
        <div class="kpi__header">
          <svg class="kpi__icon icon__property" viewBox="0 0 16 16">
            <path fill-rule="evenodd"
              d="M7.21.8C7.69.295 8 0 8 0q.164.544.371 1.038c.812 1.946 2.073 3.35 3.197 4.6C12.878 7.096 14 8.345 14 10a6 6 0 0 1-12 0C2 6.668 5.58 2.517 7.21.8m.413 1.021A31 31 0 0 0 5.794 3.99c-.726.95-1.436 2.008-1.96 3.07C3.304 8.133 3 9.138 3 10c0 0 2.5 1.5 5 .5s5-.5 5-.5c0-1.201-.796-2.157-2.181-3.7l-.03-.032C9.75 5.11 8.5 3.72 7.623 1.82z" />
            <path fill-rule="evenodd"
              d="M4.553 7.776c.82-1.641 1.717-2.753 2.093-3.13l.708.708c-.29.29-1.128 1.311-1.907 2.87z" />
          </svg>
          <span class="kpi__title">Spritkosten</span>
        </div>
        <div class="kpi__value__row">
          <div class="kpi__value"><?= htmlspecialchars(number_format($results['kpis']['fuelCostsPerKm'] ?? 0, 3, ',', '.')) ?></div>
          <div class="kpi__footer">
            <span class="kpi__unit">(€/1km)</span>
          </div>
        </div>
      </div>
      
      <div class="card kpi-card mileage">
        <div class="kpi__header">
          <svg class="kpi__icon icon__property" viewBox="0 0 16 16">
            <path
              d="M2.52 3.515A2.5 2.5 0 0 1 4.82 2h6.362c1 0 1.904.596 2.298 1.515l.792 1.848c.075.175.21.319.38.404.5.25.855.715.965 1.262l.335 1.679q.05.242.049.49v.413c0 .814-.39 1.543-1 1.997V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.338c-1.292.048-2.745.088-4 .088s-2.708-.04-4-.088V13.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5v-1.892c-.61-.454-1-1.183-1-1.997v-.413a2.5 2.5 0 0 1 .049-.49l.335-1.68c.11-.546.465-1.012.964-1.261a.8.8 0 0 0 .381-.404l.792-1.848ZM3 10a1 1 0 1 0 0-2 1 1 0 0 0 0 2m10 0a1 1 0 1 0 0-2 1 1 0 0 0 0 2M6 8a1 1 0 0 0 0 2h4a1 1 0 1 0 0-2zM2.906 5.189a.51.51 0 0 0 .497.731c.91-.073 3.35-.17 4.597-.17s3.688.097 4.597.17a.51.51 0 0 0 .497-.731l-.956-1.913A.5.5 0 0 0 11.691 3H4.309a.5.5 0 0 0-.447.276L2.906 5.19Z" />
          </svg>
          <span class="kpi__title">Kilometerstand</span>
        </div>
        <div class="kpi__value__row">
          <div class="kpi__value"><?= htmlspecialchars(number_format($results['kpis']['mileage'] ?? 0, 0, ',', '.')) ?></div>
          <div class="kpi__footer">
            <span class="kpi__unit">(km)</span>
          </div>
        </div>
      </div>
      
      <div class="card kpi-card reichweite">
        <div class="kpi__header">
          <svg class="kpi__icon icon__property" viewBox="0 0 16 16">
            <path
              d="M9.585 2.568a.5.5 0 0 1 .226.58L8.677 6.832h1.99a.5.5 0 0 1 .364.843l-5.334 5.667a.5.5 0 0 1-.842-.49L5.99 9.167H4a.5.5 0 0 1-.364-.843l5.333-5.667a.5.5 0 0 1 .616-.09z" />
            <path d="M2 4h4.332l-.94 1H2a1 1 0 0 0-1 1v4a1 1 0 0 0 1 1h2.38l-.308 1H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2" />
            <path
              d="M2 6h2.45L2.908 7.639A1.5 1.5 0 0 0 3.313 10H2zm8.595-2-.308 1H12a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H9.276l-.942 1H12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z" />
            <path
              d="M12 10h-1.783l1.542-1.639q.146-.156.241-.34zm0-3.354V6h-.646a1.5 1.5 0 0 1 .646.646M16 8a1.5 1.5 0 0 1-1.5 1.5v-3A1.5 1.5 0 0 1 16 8" />
          </svg>
          <span class="kpi__title">Reichweite</span>
        </div>
        <div class="kpi__value__row">
          <div class="kpi__value"><?= htmlspecialchars(number_format($results['kpis']['range'] ?? 0, 0, ',', '.')) ?></div>
          <div class="kpi__footer">
            <span class="kpi__unit">(km)</span>
          </div>
        </div>
      </div>

      <div class="card kpi-card monthly-total-costs">
        <div class="kpi__header">
          <svg class="kpi__icon icon__property" viewBox="0 0 16 16">
            <path
              d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v1h14V4a1 1 0 0 0-1-1zm13 4H1v5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1z" />
            <path d="M2 10a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1z" />
          </svg>
          <span class="kpi__title">Monatskosten</span>
        </div>
        <div class="kpi__value__row">
          <div class="kpi__value"><?= htmlspecialchars(number_format($results['kpis']['monthlyTotalCosts'] ?? 0, 2, ',', '.')) ?></div>
          <div class="kpi__footer">
            <span class="kpi__unit">(€)</span>
          </div>
        </div>
      </div>
      
      <div class="card kpi-card annual-total-costs">
        <div class="kpi__header">
          <svg class="kpi__icon icon__property" viewBox="0 0 16 16">
            <path
              d="M1.5 2A1.5 1.5 0 0 0 0 3.5v2h6a.5.5 0 0 1 .5.5c0 .253.08.644.306.958.207.288.557.542 1.194.542s.987-.254 1.194-.542C9.42 6.644 9.5 6.253 9.5 6a.5.5 0 0 1 .5-.5h6v-2A1.5 1.5 0 0 0 14.5 2z" />
            <path
              d="M16 6.5h-5.551a2.7 2.7 0 0 1-.443 1.042C9.613 8.088 8.963 8.5 8 8.5s-1.613-.412-2.006-.958A2.7 2.7 0 0 1 5.551 6.5H0v6A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5z" />
          </svg>
          <span class="kpi__title">Jahreskosten</span>
        </div>
        <div class="kpi__value__row">
          <div class="kpi__value"><?= htmlspecialchars(number_format($results['kpis']['annualTotalCosts'] ?? 0, 2, ',', '.')) ?></div>
          <div class="kpi__footer">
            <span class="kpi__unit">(€)</span>
          </div>
        </div>
      </div>

      <div class="card chart-card consumption">
        <div class="chart__header">
          <h2 class="section-title">Trend des Kraftstoffverbrauchs</h2>
          <div class="chart__timeswitcher">
            <button class="chart__time__btn <?= $currentUnit === 'day' ? 'active' : '' ?>" data-unit="day" onclick="window.location.href='statistiken.php?car_id=<?= $car_id ?>&unit=day'">Tag</button>
            <button class="chart__time__btn <?= $currentUnit === 'month' ? 'active' : '' ?>" data-unit="month" onclick="window.location.href='statistiken.php?car_id=<?= $car_id ?>&unit=month'">Monat</button>
            <button class="chart__time__btn <?= $currentUnit === 'year' ? 'active' : '' ?>" data-unit="year" onclick="window.location.href='statistiken.php?car_id=<?= $car_id ?>&unit=year'">Jahr</button>
          </div>
        </div>
        <div class="chart__placeholder">
          <canvas id="consumption-chart"></canvas>
        </div>
      </div>

      <div class="card last-transactions-card transaction">
        <div class="transaction-header">
          <h2 class="section-title">Letzte Ausgaben</h2>
        </div>
        <div class="transaction-list-body">
            <ul class="transaction-list">
                <?php if (empty($results['transactions'])): ?>
                    <li class="transaction-item">Keine aktuellen Ausgaben gefunden.</li>
                <?php else: ?>
                    <?php foreach ($results['transactions'] as $transaction): ?>
                        <li class="transaction-item">
														<div class="transaction-details-left"> 
																<span class="transaction-date"><?= htmlspecialchars($transaction['date']) ?></span>
																<span class="transaction-category"> - <?= htmlspecialchars($transaction['category']) ?></span>
														</div>
														<span class="transaction-amount"><?= htmlspecialchars(number_format($transaction['amount'], 2, ',', '.')) ?> €</span>
												</li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        </div>
    </main>


  </div>

  <div id="footer-placeholder"></div>
  <script src="../../assets/script.js"></script>
  
  <script>
      const INITIAL_STATS_DATA = <?= json_encode($results) ?>;
  </script>
  <script src="../js/statistiken.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script
    src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/date-fns/locale/de/index.js"></script>
</body>

</html>