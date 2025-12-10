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

// #####################################
// ** 新增：获取当前选中车辆ID **
function getSelectedCarId() {
    // 假设你的车辆选择下拉菜单的 ID 是 'car-select'
    const selectElement = document.getElementById('car-select'); 
    
    // 如果元素不存在或没有选中值，我们可能需要一个默认 ID (例如 1)
    if (selectElement && selectElement.value) {
        return selectElement.value;
    }
    // TODO: 在真正的项目中，这个值应该从URL或会话中加载，这里先给默认值 1 (因为我们测试数据就是 ID 1)
    return 1; 
}


// ** 新增：从后端获取数据的核心函数 **
async function fetchStatsData(carId, unit) {
    if (!carId) {
        console.warn("Keine Fahrzeug-ID ausgewählt, breche Datenabruf ab.");
        updateKpis({}); // 清空 KPIs
        return;
    }

    const apiUrl = `../php/statistiken.php?car_id=${carId}&unit=${unit}`;
    console.log(`Daten abrufen von: ${apiUrl}`);

    try {
        const response = await fetch(apiUrl);

        if (!response.ok) {
            // 处理 HTTP 错误 (例如 401 Unauthorized, 400 Bad Request)
            const errorData = await response.json();
            throw new Error(`Backend Fehler: ${errorData.error || response.statusText}`);
        }

        const data = await response.json();
        console.log("Daten erfolgreich empfangen:", data);

        // 返回包含所有数据的对象
        return data; 

    } catch (error) {
        console.error("Fehler beim Abrufen der Statistikdaten:", error);
        alert("Fehler beim Laden der Daten: " + error.message);
        updateKpis({}); // 错误时清空数据
        return null;
    }
}

//############################################################################


//#######################################################################################
// ------------II. Hauptinitialisierungsfunktion ------------------------

// Diese Funktion wird gestartet, sobald die Seite geladen ist
async function initDashboard() { // 注意：这里必须加上 async
  console.log('Dashboard Frontend Initialisierung gestartet...');

  // 1: Initialisiert das Diagramm (暂时用模拟数据初始化)
  initializeConsumptionChart();

  // 2: Richtet die Benutzerinteraktion ein (设置时间切换器和车辆选择器监听)
  setupTimeSwitcherListeners();
  setupCarSelectListener(); // 确保新增这个监听函数

  // 3: 首次加载数据 (默认单位 'month')
  const carId = getSelectedCarId();
  await loadAndRenderData(carId, 'month');
  

}


// ###################################################

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


// ##########################
async function loadAndRenderData(carId, unit) {
    // 1. 获取数据
    const data = await fetchStatsData(carId, unit);
    
    if (data) {
        // 2. 更新 KPI
        updateKpis(data.kpis);
        
        // 3. 更新图表 (现在使用后端数据)
        updateConsumptionChart(data.chartData.labels, data.chartData.values, unit);
        
        // 4. 渲染交易记录
        renderTransactions(data.transactions);
    } else {
        // 数据加载失败，显示占位符
        updateKpis({});
        updateConsumptionChart([], [], unit);
        renderTransactions([]);
    }
}


function renderTransactions(transactions) {
    // 目标容器：现在可以找到 .transaction-list-body
    const container = document.querySelector('.transaction-list-body');
    if (!container) {
        console.error("Ziel-Container '.transaction-list-body' für Transaktionen nicht gefunden.");
        return;
    }

    // 1. 清空容器
    container.innerHTML = '';
    
    // 如果没有交易记录
    if (!transactions || transactions.length === 0) {
        container.innerHTML = '<p class="text-center text-muted p-3">Keine Ausgaben gefunden.</p>';
        return;
    }

    // 2. 遍历并插入交易记录
    transactions.forEach(transaction => {
        // 创建一个包裹元素，用于样式化和分隔
        const item = document.createElement('div');
        item.classList.add('transaction-item', 'd-flex', 'justify-content-between', 'align-items-center', 'p-2'); 

        // 格式化金额 (使用 formatNumber 函数)
        const amountFormatted = formatNumber(parseFloat(transaction.amount), 2);
        
        // 构建 HTML 内容
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

/**
 * 辅助函数: 根据类别返回 CSS 徽章类。
 */
function getCategoryBadge(category) {
    switch (category) {
        case 'Kraftstoff':
            return 'bg-success'; // 例如：绿色
        case 'Service':
        case 'Reparatur':
            return 'bg-warning'; // 例如：黄色
        case 'Versicherung':
        case 'Steuer':
            return 'bg-info'; // 例如：蓝色
        default:
            return 'bg-secondary'; // 例如：灰色
    }
}
// -------------------------III. Diagramm- und Interaktionslogik ---------------------

function initializeConsumptionChart() {
	// Initialisiert das Chart.js-Diagramm
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

function updateConsumptionChart(labels, values, unit) {
    // 如果是从后端获取数据，直接使用传入的 labels 和 values
    consumptionChartInstance.data.labels = labels; 
    consumptionChartInstance.data.datasets[0].data = values;
    consumptionChartInstance.options.scales.x.time.unit = unit;

		let titleText = 'Datum';
    let tooltipFormat = 'MM-dd'; // 默认值

    // 根据时间单位设置图表格式 (这是你之前缺失的部分)
    if (unit === 'year') {
        tooltipFormat = 'yyyy';
        titleText = 'Jahr';
    } else if (unit === 'month') {
        tooltipFormat = 'yyyy-MM';
        titleText = 'Monat';
    } 
    
    consumptionChartInstance.options.scales.x.time.tooltipFormat = tooltipFormat;

    consumptionChartInstance.options.scales.x.title.text = titleText;
    consumptionChartInstance.update();
}

function setupTimeSwitcherListeners() {
    const container = document.querySelector('.chart__timeswitcher');
    container.addEventListener('click', async (event) => { // 加上 async
        const button = event.target.closest('.chart__time__btn'); 
        if (!button) return;

        const unit = button.getAttribute('data-unit');

        if (unit) {
            // ... (保持 active 状态切换逻辑不变) ...

            // ** 执行后端数据获取和渲染 **
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
        // 车辆切换后，重新加载默认（或当前选中）时间单位的数据
        const currentUnit = document.querySelector('.chart__time__btn.active')?.getAttribute('data-unit') || 'month';
        loadAndRenderData(newCarId, currentUnit);
    });
}



// ---------------V. Start des Skripts -------------------------
document.addEventListener('DOMContentLoaded', initDashboard);
