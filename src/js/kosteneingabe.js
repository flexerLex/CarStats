document.addEventListener('DOMContentLoaded', function () {
    const carSelect = document.getElementById('car-select');
    const dynamicFieldsContainer = document.getElementById('dynamic-fields-container');
    const mileageField = document.getElementById('km');
    const fuelTypeInfo = document.getElementById('fuel-type-info');
    const fuelAmountField = document.getElementById('fuel-amount');
    const dateField = document.getElementById('date');

    let fuelTypes = {}; // Initialize an empty object for fuel types

    // Fetch fuel types from the server
    fetch('../php/fuel_types.php')
        .then(response => response.json())
        .then(data => {
            fuelTypes = data; // Assign the fetched fuel types to the variable
        })
        .catch(error => console.error('Error fetching fuel types:', error));

    // Обработчик смены автомобиля
    carSelect.addEventListener('change', function () {
        const carId = carSelect.value;

        if (carId) {
            fetch(`../php/get_car_data.php?car_id=${carId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    // Обновление полей формы
                    let fuelTypeSelect = document.getElementById('fuel-type');
                    if (!fuelTypeSelect) {
                        fuelTypeSelect = document.createElement('select');
                        fuelTypeSelect.id = 'fuel-type';
                        fuelTypeSelect.name = 'fuel_type';
                        fuelTypeSelect.classList.add('kosteneingabe-form__input');
                    }
                    fuelTypeSelect.innerHTML = ''; // Очищаем список

                    const option = document.createElement('option');
                    option.value = data.car.type; // Устанавливаем значение типа топлива
                    option.textContent = fuelTypes[data.car.type] || 'Unbekannt'; // Use the fetched fuel types
                    fuelTypeSelect.appendChild(option);
                    dynamicFieldsContainer.querySelector('.kosteneingabe-form__input-row').appendChild(fuelTypeSelect);
                })
                .catch(error => console.error('Error fetching car data:', error));
        }
    });

    // Определение глобальной функции updateFormFields
    window.updateFormFields = function(data) {
        const car = data.car;
        const lastFuel = data.lastFuel;
        const lastMileage = data.lastMileage;

        // Обновление типа топлива
        if (fuelTypeInfo) {
            fuelTypeInfo.textContent = car.fuel_description || 'Keine Daten verfügbar';
        } else {
            console.error('Element fuel-type-info not found');
        }

        // Обновление последнего пополнения топлива
        if (fuelAmountField) {
            fuelAmountField.placeholder = lastFuel ? `${lastFuel.quantity} Liter/kWh` : 'Keine Daten verfügbar';
        }

        // Обновление километража
        if (mileageField) {
            mileageField.placeholder = lastMileage || 'Keine Daten verfügbar';
        }

        // Установка текущей даты
        if (dateField) {
            const today = new Date().toISOString().split('T')[0];
            dateField.value = today;
        }
    };
});