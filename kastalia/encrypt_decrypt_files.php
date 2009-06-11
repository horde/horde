<?php
/**
 *
 * This product includes software developed by the Horde Project (http://www.horde.org/).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Andre Pawlowski aka sqall <sqall@h4des.org>
 */

//das absolute Kastalia directory wird als Konstante definiert
//um damit config Dateien zu includieren
@define('KASTALIA_BASE', dirname(__FILE__)); 

//die Basis von Kastalia wird includiert
//um die Anmeldung zu ueberpruefen
require_once(KASTALIA_BASE . '/lib/base.php');
//die Konfigurationsdatei von Kastalia wird includiert
//um alle Kastalia Einstellungen in diesem Skript nutzen zu koennen
require(KASTALIA_BASE . '/config/conf.php');
//das Menu von Kastalia wird includiert
//damit das Menu dem Benutzer angezeigt wird
require_once(KASTALIA_BASE . '/list.php');

echo "<div class=\"header\">Kastalia Datastore - Encryption/Decryption</div>\n";
echo "<br />\n";

//nun wird ueberprueft, ob das verschluesseln/entschluesseln von Dateien aktiviert wurde
//falls nicht, wird eine Meldung ausgegeben und das Skript beendet
if(!$conf['upload']['securestore']) {
    //mit dieser Funktion werden aus Sicherheitsgruenden nach der Verschluesselung
    //alle SESSION Variablen die damit zu tun haben geloescht
    UnsetSessionVars();
    echo "Encryption/Decryption is disabled!";
    exit(0);
}

//hier werden die fuer die Weiterverarbeitung noetigen SESSION und config Variablen in lokalen Variablen gespeichert
$kastalia_mode = $_SESSION['kastalia_mode']; //diese Variable legt fest, ob ver- oder entschluesselt werden soll
$input_name = $_SESSION['kastalia_input_name']; //gibt den Namen + Ort der Quelldatei an
$output_name = $_SESSION['kastalia_output_name']; //gibt den Namen + Ort der Zieldatei an
$kastalia_part_number = $_SESSION['kastalia_part_number']; //gibt die aktuelle ver/entschluesselungsrunde an
$key = $_SESSION['kastalia_key']; //gibt den key zum ver/entschluesseln an
$kastalia_memory_size = $conf['upload']['memorysize']; //gibt die groesse des zu Verfuegung stehenden Speichers an, mit diesem Wert wird die groesse des zu ver/entschluesselnden Teiles berechnet
$refresh_cycle = $conf['upload']['refreshcycle']; //gibt an, wie lange nach Beendigung einer Ver/Entschluesselungsrunde der naechste Aufruf vom Browser gestartet wird

//hier wird ueberprueft, ob der Schluessel fuer die Ver/Entschluesselung gesetzt wurde
//falls nicht, wird abgebrochen
if($key == "") {
    echo "Error: \$key in encrypt_decrypt_files.php is empty!<br />\n";
    //falls es sich um die Verschluesselung einer Datei handelt, muss die temporaere unverschluesselte Datei
    //unbedingt geloescht werden. Hier wird ueberprueft ob die Datei verschluesselt werden sollte
    if($kastalia_mode == "encrypt") {
        echo "deleting temporary file...<br />\n";
        //hier wird nun die temporaere Datei geloescht, falls ein Fehler auftreten sollte, wird ein Error ausgegeben
            if(!unlink($input_name)) {
            echo "Error: Unable to delete temporary file!<br />\n";
            echo "This constitutes a <b>breach of security</b> because the content of the uploaded file stored unencrypted.<br />\n";
            echo "Please contact the administrator to have the temporary file " . $input_name . " deleted.\n";
        }
        else {
            echo "...done.\n";
        }
    }
    //mit dieser Funktion werden aus Sicherheitsgruenden nach der Verschluesselung
    //alle SESSION Variablen die damit zu tun haben geloescht
    UnsetSessionVars();
    exit(1);
}

