//--------------- I. Globale Variablen und Hilfsfunktionen ---------------------

let consumptionChartInstance = null;
const PLACEHOLDER_TEXT = '---';

function formatNumber(num, minDecimals) {
	// Funktion: Formatiert eine Zahl im deutschen Format (Komma als Dezimaltrennzeichen).
	if (typeof num !== 'number') {
		return PLACEHOLDER_TEXT;
	}
	let formattedString = num.toFixed(minDecimals);
	return formattedString.replace('.', ',');
}

function generateLabels(count) {
	let labels = [];
	for (let i = 1; i <= count; i++) {
		labels.push('Punkt ' + i);
	}
	return labels;
}

// ------------II. Hauptinitialisierungsfunktion ------------------------

// Diese Funktion wird gestartet, sobald die Seite geladen ist
function initDashboard() {
	console.log('Dashboard Frontend Initialisierung gestartet...');

	// 1: Initialisiert das Diagramm
	initializeConsumptionChart();

	// 2: Richtet die Benutzerinteraktion ein
	setupTimeSwitcherListeners();

	// 3: Ruft alle Ansichtsaktualisierungsfunktionen auf und initialisiert sie mit Platzhaltern
	updateKpis({});
	updateBudget({});
	renderTransactions([]);
	updateConsumptionChart([]);

	// ! ------------------- noch nicht fertig --------------------- ! **Hier muss zukünftig der Code für den anfänglichen Backend-Datenabruf hinzugefügt werden**
	console.log('Warte auf Bereitschaft der Backend-Datenschnittstelle...');
}

// ------------ III. Funktionen zur Ansichtsaktualisierung ------------------------
function updateKpis(kpis) {
	// Aktualisiert die Werte aller KPI-Karten
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
		0
	);

	document.querySelector('.monthly-total-costs .kpi__value').textContent =
		formatNumber(kpis.monthlyTotalCosts, 2);

	document.querySelector('.annual-total-costs .kpi__value').textContent =
		formatNumber(kpis.annualTotalCosts, 2);
}



function renderTransactions(transactions) {
	// Rendert oder leert die Liste der letzten Transaktionen.
	const transactionCard = document.querySelector('.last-transactions-card');
	console.log('Transaktionsliste wird noch nicht gerendert.');
	// ! ------------------ noch nicht fertig ------------------------ !
}

// -------------------------III. Diagramm- und Interaktionslogik ---------------------

function initializeConsumptionChart() {
	// Initialisiert das Chart.js-Diagramm
	const ctx = document.getElementById('consumption-chart');

	if (!ctx) {
		console.error('Fehler: Diagramm-Platzhalter fehlt!');
		return;
	}

	if (typeof Chart === 'undefined') {
		console.error('Chart.js Bibliothek wurde nicht geladen.');
		return;
	}

	consumptionChartInstance = new Chart(ctx, {
		type: 'line',
		data: {
			labels: [],
			datasets: [
				{
					label: 'Kraftstoffverbrauch (L/100km)',
					data: [],
					borderColor: 'teal',
					backgroundColor: 'rgba(0, 128, 128, 0.2)',
					tension: 0.1,
				},
			],
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
		},
	});
}

function updateConsumptionChart(newData) {
	// Aktualisiert die Daten und Labels des Diagramms
	if (!consumptionChartInstance) return;

	consumptionChartInstance.data.datasets[0].data = newData;
	consumptionChartInstance.data.labels = generateLabels(newData.length);

	consumptionChartInstance.update();
}

function setupTimeSwitcherListeners() {
	// Legt die Klick-Events für die Zeitschaltflächen fest.
	const allButtons = document.querySelectorAll('.chart__time__btn');

	for (let i = 0; i < allButtons.length; i++) {
		let button = allButtons[i];

		button.addEventListener('click', function (event) {
			const timeframe = event.target.textContent.trim();

			for (let j = 0; j < allButtons.length; j++) {
				allButtons[j].classList.remove('active');
			}

			event.target.classList.add('active');

			console.log(
				'Benutzerinteraktion: Umschalten auf ' +
					timeframe +
					', Datenabruf wird vorbereitet...'
			);

			// ! ------------------- noch nicht fertig --------------------- !
			// Placeholder für den Backend-Datenabruf

			updateConsumptionChart([]);
		});
	}
}

// ---------------V. Start des Skripts -------------------------
document.addEventListener('DOMContentLoaded', initDashboard);
