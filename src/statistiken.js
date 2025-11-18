// ===========================================
// I. Globale Variablen und Hilfsfunktionen (Global Variables and Utilities)
// ===========================================

let consumptionChartInstance = null;
const PLACEHOLDER_TEXT = '---';

function formatNumber(num, minDecimals) {
	// Prüft, ob die Eingabe eine gültige Zahl ist
	if (!Number.isFinite(num)) {
		return PLACEHOLDER_TEXT;
	}
	// [Hilfsfunktion] Formatiert eine Zahl im deutschen Format (mit Komma als Dezimaltrennzeichen).
	// Verwendet das integrierte Intl-Objekt für die internationale Formatierung
	return new Intl.NumberFormat('de-DE', {
		minimumFractionDigits: minDecimals,
		maximumFractionDigits: 2, // Behält maximal zwei Dezimalstellen bei
	}).format(num);
}

// ===========================================
// II. Framework zur Ansichtsaktualisierung (View Update Framework)
// ===========================================
// [Ansichtsfunktion] Aktualisiert die Werte aller KPI-Karten basierend auf den übergebenen Daten.
function updateKpis(kpis) {
	// Prüft, ob Daten übergeben wurden
	if (!kpis) {
		kpis = {};
	}
	// 1. Aktualisiert das Automodell
	const carModelElement = document.querySelector('.car-model');
	if (kpis.carModel) {
		carModelElement.textContent = kpis.carModel;
	} else {
	}
	// 2. Aktualisiert die KPI-Werte – findet das Element mit einem Selektor und setzt dessen Inhalt
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

// [Ansichtsfunktion] Füllt oder leert die Fortschrittsleiste für das Jahresbudget.
function updateBudget(budget) {
	const progressFill = document.querySelector('.progress-fill');
	const progressText = document.querySelector('.progress-text');

	if (budget && typeof budget.usedPercentage === 'number') {
		progressFill.style.width = budget.usedPercentage + '%';
		if (budget.usedText) {
			progressText.textContent = budget.usedText;
		} else {
			progressText.textContent = budget.usedPercentage + '% Jahrbudget gebraucht';
		}
	} else {
		// Standardstatus, wenn keine Daten vorhanden sind
		progressFill.style.width = '50%';
		progressText.textContent = 'Budgetdaten werden geladen...';
	}
}
// [Ansichtsfunktion] Rendert oder leert die Liste der letzten Transaktionen.
function renderTransactions(transactions) {
	const transactionCard = document.querySelector('.last-transactions-card');
	// ! ------------------ noch nicht fertig ------------------------ !
}

// ===========================================
// III. Diagramm- und Interaktionslogik (Interaction and Chart Setup)
// ===========================================

// [Diagrammfunktion] Initialisiert das Chart.js-Diagramm.
function initializeConsumptionChart() {
	const ctx = document.getElementById('consumption-chart');
	// Sicherheitsprüfung, um sicherzustellen, dass der Platzhalter existiert und die Chart.js-Bibliothek geladen ist
	if (!ctx || typeof Chart === 'undefined') {
		console.error(
			'Fehler: Chart.js-Bibliothek nicht geladen oder Diagramm-Platzhalter existiert nicht!'
		);
		return;
	}

	// Erstellt eine neue Chart.js-Instanz und speichert sie in der globalen Variable
	consumptionChartInstance = new Chart(ctx, {
		type: 'line',
		data: {
			labels: [], // Die anfänglichen Beschriftungen sind leer
			datasets: [
				{
					label: 'Kraftstoffverbrauch (L/100km)',
					data: [], // Die anfänglichen Daten sind leer
					borderColor: 'rgba(75, 192, 192, 1)',
					backgroundColor: 'rgba(75, 192, 192, 0.2)',
					tension: 0.1, // Lässt die Linie glatter erscheinen
				},
			],
		},
		options: {
			responsive: true,
			maintainAspectRatio: false,
		},
	});
}
// ! -------------------- noch nicht fertig ------------------------- !

// [Interaktionsfunktion] Legt die Klick-Events für die Zeitschaltflächen fest.
function setupTimeSwitcherListeners() {
	// Findet alle Schaltflächen zum Umschalten
	const buttons = document.querySelectorAll('.chart__time__btn');

	// Durchläuft jede Schaltfläche und fügt einen Event-Listener hinzu
	for (let i = 0; i < buttons.length; i++) {
		const button = buttons[i];

		button.addEventListener('click', function (event) {
			const timeframe = event.currentTarget.textContent.trim();

			// 1. Entfernt den 'active'-Stil von allen Schaltflächen
			for (let j = 0; j < buttons.length; j++) {
				buttons[j].classList.remove('active');
			}
			// 2. Fügt der aktuell angeklickten Schaltfläche den 'active'-Stil hinzu
			event.currentTarget.classList.add('active');

			console.log(
				'Benutzerinteraktion: Umschalten auf ' +
					timeframe +
					', Datenabruf wird vorbereitet...'
			);

			// ! ------------------- noch nicht fertig --------------------- !
			// **Hier muss zukünftig der Code für den Aufruf der Backend-API hinzugefügt werden**
			// Vorübergehend werden leere Daten verwendet, um das Diagramm zu leeren und den Ladezustand anzuzeigen
			updateConsumptionChart([]);
		});
	}
}

// ===========================================
// IV. Hauptinitialisierungsfunktion (Main Initialization)
// ===========================================

// Nach dem Laden der Seite wird diese Funktion ausgeführt, um das Dashboard zu starten.
function initDashboard() {
	console.log('Dashboard Frontend Initialisierung gestartet...');

	// Schritt 1: Initialisiert das Diagramm (Muss vor allen anderen Diagramm-Aktualisierungen ausgeführt werden)
	initializeConsumptionChart();

	// Schritt 2: Richtet die Benutzerinteraktion ein
	setupTimeSwitcherListeners();

	// Schritt 3: Ruft alle Ansichtsaktualisierungsfunktionen auf und initialisiert sie mit leeren Werten oder Platzhaltern
	// Übergabe leerer Objekte/Arrays zur Initialisierung:
	updateKpis({});
	updateBudget({});
	renderTransactions([]);
	updateConsumptionChart([]);

	// ! ------------------- noch nicht fertig --------------------- !
	// **Hier muss zukünftig der Code für den anfänglichen Backend-Datenabruf hinzugefügt werden**
	console.log('Warte auf Bereitschaft der Backend-Datenschnittstelle...');
}

// Stellt sicher, dass die Funktion 'initDashboard' erst ausgeführt wird, nachdem die HTML-Seite vollständig geladen und analysiert wurde
document.addEventListener('DOMContentLoaded', initDashboard);