//in dieser switch-case Anweisung wird durch kastalia_mode entschieden ob ver- oder entschluesselt werden soll
switch($kastalia_mode) {
    case "encrypt": //######################################### SPLIT AND ENCRYPT FILE #########################################
        //hier wird die Anzahl der Dateiteile berechnet, die auch gleichzeitig die Anzahl der Verschluesselungsrunden angibt
        $max_parts = ceil(filesize($input_name) / $kastalia_memory_size);
        //hier wird ueberprueft, ob alle Verschluesselungsrunden durchlaufen wurden...
        if($kastalia_part_number < $max_parts) {
            //... falls nein, wird die naechste Verschluesselungsrunde eingeleitet
            //mit dieser Funktion wird die Datei in Stuecken ausgelesen und verschluesselt wieder zusammengesetzt
            //je ein Stueck pro Verschluesselungsrunde
            SplitAndEncryptFile($input_name, $conf['upload']['tempdir'] . "/" . basename($output_name), $kastalia_part_number, $max_parts, $key);
            //hier werden die Variablen fuer das weitere vorgehen des Skriptes wieder in die SESSION Variablen gespeichert
            $_SESSION['kastalia_mode'] = "encrypt"; //diese Variable legt fest, ob ver- oder entschluesselt werden soll
            $_SESSION['kastalia_input_name'] = $input_name; //gibt den Namen + Ort der Quelldatei an
            $_SESSION['kastalia_output_name'] = $output_name; //gibt den Namen + Ort der Zieldatei an
            $_SESSION['kastalia_part_number'] = $kastalia_part_number + 1; //gibt die aktuelle ver/entschluesselungsrunde an (+1)
            $_SESSION['kastalia_key'] = $key; //gibt den key zum ver/entschluesseln an

            //##################### <HTML HEADER MANIPULATION> #####################
            //Header Manipulation zum aufrufen der naechsten Verschluesselungsrunde
            echo "<head>\n";
            echo "<meta http-equiv=\"refresh\" content=\"" . $refresh_cycle . "; URL=encrypt_decrypt_files.php\">\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<p>\n";
            echo "encrypting part " . ($kastalia_part_number + 1) . " of " . $max_parts . "<br />";
            echo "please wait...<br />";
            echo "</p>\n";
            echo "</body>\n";
            //##################### </HTML HEADER MANIPULATION> #####################

        }
        else {
            //... falls ja, werden die letzten Schritte nach der Verschluesselung durchgefuert
            //mit dieser Funktion werden aus Sicherheitsgruenden nach der Verschluesselung
            //alle SESSION Variablen die damit zu tun haben geloescht
            UnsetSessionVars();
            //hier wird die temporaere verschluesselte Datei an die richtige finale Stelle verschoben,
            //damit sie heruntergeladen werden kann
            if(!rename($conf['upload']['tempdir'] . "/" . basename($output_name) . ".kastaliaenc", $output_name . ".kastaliaenc")) {
                //sollte ein Fehler beim verschieben der temporaeren verschluesselten Datei auftreten
                //wird eine Fehlermeldung ausgegeben, aber nicht abgebrochen, da die nachfolgenden Befehle noch abgearbeitet werden muessen
                echo "Error: Encrypted file couldn't be moved to final position " . $output_name . ".kastaliaenc<br />\n";
            }
            echo "Encryption done...<br />\n";
            echo "File successfully stored under <b>" . substr($output_name, strlen($conf['datastore']['location'] . "/")) . "</b> !\n";
            //hier wird nun die temporaere unverschluesselte Datei geloescht (Wichtig: sollte hier ein Fehler auftreten
            //liegt eine unverschluesselte Version der Datei in dem temporaeren Verzeichnis!)
            if(!unlink($input_name)) {
                echo "Error: Unable to delete temporary file after encryption!<br />\n";
                echo "This constitutes a <b>breach of security</b> because the content of the uploaded file still stored unencrypted.<br />\n";
                echo "Please contact the administrator to have the temporary file " . $input_name . " deleted.\n";
                exit(1);
            }
        }
        break;
    case "decrypt": //######################################### DECRYPT AND MERGE FILES #########################################
        //hier wird die Endung ".kastaliaenc" an den input_name gesetzt, da die von Kastalia verschluesselten
        //Dateien immer diese Endung besitzen
        $temp_input_name = $input_name . ".kastaliaenc";
        //hier wird die Anzahl der Dateiteile berechnet, die auch gleichzeitig die Anzahl der Entschluesselungsrunden angibt
        $max_parts = ceil(filesize($temp_input_name) / $kastalia_memory_size);
        //hier wird ueberprueft, ob alle Entschluesselungsrunden durchlaufen wurden...
        if($kastalia_part_number < $max_parts) {
            //... falls nein, wird die naechste Entschluesselungsrunde eingeleitet
            //mit dieser Funktion wird die Datei in Stuecken ausgelesen und entschluesselt wieder zusammengesetzt
            //je ein Stueck pro Entschluesselungsrunde
            MergeAndDecryptFile($input_name, $output_name, $kastalia_part_number, $max_parts, $key);
            //hier werden die Variablen fuer das weitere vorgehen des Skriptes wieder in die SESSION Variablen gespeichert
            $_SESSION['kastalia_mode'] = "decrypt"; //diese Variable legt fest, ob ver- oder entschluesselt werden soll
            $_SESSION['kastalia_input_name'] = $input_name; //gibt den Namen + Ort der Quelldatei an
            $_SESSION['kastalia_output_name'] = $output_name; //gibt den Namen + Ort der Zieldatei an
            $_SESSION['kastalia_part_number'] = $kastalia_part_number + 1; //gibt die aktuelle ver/entschluesselungsrunde an (+1)
            $_SESSION['kastalia_key'] = $key; //gibt den key zum ver/entschluesseln an

            //##################### <HTML HEADER MANIPULATION> #####################
            //Header Manipulation zum aufrufen der naechsten Entschluesselungsrunde
            echo "<head>\n";
            echo "<meta http-equiv=\"refresh\" content=\"" . $refresh_cycle . "; URL=encrypt_decrypt_files.php\">\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<p>\n";
            echo "decrypting part " . ($kastalia_part_number + 1) . " of " . $max_parts . "<br />";
            echo "please wait...<br />";
            echo "</p>\n";
            echo "</body>\n";
            //##################### </HTML HEADER MANIPULATION> #####################

        }
        else {
            //... falls ja, werden die letzten Schritte nach der Entschluesselung durchgefuert
            //mit dieser Funktion werden aus Sicherheitsgruenden nach der Entschluesselung
            //alle SESSION Variablen die damit zu tun haben geloescht
            UnsetSessionVars();
            //diese Variable gibt fuer das download.php Skript den Dateinamen zum herunterladen an
            $_SESSION['kastalia_temp_download'] = basename($output_name);

            //##################### <HTML HEADER MANIPULATION> #####################
            //Header Manipulation zum aufrufen des Downloadskriptes
            echo "<head>\n";
            echo "<meta http-equiv=\"refresh\" content=\"" . $refresh_cycle . "; URL=download.php\">\n";
            echo "</head>\n";
            echo "<body>\n";
            echo "<p>\n";
            echo "starting download<br />";
            echo "please wait...<br />";
            echo "</p>\n";
            echo "</body>\n";
            //##################### </HTML HEADER MANIPULATION> #####################

        }
        break;
    default:
        //falls der Modus weder "encrypt" noch "decrypt" ist, ist ein unerwarteter Fehler aufgetreten
        echo "Error: Unexpected value of \$kastalia_mode in encrypt_decrypt_files.php!";
        //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
        //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
        UnsetSessionVars();
        exit(1);
        break;
}

