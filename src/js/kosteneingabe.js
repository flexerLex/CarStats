document.addEventListener('DOMContentLoaded', function () {
    const carSelect = document.getElementById('car-select');
    const dynamicFieldsContainer = document.getElementById('dynamic-fields-container');
    const mileageField = document.getElementById('km');
    const dateField = document.getElementById('date');
    const carIdField = document.getElementById('car_id');
    const categoryField = document.getElementById('category');
    const successMessage = document.getElementById('success-message');
    const errorMessage = document.getElementById('error-message');
    const expenseBtns = document.querySelectorAll('.kosteneingabe-expense-filter__btn');

    let fuelTypes = {};
    let dateInitialized = false;

    if (carSelect && carIdField) {
        carIdField.value = carSelect.value;
        carSelect.addEventListener('change', function () {
            carIdField.value = carSelect.value;
            const carId = carSelect.value;
            if (carId) {
                fetch(`../php/get_car_data.php?car_id=${carId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }
                        updateFormFields(data);
                    })
                    .catch(error => console.error('Error fetching car data:', error));
            }
        });
        if (carSelect.value) {
            carSelect.dispatchEvent(new Event('change'));
        }
    }

    expenseBtns.forEach((btn) => {
        btn.addEventListener('click', function () {
            expenseBtns.forEach((b) => b.classList.remove('active'));
            this.classList.add('active');
            const currentCategory = this.dataset.filter;
            if (categoryField) {
                categoryField.value = currentCategory;
            }
            updateDynamicFields(currentCategory);

            const carId = carSelect.value;
            if (carId) {
                fetch(`../php/get_car_data.php?car_id=${carId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error(data.error);
                            return;
                        }
                        updateFormFields(data);
                    })
                    .catch(error => console.error('Error fetching car data:', error));
            }
        });
    });

    let currentCategory = categoryField ? categoryField.value : 'Sprit';
    fetch('../php/fuel_types.php')
        .then(response => response.json())
        .then(data => {
            fuelTypes = data;
            updateDynamicFields(currentCategory);
        })
        .catch(error => console.error('Error fetching fuel types:', error));

    function updateDynamicFields(category) {
        let html = '';
        switch (category) {
            case 'Sprit':
                html = `
                    <div class="kosteneingabe-form__input-row">
                        <label class="kosteneingabe-form__label">Kraftstoffart:</label>
                        <span id="fuel-type-info" class="kosteneingabe-form__info"></span>
                    </div>
                    <div class="kosteneingabe-form__input-row">
                        <label class="kosteneingabe-form__label" for="fuel-amount">Getankte Menge (Liter/kWh)</label>
                        <input type="number" id="fuel-amount" name="fuel_amount" min="0" step="0.01" class="kosteneingabe-form__input" placeholder="Letzte Menge">
                    </div>
                `;
                dynamicFieldsContainer.innerHTML = html;

                let inputRow = dynamicFieldsContainer.querySelector('.kosteneingabe-form__input-row');
                if (!inputRow) {
                    inputRow = document.createElement('div');
                    inputRow.classList.add('kosteneingabe-form__input-row');
                    dynamicFieldsContainer.appendChild(inputRow);
                }

                const fuelTypeSelect = document.createElement('select');
                fuelTypeSelect.id = 'fuel-type';
                fuelTypeSelect.name = 'fuel_type';
                fuelTypeSelect.classList.add('kosteneingabe-form__input');
                inputRow.appendChild(fuelTypeSelect);

                if (Object.keys(fuelTypes).length === 0) {
                    console.error('Fuel types not loaded yet');
                    return;
                }
                fetch('../php/get_car_data.php?car_id=' + carSelect.value)
                    .then(response => response.json())
                    .then(data => {
                        const fuelTypeInfo = document.getElementById('fuel-type-info');
                        if (fuelTypeInfo) {
                            fuelTypeInfo.textContent = data.car.fuel_description || '';
                        }
                        fuelTypeSelect.innerHTML = '';
                        const option = document.createElement('option');
                        option.value = data.car.type;
                        option.textContent = fuelTypes[data.car.type] || 'Unbekannt';
                        fuelTypeSelect.appendChild(option);
                    })
                    .catch(error => console.error('Error fetching car data:', error));
                break;

            case 'Service':
            case 'Reparatur':
                html = `
                    <div class="kosteneingabe-form__input-row">
                        <label class="kosteneingabe-form__label" for="service-type">Art der Leistung</label>
                        <input type="text" id="service-type" name="service-type" class="kosteneingabe-form__input" placeholder="z.B. Ölwechsel, Bremsen">
                    </div>
                `;
                dynamicFieldsContainer.innerHTML = html;
                break;

            case 'Versicherung':
                html = `
                    <div class="kosteneingabe-form__input-row">
                        <label class="kosteneingabe-form__label" for="insurance-type">Versicherungsart</label>
                        <input type="text" id="insurance-type" name="insurance-type" class="kosteneingabe-form__input" placeholder="z.B. Haftpflicht, Vollkasko">
                    </div>
                `;
                dynamicFieldsContainer.innerHTML = html;
                break;

            case 'Anschaffung':
            case 'Zubehör':
                html = `
                    <div class="kosteneingabe-form__input-row">
                        <label class="kosteneingabe-form__label" for="purchase-desc">Beschreibung</label>
                        <input type="text" id="purchase-desc" name="purchase-desc" class="kosteneingabe-form__input" placeholder="z.B. Dachbox, Navi">
                    </div>
                `;
                dynamicFieldsContainer.innerHTML = html;
                break;

            default:
                dynamicFieldsContainer.innerHTML = '';
        }

        if (dateField && !dateInitialized) {
            const today = new Date().toISOString().split('T')[0];
            dateField.value = today;
            dateInitialized = true;
        }

        dynamicFieldsContainer.classList.remove('kosteneingabe-form__fields-animate');
        void dynamicFieldsContainer.offsetWidth;
        dynamicFieldsContainer.classList.add('kosteneingabe-form__fields-animate');
    }

    window.updateFormFields = function(data) {
        const car = data.car;
        const lastFuel = data.lastFuel;
        const lastMileage = data.lastMileage;

        const fuelTypeInfo = document.getElementById('fuel-type-info');
        const fuelAmountField = document.getElementById('fuel-amount');

        if (fuelTypeInfo) {
            fuelTypeInfo.textContent = car.fuel_description || '';
        }
        if (fuelAmountField) {
            fuelAmountField.placeholder = lastFuel ? `${lastFuel.quantity} Liter/kWh` : 'Keine Daten verfügbar';
        }
        if (mileageField) {
            mileageField.placeholder = lastMileage || 'Keine Daten verfügbar';
        }
        if (dateField && !dateInitialized) {
            const today = new Date().toISOString().split('T')[0];
            dateField.value = today;
            dateInitialized = true;
        }
    };

    if (successMessage) {
        successMessage.classList.add('show');
        setTimeout(() => {
            successMessage.classList.remove('show');
        }, 5000);
    }
    if (errorMessage) {
        errorMessage.classList.add('show');
        setTimeout(() => {
            errorMessage.classList.remove('show');
        }, 5000);
    }
});