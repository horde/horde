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

echo "<div class=\"header\">Kastalia Datastore - Upload</div>\n";
echo "<br />\n";

//nun wird ueberprueft, ob das hochladen von Dateien ueberhaupt erlaubt ist
//falls nicht, wird eine Meldung ausgegeben und das Skript beendet
if(!$conf['upload']['uploadenabled']) {
    echo "File uploads are disabled!";
    exit(0);
}

//hier wird ueberprueft, ob der Zielordner zum hochladen gesetzt ist
if(isset($_POST['kastalia_targetlocation'])) {
    //hier wird mittels POST der uploadzielordner ermittelt
    $kastalia_targetlocation = $_POST['kastalia_targetlocation'];
    //Ueberpruefung nach den Zeichenfolgen "/." und "./" damit
    //durch das Manipulieren der Variable kastalia_targetlocation
    //keiner aus dem kastalia Datastore entkommen kann (durch Nutzung von "../")
    if(strpos($kastalia_targetlocation,'/.') === false && strpos($kastalia_targetlocation,'./') === false) {
        //ueberpruefung ob die Datei eine ".htaccess" ist, falls ja wird zur Sicherheit abgebrochen, damit niemand diese
        //ueberschreiben kann oder damit regeln fuer den webserver festlegen kann 
        if(Kastalia::ReplaceSpecialChars($_FILES['userfile']['name']) != ".htaccess") {
            //hier wird serverseitig ueberprueft, ob die hochgeladene Datei die Endung ".kastaliaenc" besitzt
            //falls ja wird mit einem Fehler abgebrochen, da diese intern von Kastalia verwendet wird
            //um von Kastalia verschluesselte Dateien ausfindig zu machen
            if(substr(Kastalia::ReplaceSpecialChars($_FILES['userfile']['name']), -12) == ".kastaliaenc") {
                echo "Error: File extension \".kastaliaenc\" not allowed!";
                exit(1);
            }
            //hier wird der absolute Pfad mit Dateiname (Sonderzeichen werden entfernt) zusammengesetzt
            //wohin die Datei hochgeladen werden soll
            $target_directory = $conf['datastore']['location'] . $kastalia_targetlocation;
            $target_location = $target_directory . "/" . Kastalia::ReplaceSpecialChars($_FILES['userfile']['name']);
            //diese Funktion fuehrt den Upload aus
            UploadFile($target_location);
        }
        else {
            echo "Error: .htaccess files not allowed for upload!";
            exit(1);
        }
    }
    else {
        echo "Error: \$kastalia_targetlocation in upload.php contains illegal characters!";
        exit(1);
    }
}
//falls der Dateiname nicht gesetzt wurde, wird mit einer Fehlermeldung abgebrochen
else {
    echo "Error: \$kastalia_targetlocation in upload.php is not set!"; 
    exit(1);
}

