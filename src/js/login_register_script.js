
let login_btn = document.getElementById('login_btn');
login_btn.addEventListener('click', login_active);

let register_btn = document.getElementById('register_btn');
register_btn.addEventListener('click', register_active);

/*
let login_submit_btn = document.getElementById('login_submit');
login_submit_btn.addEventListener('click', login_submitted);

let login_register_btn = document.getElementById('register_submit');
login_register_btn.addEventListener('click', register_submitted);
 */

let login_mask = document.getElementById('login_mask');
let register_mask = document.getElementById('register_mask');

function login_active() {
    login_btn.className="tab active";
    register_btn.className="tab";
    login_mask.className="form active";
    register_mask.className="form";
}

function register_active() {
    register_btn.className="tab active";
    login_btn.className="tab";
    register_mask.className="form active";
    login_mask.className="form";
}

function login_submitted() {
    let login_username = document.getElementById('username').value;
    let login_password = document.getElementById('password').value;
    window.alert("Daten wurden abgesendet")
    // Daten an Backend schicken
}

/*
function register_submitted() {
    let first_name = document.getElementById('first_name_input').value;
    let name = document.getElementById('name_input').value;
    let email = document.getElementById('email_input').value;
    let username = document.getElementById('username_input').value;
    let password = document.getElementById('password_input').value;
    window.alert("Daten wurden abgesendet")
    // Daten an Backend schicken
}
*/


// Überprüfung / Absicherung einfügen
// Passwort hashen

