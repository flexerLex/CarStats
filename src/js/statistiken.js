//--------------- I. Globale Variablen und Hilfsfunktionen ---------------------
let consumptionChartInstance = null;
const PLACEHOLDER_TEXT = '---';

const PRIMARY_COLOR_SOLID = '#155A03'; 
const COLOR_CHART_FILL = 'rgba(218, 237, 213, 0.5)';

function formatNumber(num, minDecimals) {
    if (typeof num !== 'number') {
		return PLACEHOLDER_TEXT;
	}
    const numberValue = parseFloat(num); 
    const formatter = new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: minDecimals,
        maximumFractionDigits: minDecimals,
        useGrouping: minDecimals > 0 ? true : (numberValue >= 1000)
    });
    
    return formatter.format(numberValue);
}

function getSelectedCarId() {
    const selectElement = document.getElementById('car-select'); 
    if (selectElement && selectElement.value) {
        return selectElement.value;
    }
    return 1; 
}

async function fetchStatsData(carId, unit) {
    if (!carId) {
        console.warn("Keine Fahrzeug-ID ausgewählt, breche Datenabruf ab.");
        updateKpis({}); 
        return;
    }
    const apiUrl = `../php/statistiken.php?car_id=${carId}&unit=${unit}`;
    console.log(`Daten abrufen von: ${apiUrl}`);

    try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(`Backend Fehler: ${errorData.error || response.statusText}`);
        }
        const data = await response.json();
        console.log("Daten erfolgreich empfangen:", data);
        return data; 
    } catch (error) {
        console.error("Fehler beim Abrufen der Statistikdaten:", error);
        alert("Fehler beim Laden der Daten: " + error.message);
        updateKpis({}); 
        return null;
    }
}

// ------------II. Hauptinitialisierungsfunktion ------------------------
async function initDashboard() { 
  console.log('Dashboard Frontend Initialisierung gestartet...');

  initializeConsumptionChart();
  setupTimeSwitcherListeners();
  setupCarSelectListener(); 

  const carId = getSelectedCarId();
  await loadAndRenderData(carId, 'month');
}

// ------------ III. Funktionen zur Ansichtsaktualisierung ------------------------
function updateKpis(kpis) {
	if (!kpis) {
		kpis = {};
	}

	document.querySelector('.car-model').textContent =
		kpis.carModel || PLACEHOLDER_TEXT;
	document.querySelector('.average-consumption .kpi__value').textContent =
		formatNumber(kpis.averageConsumption, 2);
	document.querySelector('.fuel-costs .kpi__value').textContent = formatNumber(
		kpis.fuelCostsPerKm,
		2
	);
	document.querySelector('.mileage .kpi__value').textContent = formatNumber(
		kpis.mileage,
		0
	);
	document.querySelector('.reichweite .kpi__value').textContent = formatNumber(
		kpis.range,
		2
	);
	document.querySelector('.monthly-total-costs .kpi__value').textContent =
    formatNumber(parseFloat(kpis.monthlyTotalCosts), 2); 
    document.querySelector('.annual-total-costs .kpi__value').textContent =
    formatNumber(parseFloat(kpis.annualTotalCosts), 2); 
}

async function loadAndRenderData(carId, unit) {
    const data = await fetchStatsData(carId, unit);
    
    if (data) {
        updateKpis(data.kpis);
        updateConsumptionChart(data.chartData.labels, data.chartData.values, unit);
        renderTransactions(data.transactions);
    } else {
        updateKpis({});
        updateConsumptionChart([], [], unit);
        renderTransactions([]);
    }
}

function renderTransactions(transactions) {
    console.log("Transaktionen empfangen:", transactions);
    const container = document.querySelector('.transaction-list-body');
    if (!container) {
        console.error("Ziel-Container '.transaction-list-body' für Transaktionen nicht gefunden.");
        return;
    }
    container.innerHTML = '';
    
    if (!transactions || transactions.length === 0) {
        container.innerHTML = '<p class="text-center text-muted p-3">Keine Ausgaben gefunden.</p>';
        return;
    }
    transactions.forEach(transaction => {
        const item = document.createElement('div');
        item.classList.add('transaction-item', 'd-flex', 'justify-content-between', 'align-items-center', 'p-2'); 

        const amountFormatted = formatNumber(parseFloat(transaction.amount), 2);
        
        item.innerHTML = `
            <div class="transaction-info">
                <div class="transaction-category">${transaction.category}</div>
                <small class="transaction-date text-muted">${transaction.date}</small>
            </div>
            
            <div class="transaction-amount">
                <span class="badge rounded-pill ${getCategoryBadge(transaction.category)}">
                    ${amountFormatted} €
                </span>
            </div>
        `;

        container.appendChild(item);
    });
}