//diese Funktion ueberprueft Fehler die beim upload vorkommen koennen
//und fuehrt den upload durch
function UploadFile($upload_target) {
    //die Konfigurationsdatei von Kastalia wird includiert
    require(KASTALIA_BASE . '/config/conf.php');
    if(!empty($_FILES)) {
        switch ($_FILES['userfile']['error']) {
            case 0: //UPLOAD_ERR_OK
                //hier wird die in der Config eingestellte maximale Dateigroesse mit
                //der Dateigroesse der hochgeladenen Datei abgeglichen
                if($_FILES['userfile']['size'] <= $conf['upload']['maxfilesize']) {
                    //Sicherheitsueberpruefung ob Datei mittels HTTP POST hochgeladen wurde
                    //und nicht eine andere Datei weiterbearbeitet wird
                    if(is_uploaded_file($_FILES['userfile']['tmp_name'])) {
                        //hier wird ueberprueft ob der Upload verschluesselt gespeichert werden soll
                        if(isset($_POST['kastalia_secure_store']) && $_POST['kastalia_secure_store'] == true) { //SECURE STORE
                            //hier wird ueberprueft, ob das verschluesselte Speichern von Dateien ueberhaupt aktiviert ist
                            //falls nicht, wird die Weiterverarbeitung abgebrochen und eine Meldung ausgegeben
                            if(!$conf['upload']['securestore']) {
                                echo "Encryption/Decryption is disabled!";
                                exit(1);
                            } 
                            //sollte die Datei mit der Endung ".kastaliaenc" schon existieren, wird ein "_" an sie drangehaengt,
                            //damit die existierende Datei nicht ueberschrieben wird
                            while(file_exists($upload_target . ".kastaliaenc")) {
                                $upload_target = $upload_target . "_";
                            }
                            //hier wird serverseitig ueberprueft ob das Passwort fuer die Verschluesselung leer ist
                            //falls ja, wird mit einem Fehler abgebrochen, da dies verboten ist (und die temporaere Datei von
                            //PHP automatisch geloescht) 
                            if($_POST['kastalia_password'] == "") {
                                echo "Error: Empty passwords not allowed!";
                                exit(1);
                            }
                            //die hochgeladene Datei wird nun ersteinmal in das temporaere Verzeichnis von Kastalia verschoben
                            //und mit der Endung "_kastalia" versehen
                            if(move_uploaded_file($_FILES['userfile']['tmp_name'], $conf['upload']['tempdir'] . "/" . basename($_FILES['userfile']['tmp_name']) . "_kastalia")) {
                                //die SESSION Variablen fuer die Verschluesselung werden gesetzt
                                $_SESSION['kastalia_mode'] = "encrypt"; //diese Variable gibt den Modus an in welchem das Skript encrypt_decrypt_files.php ausgefuehrt werden soll
                                $_SESSION['kastalia_input_name'] = $conf['upload']['tempdir'] . "/" . basename($_FILES['userfile']['tmp_name']) . "_kastalia"; //diese Variable gibt die zu verschluesselnde Datei an
                                $_SESSION['kastalia_output_name'] = $upload_target; //diese Variable gibt das Ziel fuer die verschluesselte Datei an
                                $_SESSION['kastalia_part_number'] = 0; //diese Variable gibt die aktuelle Verschluesselungsrunde an (die Beginnrunde ist immer 0)
                                $_SESSION['kastalia_key'] = $_POST['kastalia_password']; //diese Variable beinhaltet das Passwort, welches fuer die Verschluesselung benutzt wird
                                //mit der encrypt_decrypt_files.php wird die Verschluesselung fuer die Datei ausgefuehrt
                                //(diese besteht aus mehreren Teilschritten wobei der Browser automatisch das Skript in Intervallen neu aufruft)
                                include('encrypt_decrypt_files.php');
                            } 
                            else {
                                echo "Error: File couldn't be moved to temporary directory!";
                                //Debuging
                                //print_r($_FILES);
                                exit(1);
                            }
                        }
                        else { //UNSECURE STORE
                            //sollte die Datei schon existieren, wird ein "_" an sie drangehaengt,
                            //damit die existierende Datei nicht ueberschrieben wird
                            while(file_exists($upload_target)) {
                                $upload_target = $upload_target . "_";
                            }
                            //die hochgeladene Datei wird nun an die dafuer vorgesehene stelle kopiert
                            if(move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_target)) {
                                echo "File successfully stored under <b>" . substr($upload_target, strlen($conf['datastore']['location'] . "/")) . "</b> !\n";
                            }
                            else {
                                echo "Error: File couldn't be moved!";
                                //Debuging
                                //print_r($_FILES);
                                exit(1);
                            }
                        }
                    }
                    else {
                        echo "Error: Used file is not uploaded via HTTP POST!";
                        exit(1);
                    }

                }
                else {
                    echo "Error: The uploaded file exceeds the configured file size!";
                    exit(1);
                }
                break;
            case 1: //UPLOAD_ERR_INI_SIZE
                echo "Error: The uploaded file exceeds the upload_max_filesize directive in php.ini!";
                exit(1);
                break;
            case '2': //UPLOAD_ERR_FORM_SIZE
                echo "Error: The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form!";
                exit(1);
                break;
            case 3: //UPLOAD_ERR_PARTIAL
                echo "Error: The file was only partially uploaded!";
                exit(1);
                break;
            case 4: //UPLOAD_ERR_NO_FILE
                echo "Error: No file was uploaded!";
                exit(1);
                break;
            case 6: //UPLOAD_ERR_NO_TMP_DIR
                echo "Error: Missing a temporary folder!";
                exit(1);
                break;
            case 7: //UPLOAD_ERR_CANT_WRITE
                echo "Error: Failed to write file to disk!";
                exit(1);
                break;
            case 8: //UPLOAD_ERR_EXTENSION
                echo "Error: File upload stopped by extension!";
                exit(1);
                break;
            default:
                echo "Error: Unexpected value of \$_FILES['file']['error'] in upload.php!";
                exit(1);
                break;
        }
    }
    else {
        echo "Error: \$_FILES is empty!<br /><br />";
        echo "Possible reasons:<br />";
        echo "-upload_max_filesize, post_max_size or memory_limit value is too low in php.ini.<br />";
        echo "-file_uploads is set to Off in php.ini.";
        exit(1);
    }
}
?>

<?php
//der footer wird includiert
require KASTALIA_TEMPLATES . '/common-footer.inc';
?>