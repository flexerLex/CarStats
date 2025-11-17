// -------------script für letzt Ausgabe und Kosteneingabe------------------------------------------
/**
 * Liest die gespeicherten Transaktionen aus dem lokalen Speicher (localStorage) und zeigt sie an.
 * (Lädt die gespeicherten Ausgaben und rendert sie im DOM.)
 */
function loadAndRenderTransactions() {
	// 1. Daten aus dem Speicher laden (Wenn nichts da ist, nehmen wir ein leeres Array: '[]')
	const transactionsData = JSON.parse(
		localStorage.getItem('carStatsTransactions') || '[]'
	);

	// Den Hauptcontainer für die Transaktionsliste finden
	const cardContainer = document.querySelector('.last-transactions-card');
	// Wenn der Container nicht existiert, brechen wir ab (wichtig, falls das Skript woanders läuft)
	if (!cardContainer) return;

	// Die Überschrift (Header) finden, um die Liste darunter zu platzieren
	const header = cardContainer.querySelector('.transaction-header');

	// Alte/vorherige Transaktionseinträge entfernen (damit wir die Liste aktualisieren können)
	while (header.nextElementSibling) {
		header.nextElementSibling.remove();
	}

	// Wir zeigen nur die neuesten 5 Einträge an ('slice(0, 5)' schneidet das Array)
	const transactionsToShow = transactionsData.slice(0, 5);

	let htmlContent = ''; // Variable, um den gesamten HTML-Code zu sammeln

	// 2. Schleife durch die Einträge, um den HTML-Code zu bauen
	transactionsToShow.forEach((transaction) => {
		// Datum und Betrag für die deutsche Anzeige formatieren (z.B. Komma statt Punkt)
		const displayDate = new Date(transaction.date).toLocaleDateString('de-DE');
		const displayAmount = transaction.amount.toFixed(2).replace('.', ',') + '€';

		// Hier wird der HTML-Template-String für den einzelnen Eintrag erstellt
		htmlContent += `
                <div class="transaction-item" style="--icon-color: ${transaction.iconColor};">
                    <div class="transaction-icon-container">
                        <svg class="transaction-item-icon" viewBox="0 0 24 24">
                            <path d="${transaction.iconPath}"></path> 
                        </svg>
                    </div>
                    <div class="transaction-details">
                        <div class="transaction-name">${transaction.description}</div>
                        <div class="transaction-date">${displayDate}</div>
                    </div>
                    <span class="transaction-amount">${displayAmount}</span>
                </div>
            `;
	});

	// 3. Den gesammelten HTML-Code am Ende der Überschrift (Header) einfügen
	cardContainer.insertAdjacentHTML('beforeend', htmlContent);
}

// Wichtig: Wir starten die Funktion erst, wenn die gesamte Seite geladen ist
document.addEventListener('DOMContentLoaded', loadAndRenderTransactions);
