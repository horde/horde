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

//die Konfigurationsdatei von Kastalia wird includiert
//um alle Kastalia Einstellungen in diesem Skript nutzen zu koennen
require(KASTALIA_BASE . '/config/conf.php');

//bei dem download.php Skript macht ein includieren von base.php Probleme
//und kann zu Fehlerhaften Downloads fuehren, deshalb wird ein manueller Anmeldecheck durchgefuehrt
//################### <MANUELLER ANMELDE CHECK (WENN base.php NICHT INCLUDIERT WIRD)> ###################
// Check for a prior definition of HORDE_BASE (perhaps by an auto_prepend_file
// definition for site customization).
if (!defined('HORDE_BASE')) {
    @define('HORDE_BASE', dirname(__FILE__) . '/..');
}

// Load the Horde Framework core, and set up inclusion paths.
require_once HORDE_BASE . '/lib/core.php';

// Registry.
$registry = new Horde_Registry();
try {
    $registry->pushApp('kastalia', array('logintasks' => true));
} catch (Horde_Exception $e) {
    $registry->authenticateFailure('kastalia', $e);
}
//################### </MANUELLER ANMELDE CHECK (WENN base.php NICHT INCLUDIERT WIRD)> ###################

//als erstes wird ueberprueft, ob die zu herunterladende Datei durch
//eine dafuer vorgesehenen SESSION Variable definiert wurde
//(diese SESSION Variable ist fuer downloads aus dem temporaeren Verzeichnis vorgesehen)
if(isset($_SESSION['kastalia_temp_download'])) {
    $kastalia_filename = $_SESSION['kastalia_temp_download'];
    //nachdem die SESSION Variable in eine fuer das Skript lokalen Variable gespeichert worden ist
    //wird die SESSION Variable aus Sicherheits- und Konfliktgruenden geloescht
    unset($_SESSION['kastalia_temp_download']);
    //hier wird ueberprueft, ob es sich bei der zu herunterladenden Datei um eine ".htaccess" Datei
    //handelt. Dabei werden allerdings auch Dateien herausgefiltert, die die Endung ".htaccess" haben.
    if(substr($kastalia_filename, -9) == ".htaccess") {
        echo "Error: Downloading .htaccess files out of the temporary directory not allowed!";
        exit(1);
    }
    //hier wird ueberprueft, ob das verschluesselte speichern von Dateien aktiviert ist...
    if($conf['upload']['securestore']) {
        //... falls ja, werden weitere Pruefungen durchgefuerht
        //Ueberpruefung nach den Zeichenfolgen "/." und "./" damit
        //durch das Manipulieren der Variable kastalia_filename
        //keiner aus dem temporaeren Verzeichnis entkommen kann (durch Nutzung von "../")
        if(strpos($kastalia_filename,'/.') === false && strpos($kastalia_filename,'./') === false) {
            //nun wird der Download aus dem temporaeren Verzeichnis gestartet
            DownloadFile($conf['upload']['tempdir'], $kastalia_filename);
            //nachdem die Datei heruntergeladen wurde, muss sie geloescht werden,
            //damit keiner mehr Zugriff auf sie erhaelt
            if(!unlink($conf['upload']['tempdir'] . "/" . $kastalia_filename)) {
                echo "Error: Unable to delete temporary file after downloading!";
                exit(1);
            }
        }
        else {
            echo "Error: \$kastalia_filename in download.php contains illegal characters!";
            exit(1);
        }
    }
    else {
        //... ansonsten wird mit einer Meldung abgebrochen
        echo "Encryption/Decryption is disabled!";
        exit(0);
    }
}
//falls die SESSION Variable nicht gesetzt wurde...
else {
    //...wird hier ueberprueft, ob mittels GET der Dateiname gesetzt worden ist
    if(isset($_GET['kastalia_filename'])) {
        //hier wird ueber GET der Dateiname ermittelt
        $kastalia_filename = $_GET['kastalia_filename'];
        //hier wird ueberprueft, ob es sich bei der zu herunterladenden Datei um eine ".htaccess" Datei
        //handelt. Dabei werden allerdings auch Dateien herausgefiltert, die die Endung ".htaccess" haben.
        if(substr($kastalia_filename, -9) == ".htaccess") {
            echo "Error: Downloading .htaccess files out of the datastore not allowed!";
            exit(1);
        }
        //Ueberpruefung nach den Zeichenfolgen "/." und "./" damit
        //durch das Manipulieren der Variable kastalia_filename
        //keiner aus dem kastalia Datastore entkommen kann (durch Nutzung von "../")
        if(strpos($kastalia_filename,'/.') === false && strpos($kastalia_filename,'./') === false) {
            //hier wird ueberprueft ob es sich bei dem Download um eine von Kastalia verschluesselte Datei handelt (Endung ".kastaliaenc")
            if(substr($kastalia_filename, -12) == ".kastaliaenc") { //SECURE STORED
                //hier wird serverseitig ueberprueft, ob ein leeres Passwort uebergeben wurde
                //falls ja, wird mit einem Error abgebrochen, da leere Passwoerter verboten sind
                if($_POST['kastalia_password'] == "") {
                    echo "Error: Empty passwords not allowed!";
                    exit(1);
                }
                //hier wird ueberprueft, ob das verschluesselte speichern von Dateien aktiviert ist
                if($conf['upload']['securestore']) {
                    $_SESSION['kastalia_mode'] = "decrypt"; //diese Variable gibt den Modus an in welchem das Skript encrypt_decrypt_files.php ausgefuehrt werden soll
                    $_SESSION['kastalia_input_name'] = substr($conf['datastore']['location'] . "/" . $kastalia_filename, 0, -12); //diese Variable gibt die zu entschluesselnde Datei an (ohne die Endung ".kastaliaenc")
                    $_SESSION['kastalia_output_name'] = substr($conf['upload']['tempdir'] . "/" . basename($kastalia_filename), 0, -12); //diese Variable gibt den Ort fuer die entschluesselte Zieldatei an (der temporaere Ordner von Kastalia, das "basename" ist hier wichtig, da ansonsten auch die unterordner vom datastore im Dateinamen stecken)
                    $_SESSION['kastalia_part_number'] = 0; //diese Variable gibt die aktuelle Entschluesselungsrunde an (die Beginnrunde ist immer 0)
                    $_SESSION['kastalia_key'] = $_POST['kastalia_password']; //diese Variable gibt den Schluessel zum entschluesseln der Datei an
                    //mit der encrypt_decrypt_files.php wird die Entschluesselung fuer die Datei ausgefuehrt
                    //(diese besteht aus mehreren Teilschritten wobei der Browser automatisch das Skript in Intervallen neu aufruft)
                    include('encrypt_decrypt_files.php');
                }
                else {
                    //falls es deaktiviert ist, wird mit einer Meldung abgebrochen
                    echo "Encryption/Decryption is disabled!";
                    exit(0);
                }
            }
            //falls die zu herunterladende Datei keine von Kastalia verschluesselte Datei ist
            //wird der Download einfach gestartet
            else { //UNSECURE STORED
                DownloadFile($conf['datastore']['location'], $kastalia_filename);
            }
        }
        else {
            echo "Error: \$kastalia_filename in download.php contains illegal characters!";
            exit(1);
        }
    }
    //falls der Dateiname nicht gesetzt wurde, wird mit einer Fehlermeldung abgebrochen
    else{
        echo "Error: \$kastalia_filename in download.php is not set!"; 
        exit(1);
    }
}

//diese Funktion setzt die Header zum Download der Datei
//und gibt die Datei an den Browser
function DownloadFile($file_location,$file_name) {
    //Ueberpruefung ob Datei existiert und lesbar ist
    if(is_readable($file_location . '/' . $file_name)) {
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($file_location . '/' . $file_name));
        header('Content-Disposition: attachment; filename=' . basename($file_name));
        flush();
        readfile($file_location . '/' . $file_name);
    }
    else {
        echo "Error: File $file_name doesn't exist or is not readable!";
        exit(1);
    }
}
?>