//diese Funktion verschluesselt die errechneten Dateiteile und fuegt sie in eine Datei an die richtige Stelle
function SplitAndEncryptFile($input_file_name, $output_name, $part_number, $max_parts, $key) {
    //hier wird der Name fuer die verschluesselte Datei mit der Endung ".kastaliaenc" versehen
    $output_file_name = $output_name . ".kastaliaenc";
    //hier wird ueberprueft ob die verschluesselte Enddatei schon existiert und es die erste Verschluesselungsrunde ist 
    //(damit die gerade zu verschluesselnde und erstellte Datei nicht mit in die Pruefung einbezogen wird) 
    //falls ja wird mit einem Error abgebrochen
    if($part_number == 0 && file_exists($output_file_name)) {
        echo "Error: File " . $output_file_name . " already exists! Stopping encryption!<br />";
        //wenn die verschluesselte Datei schon existiert, wird die temporaere unverschluesselte Datei
        //geloescht damit diese nicht auf dem Server unverschluesselt gespeichert bleibt
        echo "deleting temporary file...<br />\n";
        //hier wird nun die temporaere Datei geloescht, falls ein Fehler auftreten sollte, wird ein Error ausgegeben
        if(!unlink($input_file_name)) {
            echo "Error: Unable to delete temporary file!<br />\n";
            echo "This constitutes a <b>breach of security</b> because the content of the uploaded file stored unencrypted.<br />\n";
            echo "Please contact the administrator to have the temporary file " . $input_file_name . " deleted.\n";
        }
        else {
            echo "...done.\n";
        }
        //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
        //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
        UnsetSessionVars();
        exit(1);
    }
    //hier wird die zu verschluesselnde Datei fuer das Lesen geoeffnet
    if($input_file_handle = fopen($input_file_name, 'rb')) {
        $input_file_size = filesize($input_file_name);
        //hier wird die groesse (in Bytes) fuer die einzelnen zu verschluesselnden Datenteile berrechnet
        $parts_size = floor($input_file_size/$max_parts);
        //da durch die Groessenberechnung fuer die einzelnen Datenteile (durch die Nutzung von "floor")
        //ein Rest entstehen kann, wird dieser hier berrechnet um ihn an den letzten Datenteil anzuhaengen
        //damit kein Datenverlust entstehen kann
        $last_bytes = $input_file_size % $max_parts;
        //nun wird die Datei in die die verschluesselten Daten geschrieben werden sollen geoeffnet
        //(der Dateizeiger zeigt in diesem Modus auf das Ende der Datei)
        if($output_file_handle = fopen($output_file_name ,'ab')) {
            //hier wird der Dateizeiger an die richtige Position gesetzt um die Daten fuer die jetzige Verschluesselungsrunde zu lesen
            fseek($input_file_handle, $part_number * $parts_size, SEEK_SET);
            //hier wird ueberprueft, ob es sich um die letzte Verschluesselungsrunde handelt
            if($part_number == ($max_parts-1) ) {
                //die groesse des letzten Datenteils wird um den Rest ($last_bytes) der Groessenberechnung erhoeht
                fwrite($output_file_handle, EncryptData($key, fread($input_file_handle, $parts_size + $last_bytes)));
            }
            else {
                fwrite($output_file_handle, EncryptData($key, fread($input_file_handle, $parts_size)));
            }
            //die verschluesselte Zieldatei wird geschlossen
            fclose($output_file_handle);
        }
        else {
            echo "Error: Can't open file " . $output_file_name . "!";
            //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
            //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
            UnsetSessionVars();
            exit(1);
        }
        //die zu verschluesselnde Datei wird geschlossen
        fclose($input_file_handle);
    }
    else {
        echo "Error: Can't open file " . $input_file_name . "!";
        //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
        //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
        UnsetSessionVars();
        exit(1);
    }
    return true;
}

