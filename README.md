# CarStats
CarStats is the central tool for those who want to manage their car efficiently and cost-effectively

Autor: Rico Baur

##  UNSER PROJEKT
Mit Carstats können Sie den Überblick über alle Ihre Autos behalten.

Von TÜV und Service über Reparaturen bis hin zu Betankungen können Sie alle Kosten tracken und erhalten aussagekräftige 
Statistiken.
So behalten Sie Ihre Ausgaben und Ihren ökologischen Fußabdruck immer im Auge.
Zudem werden Sie an alle anstehenden Termine, wie der Durchführung eines Ölwechsels oder TÜV, erinnert und können so nie
wieder in die unangenehme Situation kommen, dies vergessen zu haben.



##  SETUPANLEITUNG

CarStats lokal lauffähig über XAMPP machen (erstellt von Rico Baur)


Klonen Sie das Projekt von Github und öffnen Sie es in PHPStorm.
Erstellen Sie in phpMyAdmin eine neue Database, geben Sie hierzu einfach folgenden Befehl unter dem Reiter „SQL“ ein:

CREATE DATABASE IF NOT EXISTS carstats;

Stellen Sie sicher, dass im Dokument „connect_DB.php“ im Ordner „php“ folgende Variablen richtig gesetzt sind:

$host = 'localhost';
$dbname = 'carstats';
$username = 'root';
$password = '';

Führen Sie nun das Dokument „setup_DB.php“ aus, das im Ordner „setup“ liegt. Dies können Sie einfach durch öffnen im 
Browser über PHP Storm tun, Sie werden darüber funktioniert, ob alles geklappt hat.

Sie sind nun bereit zu starten, registrieren Sie sich, loggen Sie sich ein, erstellen Sie sich Fahrzeuge und tracken 
auch Sie alles rund um Ihr Auto. Viel Spaß.

## TESTDATEN

Um Ihnen das Testen zu erleichtern gibt es bereits den Testaccount mit dem Benutzernamen "schuetz" und Passwort "fschuetz",
unter dem bereits ein Auto sowie Betankungen eingetragen sind.

## TECHNOLOGIEN
MySql, PHP, HTML, CSS, JavaScript