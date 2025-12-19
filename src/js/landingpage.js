// KEKSEEE
document.addEventListener("DOMContentLoaded", function () {
    const banner = document.getElementById("cookie-banner");

    if (!localStorage.getItem("cookieConsent")) {
        banner.classList.remove("hidden");
    }

    document.getElementById("cookie-accept").addEventListener("click", () => {
        localStorage.setItem("cookieConsent", "accepted");
        banner.classList.add("hidden");
    });

    document.getElementById("cookie-manage").addEventListener("click", () => {
        localStorage.setItem("cookieConsent", "accepted");
        banner.classList.add("hidden");
    });

    document.getElementById("cookie-decline").addEventListener("click", () => {
        localStorage.setItem("cookieConsent", "declined");
        banner.classList.add("hidden");
    });
});
