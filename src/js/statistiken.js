//--------------- I. Globale Variablen und Hilfsfunktionen ---------------------
let consumptionChartInstance = null;

const PRIMARY_COLOR_SOLID = '#155A03'; 
const COLOR_CHART_FILL = 'rgba(218, 237, 213, 0.5)';

// ------------II. Hauptinitialisierungsfunktion ------------------------
async function initDashboard() { 
    console.log('Dashboard Frontend Initialisierung gestartet...');
    
    // 检查 PHP 是否成功传递了数据
    if (typeof INITIAL_STATS_DATA === 'undefined' || !INITIAL_STATS_DATA.chartData) {
        console.error("INITIAL_STATS_DATA ist nicht definiert oder unvollständig!");
        return;
    }

    const { chartData } = INITIAL_STATS_DATA;
    
    // 获取当前的时间单位 (Tag, Monat, Jahr)
    const currentUnit = document.querySelector('.chart__time__btn.active')?.getAttribute('data-unit') || 'month';

    // 1. 初始化图表
    initializeConsumptionChart();
    
    // 2. 填充图表数据
    // 注意：根据你的 PHP，数据现在都在 chartData.values 中 (格式为 [{x: '...', y: ...}])
    updateConsumptionChart(chartData.values, currentUnit);
    
    // 注意：KPI 数字已经在 PHP 中渲染完成，此处不再调用 updateKpis 以防干扰格式
}

// -------------------------III. Diagramm- und Interaktionslogik ---------------------
function initializeConsumptionChart() {
    const ctx = document.getElementById('consumption-chart');
    if (!ctx) return; 

    const config = {
        type: 'line',
        data: {
            datasets: [
                {
                    label: 'Verbrauch (L/100km)',
                    data: [], // 初始为空
                    borderColor: PRIMARY_COLOR_SOLID,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: COLOR_CHART_FILL,
                    pointRadius: 4,
                    pointHoverRadius: 6
                },
            ],
        },
        options: {
            responsive: true,
            locale: 'de-DE',
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Verbrauch: ${context.parsed.y.toLocaleString('de-DE')} L/100km`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'month', 
                        tooltipFormat: 'dd.MM.yyyy', 
                    },
                    title: {
                        display: true,
                        text: 'Zeitraum',
                        align: 'end',
                        color:'#3B3D3B'
                    },
                    grid: { display: false },
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'L/100km',
                        align: 'end',
                        color:'#3B3D3B'
                    },
                    grid: { display: true }
                }
            }
        },
    };
    consumptionChartInstance = new Chart(ctx, config);
}

function updateConsumptionChart(values, unit) {
    if (!consumptionChartInstance) return;
    
    let displayFormat;
    let xAxisTitle = '';

    // 根据选择的单位调整显示格式
    if (unit === 'year') {
        displayFormat = 'yyyy';
        xAxisTitle = 'Jahr'; 
    } else if (unit === 'month') {
        displayFormat = 'MMM yyyy'; 
        xAxisTitle = 'Monat'; 
    } else { // day
        displayFormat = 'dd.MM.yyyy'; 
        xAxisTitle = 'Datum'; 
    }

    // 更新 Chart.js 数据集
    // values 格式为 [{x: "2023-10-01", y: 8.5}, ...]
    consumptionChartInstance.data.datasets[0].data = values;
    
    // 更新 X 轴配置
    consumptionChartInstance.options.scales.x.time.unit = unit;
    consumptionChartInstance.options.scales.x.time.tooltipFormat = displayFormat;
    consumptionChartInstance.options.scales.x.title.text = xAxisTitle;
    
    consumptionChartInstance.update();
}

// ---------------V. Start des Skripts -------------------------
document.addEventListener('DOMContentLoaded', initDashboard);