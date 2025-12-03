// eintraege loeschen / bearbeiten
// neue Fahrzeuge anlegen

let button_edit0 = document.getElementById('btn_edit0');
button_edit0.addEventListener('click', edit_vehicle0);

function edit_vehicle0(){
    // Ans Backend senden und in Datenbank anpassen mit vorgefertigter SQL
}

let button_delete0 = document.getElementById('btn_delete0');
button_edit0.addEventListener('click', delete_vehicle0);

function delete_vehicle0(){
    // Ans Backend senden und in Datenbank anpassen mit vorgefertigter SQL
}

let button_edit1 = document.getElementById('btn_edit1');
button_edit1.addEventListener('click', edit_vehicle1);

function edit_vehicle1(){
    // Ans Backend senden und in Datenbank anpassen mit vorgefertigter SQL
}

let button_delete1 = document.getElementById('btn_delete1');
button_edit1.addEventListener('click', delete_vehicle1);

function delete_vehicle1(){
    // Ans Backend senden und in Datenbank anpassen mit vorgefertigter SQL
}

let button_new_vehicle = document.getElementById('btn_new_vehicle_submit');
button_new_vehicle.addEventListener('click', collectdata);

function collectdata(){
    // Überprüfung auf schädliche oder falsche Eingaben wird noch ergänzt
    let brand = document.getElementById('brand').value;
    let model = document.getElementById('model').value;
    let age = document.getElementById('age').value;
    let identification = document.getElementById('identification').value;
    let type = document.getElementById('type').value;
    let mileage = document.getElementById('mileage').value;
    let tuevalue = document.getElementById('tue').value;
    let oilchange = document.getElementById('oilchange').value;
    let greatservice = document.getElementById('greatservice').value;
    //Daten als JSON ins Backend schicken
    window.alert("Daten wurden abgesendet");
}