//diese Funktion entschluesselt die errechneten Dateiteile und fuegt sie in eine Datei an die richtige Stelle
function MergeAndDecryptFile($input_name, $output_file_name, $part_number, $max_parts, $key) {
    //die Konfigurationsdatei von Kastalia wird includiert
    //um alle Kastalia Einstellungen in dieser function nutzen zu koennen
    require(KASTALIA_BASE . '/config/conf.php');
    //hier wird ueberprueft ob die entschluesselte Enddatei schon existiert (dies kann passieren wenn 
    //jemand die Datei gerade entschluesselt um sie herunterzuladen) und es die erste Entschluesselungsrunde ist 
    //(damit die gerade zu entschluesselnde und erstellte Datei nicht mit in die Pruefung einbezogen wird) 
    //falls ja wird mit einem Error abgebrochen
    if(file_exists($output_file_name) && $part_number == 0) {
        echo "Error: File " . $output_file_name . " already exists! Stopping decryption!<br />\n";
        //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
        //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
        UnsetSessionVars();
        //hier wird die ctime von der schon existierenden Datei ermittelt um zu berechnen
        //wann Kastalia spaetestens diese Date iloeschen wird und es dem Nutzer mitzuteilen
        if($file_ctime = filectime($output_file_name)) {
            //hier wird berechnet wie lange die Datei noch laengstens bestehen wird bevor sie von Kastalia geloescht wird
            $file_ctime = $file_ctime + (60 * $conf['upload']['tempctime']) - time();
            //sollte die Zeitrechnung negativ werden, ist ein Fehler aufgetreten und eine andere Nachricht wird ausgegeben 
            if($file_ctime >= 0) {
                //die Angabe in Minuten wird auf 2 stellen hinter dem Komma gerundet
                echo "Kastalia will delete the file in about " . round($file_ctime / 60, 2) . " minutes at the latest. Then try again.\n";
            }
            else {
                //wird ausgegeben wenn die Zeitrechnung negativ wird
                //und somit die automatische Loeschung nicht funktioniert hat
                echo "Please contact the administrator to have the file deleted.";
            }
        }
        else {
            //wird ausgegeben wenn die ctime Ermittlung erfolglos war und dementsprechend die automatische
            //Loeschung auch nicht erfolgen wird
            echo "Please contact the administrator to have the file deleted.";
        }
        exit(1);
    }
    //hier wird der Name fuer die verschluesselte Datei mit der Endung ".kastaliaenc" versehen
    //da sie so auf dem Dateisystem gespeichert wurde
    $input_file_name = $input_name . ".kastaliaenc";
    //die Datei wird zum schreiben geoeffnet mit dem Dateizeiger auf das Ende der Datei
    if($input_file_handle = fopen($input_file_name, 'rb')) {
        $input_file_size = filesize($input_file_name);
        //hier wird die groesse (in Bytes) fuer die einzelnen zu entschluesselnden Datenteile berrechnet
        $parts_size = floor($input_file_size/$max_parts);
        //da durch die Groessenberechnung fuer die einzelnen Datenteile (durch die Nutzung von "floor")
        //ein Rest entstehen kann, wird dieser hier berrechnet um ihn an den letzten Datenteil anzuhaengen
        //damit kein Datenverlust entstehen kann
        $last_bytes = $input_file_size % $max_parts;
        //nun wird die Datei in die die entschluesselten Daten geschrieben werden sollen geoeffnet
        //(der Dateizeiger zeigt in diesem Modus auf das Ende der Datei)
        if($output_file_handle = fopen($output_file_name, 'ab')) {
            //hier wird der Dateizeiger an die richtige Position gesetzt um die Daten fuer die jetzige Entschluesselungsrunde zu lesen
            fseek($input_file_handle, $part_number * $parts_size, SEEK_SET);
            //hier wird ueberprueft, ob es sich um die letzte Entschluesselungsrunde handelt
            if($part_number == ($max_parts-1) ) {
                //die groesse des letzten Datenteils wird um den Rest ($last_bytes) der Groessenberechnung erhoeht
                fwrite($output_file_handle, DecryptData($key, fread($input_file_handle, $parts_size + $last_bytes)));
            }
            else {
                fwrite($output_file_handle, DecryptData($key, fread($input_file_handle, $parts_size)));
            }
            //der entschluesselte Zieldatei wird geschlossen
            fclose($output_file_handle);
        }
        else {
            echo "Error: Can't open file " . $output_file_name . "!";
            //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
            //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
            UnsetSessionVars();
            exit(1);
        }
        //die zu entschluesselnde Datei wird geschlossen
        fclose($input_file_handle);
    }
    else {
        echo "Error: Can't open file " . $input_file_name . "!";
        //mit dieser Funktion werden aus Sicherheitsgruenden beim Abbruch
        //alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben geloescht
        UnsetSessionVars();
        exit(1);
    }
    return true; 
}

