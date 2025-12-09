//--------------- I. Globale Variablen und Hilfsfunktionen ---------------------

let consumptionChartInstance = null;
const PLACEHOLDER_TEXT = '---';

const PRIMARY_COLOR_SOLID = '#155A03'; 
const COLOR_CHART_FILL = 'rgba(218, 237, 213, 0.5)';

function formatNumber(num, minDecimals) {
	// Funktion: Formatiert eine Zahl im deutschen Format (Komma als Dezimaltrennzeichen).
	if (typeof num !== 'number') {
		return PLACEHOLDER_TEXT;
	}
	let formattedString = num.toFixed(minDecimals);
	return formattedString.replace('.', ',');
}



//############################################################################

// 模拟的原始数据（通常是你从后端获取的数据）
const rawData = [
    { date: '2024-01-01', sales: 150 },
    { date: '2024-01-05', sales: 200 },
    { date: '2024-01-15', sales: 180 },
    { date: '2024-02-10', sales: 300 },
    { date: '2024-02-25', sales: 250 },
    { date: '2025-01-03', sales: 220 },
    { date: '2025-01-10', sales: 190 },
    // ... 实际数据会更多
];

// 这是一个模拟的“聚合”函数
function getAggregatedData(unit) {
    if (unit === 'day') {
        // 假设这里返回了原始数据 (按天)
        return {
            labels: rawData.map(d => d.date),
            values: rawData.map(d => d.sales),
        };
    } else if (unit === 'month') {
        // 模拟按月聚合 (例如：1月总和 530, 2月总和 550, 2025年1月总和 410)
        return {
            labels: ['2024-01', '2024-02', '2025-01'],
            values: [530, 550, 410],
        };
    } else if (unit === 'year') {
        // 模拟按年聚合 (例如：2024年总和 1080, 2025年总和 410)
        return {
            labels: ['2024', '2025'],
            values: [1080, 410],
        };
    }
}
//#######################################################################################
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

	// // 🔴 静态数据源（硬编码数据）
	// const initialChartData = [9.5, 10.1, 9.8, 11.0, 10.5, 9.9];
	// updateConsumptionChart(initialChartData);

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
		2
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

	const initialChartData = getAggregatedData('day');

	const config= {
		type: 'line',
		data: {
			labels: initialChartData.labels,
			datasets: [
				{
					data: initialChartData.values,
					borderColor: PRIMARY_COLOR_SOLID,
					tension: 0.2,
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
          display: false //隐藏label
        }
      },
			scales: {
				x: {
					type: 'time',
					time: {
						unit: 'day',
						tooltipFormat: 'MM-dd'
					},
					title: {
						display: true,
						text: 'Datum',
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
          }
				}
			}
		},
	};
	consumptionChartInstance = new Chart(ctx, config);
}

function updateConsumptionChart(unit) {
	const newData = getAggregatedData(unit);

	consumptionChartInstance.data.labels = newData.labels;
	consumptionChartInstance.data.datasets[0].data = newData.values;
	consumptionChartInstance.options.scales.x.time.unit = unit;

	let titleText = 'Datum';
	if (unit === 'year') {
        consumptionChartInstance.options.scales.x.time.tooltipFormat = 'yyyy';titleText = 'Jahr';
    } else if (unit === 'month') {
        consumptionChartInstance.options.scales.x.time.tooltipFormat = 'yyyy-MM';titleText = 'Monat';
    } else {
        consumptionChartInstance.options.scales.x.time.tooltipFormat = 'MM-dd';titleText = 'Datum';
    }
	consumptionChartInstance.options.scales.x.title.text = titleText;
	consumptionChartInstance.update();
}

function setupTimeSwitcherListeners() {
  // 假设所有按钮的父容器有一个 ID，比如 'time-selector-container'
  // 如果没有，你需要找到它们的共同父元素。
	const container = document.querySelector('.chart__timeswitcher');
	// if (!container) {
  //     console.error("Fehler: Eltern-Container '.chart__timeswitcher' wurde nicht gefunden. Die Klick-Events werden nicht gesetzt.");
  //     return; 
  // }

  container.addEventListener('click', (event) => {
      // 确保点击的是带有 data-unit 属性的按钮
      const button = event.target.closest('.chart__time__btn'); 
      if (!button) return;

      const unit = button.getAttribute('data-unit');

      if (unit) {
          // 移除所有按钮的 active 状态，并设置当前按钮为 active
          document.querySelectorAll('.chart__time__btn').forEach(btn => {
              btn.classList.remove('active');
          });
          button.classList.add('active');

          // 执行图表更新
          updateConsumptionChart(unit);
      }
  });
}



// ---------------V. Start des Skripts -------------------------
document.addEventListener('DOMContentLoaded', initDashboard);
