//--------------- I. Globale Variablen und Hilfsfunktionen ---------------------
let consumptionChartInstance = null;

const PLACEHOLDER_TEXT = '---'; 

const PRIMARY_COLOR_SOLID = '#155A03'; 
const COLOR_CHART_FILL = 'rgba(218, 237, 213, 0.5)';

function formatNumber(num, minDecimals) {
    if (typeof num !== 'number' || isNaN(num) || num === null) {
        return PLACEHOLDER_TEXT;
    }
    const numberValue = parseFloat(num); 
    const useGrouping = minDecimals > 0 ? true : (numberValue >= 1000);

    const formatter = new Intl.NumberFormat('de-DE', {
        minimumFractionDigits: minDecimals,
        maximumFractionDigits: minDecimals,
        useGrouping: useGrouping
    });
    
    return formatter.format(numberValue);
}

// ------------II. Hauptinitialisierungsfunktion ------------------------
async function initDashboard() { 
    console.log('Dashboard Frontend Initialisierung gestartet...');
    
    if (typeof INITIAL_STATS_DATA === 'undefined') {
        console.error("INITIAL_STATS_DATA ist nicht im globalen Scope definiert! Backend-Integration prüfen.");
        initializeConsumptionChart(); 
        return;
    }

    const { kpis, chartData, transactions } = INITIAL_STATS_DATA;
    
    const currentUnit = document.querySelector('.chart__time__btn.active')?.getAttribute('data-unit') || 'month';

    // 1. Chart initialisieren und mit Daten füllen
    initializeConsumptionChart();
    updateConsumptionChart(chartData.labels, chartData.values, currentUnit);
    
    updateKpis(kpis);

}

// ------------ III. Funktionen zur Ansichtsaktualisierung ------------------------

function updateKpis(kpis) {
    if (!kpis) {
        kpis = {};
    }

    const carModelElement = document.querySelector('.car-model-display');
    if(carModelElement) {
        carModelElement.textContent = kpis.carModel || PLACEHOLDER_TEXT;
    }
    
    
    // Verbrauch (L/100km)
    document.querySelector('.average-consumption .kpi__value').textContent =
        formatNumber(kpis.averageConsumption, 2);
    
    // Spritkosten (€/1km)
    document.querySelector('.fuel-costs .kpi__value').textContent = 
        formatNumber(kpis.fuelCostsPerKm, 3); 
        
    // Kilometerstand (km)
    document.querySelector('.mileage .kpi__value').textContent = formatNumber(
        kpis.mileage,
        0
    );
    
    // Reichweite (km)
    document.querySelector('.reichweite .kpi__value').textContent = formatNumber(
        kpis.range,
        0
    );
    
    // Monatskosten (€)
    document.querySelector('.monthly-total-costs .kpi__value').textContent =
    formatNumber(parseFloat(kpis.monthlyTotalCosts), 2); 
    
    // Jahreskosten (€)
    document.querySelector('.annual-total-costs .kpi__value').textContent =
    formatNumber(parseFloat(kpis.annualTotalCosts), 2); 
    
}


// function getCategoryBadge(category) {
//     switch (category) {
//         case 'Kraftstoff':
//             return 'bg-success'; // 例如：绿色
//         case 'Service':
//         case 'Reparatur':
//             return 'bg-warning'; // 例如：黄色
//         case 'Versicherung':
//         case 'Steuer':
//             return 'bg-info'; // 例如：蓝色
//         default:
//             return 'bg-secondary'; // 例如：灰色
//     }
// }
// -------------------------III. Diagramm- und Interaktionslogik ---------------------
function initializeConsumptionChart() {
    const ctx = document.getElementById('consumption-chart');
    if (!ctx) return; 

    const config = {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    data: [],
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
                        tooltipFormat: 'MMM yyyy', 
                        parser: 'yyyy-MM' 
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
                    max: 12.0 // Beispielwert, kann dynamisch angepasst werden
                }
            }
        },
    };
    consumptionChartInstance = new Chart(ctx, config);
}

function updateConsumptionChart(labels, values, unit) {
    if (!consumptionChartInstance) {
        initializeConsumptionChart();
    }
    
    let parserFormat;
    let displayFormat;
    let xAxisTitle = '';

    if (unit === 'year') {
        parserFormat = 'yyyy'; 
        displayFormat = 'yyyy';
        xAxisTitle = 'Jahr'; 
    } else if (unit === 'month') {
        parserFormat = 'yyyy-MM'; 
        displayFormat = 'MMM yyyy'; 
        xAxisTitle = 'Monat'; 
    } else { // day
        parserFormat = 'yyyy-MM-dd'; 
        displayFormat = 'MMM d'; 
        xAxisTitle = 'Datum'; 
    }

    consumptionChartInstance.data.datasets[0].data = values;
    consumptionChartInstance.options.scales.x.time.unit = unit;
    consumptionChartInstance.options.scales.x.time.parser = parserFormat;
    consumptionChartInstance.options.scales.x.time.tooltipFormat = displayFormat;
    consumptionChartInstance.options.scales.x.title.text = xAxisTitle;
    
    consumptionChartInstance.update();
}

// ---------------V. Start des Skripts -------------------------
document.addEventListener('DOMContentLoaded', initDashboard);