//diese Funktion verschluesselt die uebergebenden Daten mit den uebergebenden key
//und gibt die verschluesselten Daten zurueck
function EncryptData($key, $plaintext) {
//erstellt den initialisierungsvektor in der groesse des ausgewaehlten algorithmuses
//aus der md5 summe des uebergebenden keys
$iv = substr(md5($key), 0, mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CFB));
//verschluesselt die Daten mit dem angegebenen Modus und Algorithmuses
return mcrypt_cfb(MCRYPT_BLOWFISH, $key, $plaintext, MCRYPT_ENCRYPT, $iv);
}

//diese Funktion entschluesselt die uebergebenden Daten mit den uebergebenden key
//und gibt die entschluesselten Daten zurueck
function DecryptData($key, $ciphertext) {
//erstellt den initialisierungsvektor in der groesse des ausgewaehlten algorithmuses
//aus der md5 summe des uebergebenden keys
$iv = substr(md5($key), 0, mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CFB));
//entschluesselt die Daten mit dem angegebenen Modus und Algorithmuses
return mcrypt_cfb(MCRYPT_BLOWFISH, $key, $ciphertext, MCRYPT_DECRYPT, $iv);
}

//diese Funktion loescht alle SESSION Variablen die mit der Ver/Entschluesselung zu tun haben
function UnsetSessionVars() {
    unset($_SESSION['kastalia_mode']);
    unset($_SESSION['kastalia_input_name']);
    unset($_SESSION['kastalia_output_name']);
    unset($_SESSION['kastalia_part_number']);
    unset($_SESSION['kastalia_key']);
}
?>

<?php
//der footer wird includiert
require KASTALIA_TEMPLATES . '/common-footer.inc';
?>