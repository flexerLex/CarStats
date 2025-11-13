
let login_btn = document.getElementById('login_btn');
login_btn.addEventListener('click', login_active);

let register_btn = document.getElementById('register_btn');
register_btn.addEventListener('click', register_active);

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

// login mask auslesen
let login_username_input = document.getElementById('username').value;
let login_password_input = document.getElementById('password').value;

// Überprüfung / Absicherung einfügen
// Passwort hashen

let login_username = login_username_input.value;
let login_password = login_password_input.value;


// register mask auslesen
let first_name_input = document.getElementById('first_name_input');
let name_input = document.getElementById('name_input');
let email_input = document.getElementById('email_input');
let username_input = document.getElementById('username_input');
let password_input = document.getElementById('password_input');

let first_name = first_name_input.value;
let name = name_input.value;
let email = email_input.value;
let username = username_input.value;
let password = password_input.value;