// -------------------------III. Diagramm- und Interaktionslogik ---------------------

function initializeConsumptionChart() {
	const ctx = document.getElementById('consumption-chart');
	const initialChartData = { labels: [], values: [] };
	const config= {
		type: 'line',
		data: {
			labels: initialChartData.labels,
			datasets: [
				{
					data: initialChartData.values,
					borderColor: PRIMARY_COLOR_SOLID,
					tension: 0.4,
					fill: true,
					backgroundColor: COLOR_CHART_FILL,
				},
			],
		},
		options: {
			responsive: true,
			locale: 'de-DE',
			maintainAspectRatio: false,
			plugins: {
        legend: {
          display: false 
        }
      },
			scales: {
				x: {
					type: 'time',
					time: {
						unit: 'month',
						tooltipFormat: 'MMM yyyy'
					},
					title: {
						display: true,
						text: 'Monat',
						align: 'end',
						color:'#3B3D3B'
					},
					grid: {
            display: false 
					},
				},
				y: {
					title: {
						display: true,
						text: 'Verbrauch (L/100km)',
						align: 'end',
						color:'#3B3D3B'
					},grid: {
                        display: true, 
                    },
                    min: 0,
                    max: 12.0
				}
			}
		},
	};
	consumptionChartInstance = new Chart(ctx, config);
}

function updateConsumptionChart(labels, values, unit) {
    consumptionChartInstance.data.labels = labels; 
    consumptionChartInstance.data.datasets[0].data = values;
    consumptionChartInstance.options.scales.x.time.unit = unit;

    let parserFormat;
    let displayFormat;

    if (unit === 'year') {
        parserFormat = 'yyyy'; 
        displayFormat = 'yyyy';
    } else if (unit === 'month') {
        parserFormat = 'yyyy-MM'; 
        displayFormat = 'MMM yyyy'; 
    } else { 
        parserFormat = 'yyyy-MM-dd'; 
        displayFormat = 'MMM d'; 
    }

    consumptionChartInstance.options.scales.x.time.parser = parserFormat;
    consumptionChartInstance.options.scales.x.time.unit = unit;
    consumptionChartInstance.options.scales.x.time.tooltipFormat = displayFormat;
    consumptionChartInstance.data.labels = labels; 
    consumptionChartInstance.data.datasets[0].data = values;

    let xAxisTitle = '';
    switch(unit) {
        case 'year':
            xAxisTitle = 'Jahr'; 
            break;
        case 'month':
            xAxisTitle = 'Monat'; 
            break;
        case 'day':
        default:
            xAxisTitle = 'Datum'; 
            break;
    }
    consumptionChartInstance.options.scales.x.title.text = xAxisTitle;
    
    consumptionChartInstance.update();
}

function setupTimeSwitcherListeners() {
    const container = document.querySelector('.chart__timeswitcher');
    if (!container) return; 

    container.addEventListener('click', async (event) => {
        const button = event.target.closest('.chart__time__btn');
        if (!button) return;
        const unit = button.getAttribute('data-unit');
        if (unit) {
            const activeButton = container.querySelector('.chart__time__btn.active');
            if (activeButton) {
                activeButton.classList.remove('active');
            }
            button.classList.add('active');

            const carId = getSelectedCarId();
            await loadAndRenderData(carId, unit);
        }
    });
}

function setupCarSelectListener() {
    const selectElement = document.getElementById('car-select'); // 假设 ID 是 'car-select'
    if (!selectElement) return;

    selectElement.addEventListener('change', (event) => {
        const newCarId = event.target.value;
        const currentUnit = document.querySelector('.chart__time__btn.active')?.getAttribute('data-unit') || 'month';
        loadAndRenderData(newCarId, currentUnit);
    });
}

// ---------------V. Start des Skripts -------------------------
document.addEventListener('DOMContentLoaded', initDashboard);
