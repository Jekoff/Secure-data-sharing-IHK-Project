<?php
// Verbindung zur Datenbank herstellen
$servername = "localhost";
$username = "pwtoolusr";
$password = "lI^UrB0oqpgq~z04";
$dbname = "pwtool";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Verbindung zur Datenbank fehlgeschlagen: " . $e->getMessage();
}

// Initialisiere $stmt
$stmt = null;

// Prüfe, ob der GET-Parameter "token" gesetzt ist
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Überprüfe, ob ein Eintrag mit dem gegebenen Token in der Datenbank existiert
    $stmt = $conn->prepare("SELECT * FROM Benutzerdaten WHERE TOKEN = :token");
    $stmt->bindParam(":token", $token);
    $stmt->execute();

    if ($stmt->rowCount() == 1) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Überprüfe ob OTP übergeben wurde
        if (isset($_POST['otp'])) {
            $enteredOtp = $_POST['otp'];
            $correctOtp = $row['otp'];

            // Wenn der OTP korrekt ist, werden die Zugangsdaten angezeigt
            if ($enteredOtp == $correctOtp) {
                echo "<!DOCTYPE html>
                <html>
                <head>
                    <title>Kunden-Seite</title>
                </head>
                <body>
                    <h1>Kunden-Seite</h1>
                    <p>ACHTUNG: Die Zugangsdaten wurden erfolgreich abgeholt! Ihre Zugangsdaten wurden jetzt aus Sicherheitsgründen von unseren Servern gelöscht. Bitte speichern Sie diese ab.</p>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Mail</th>
                            <th>Password</th>
                            <th>Notes</th>
                            <th>loginurl</th>
                        </tr>
                        <tr>
                            <td>".$row['ID']."</td>
                            <td>".$row['Username']."</td>
                            <td>".$row['Mail']."</td>
                            <td>".$row['Password']."</td>
                            <td>".$row['Notes']."</td>
                            <td>".$row['loginurl']."</td>
                        </tr>
                    </table>
                </body>
                </html>";

                // Eintrag aus Sicherheitsgründen aus der Datenbank entfernen
                $deleteSql = "DELETE FROM Benutzerdaten WHERE TOKEN = :token";
                $stmt = $conn->prepare($deleteSql);
                $stmt->bindParam(":token", $token);
                $stmt->execute();
            } else {
                // Erhöhe die Anzahl der Fehlversuche (OTP)
                $failedLogins = $row['failedLogins'] + 1;

                // Lösche den Eintrag aus der Datenbank, falls der OTP 10x falsch eingegeben wurde
                if ($failedLogins >= 10) {
                    $deleteSql = "DELETE FROM Benutzerdaten WHERE TOKEN = :token";
                    $stmt = $conn->prepare($deleteSql);
                    $stmt->bindParam(":token", $token);
                    $stmt->execute();
                    echo "Der Datensatz wurde aus Sicherheitsgründen gelöscht. Bitte kontaktieren Sie millenium, um neue Zugangsdaten anzufordern.";
                } else {
                    echo "OTP stimmt nicht überein. Failed Logins: $failedLogins";
                    $updateFailedLoginsSql = "UPDATE Benutzerdaten SET failedLogins = :failedLogins WHERE TOKEN = :token";
                    $stmt = $conn->prepare($updateFailedLoginsSql);
                    $stmt->bindParam(":failedLogins", $failedLogins);
                    $stmt->bindParam(":token", $token);
                    $stmt->execute();
                }
            }
        } else {
            // Forular für die Eingabe des OTP anzeigen
            echo "<!DOCTYPE html>
            <html>
            <head>
                <title>Kunden-Seite</title>
            </head>
            <body>
                <h1>Kunden-Seite</h1>
                <form method='post'>
                    <label for='otp'>OTP:</label>
                    <input type='text' name='otp' id='otp' required><br>
                    <input type='submit' value='Daten anzeigen'>
                </form>
            </body>
            </html>";
        }
    } else {
        echo "Unter dem angegebenen Token sind keine Zugangsdaten zur Abholung bereit. Bitte kontaktieren Sie millenium um einen neuen Zugang zu erhalten.";
    }
} else {
    echo "Es wurde kein Token angegeben. Bitte prüfen Sie den Link oder kontaktieren Sie millenium um einen neuen Zugang zu erhalten.";
}

// Datenbankverbindung schließen
$conn = null;
?>