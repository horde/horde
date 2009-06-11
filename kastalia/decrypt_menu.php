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

echo "<div class=\"header\">Kastalia Datastore - Decryption Password</div>\n";
echo "<p><b>notice:</b> cookies and javascripts must be enabled to download files!</p>\n";

//nun wird ueberprueft, ob das verschluesseln/entschluesseln von Dateien aktiviert wurde
//falls nicht, wird eine Meldung ausgegeben und das Skript beendet
if(!$conf['upload']['securestore']) {
    echo "Encryption/Decryption is disabled!";
    exit(0);
}

//hier wird ueberprueft, ob die zu herunterladende Datei ueberhaupt
//uebergeben wurde, damit Manipulation verhindert wird und um Fehler zu vermeiden
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
        //hier wird ueberprueft, ob die zu entschluesselnde Datei existiert oder lesbar ist
        if(!is_readable($conf['datastore']['location'] . "/" . $kastalia_filename)) {
            echo "Error: File $kastalia_filename doesn't exist or is not readable!";
            exit(1);
        }
        //hier wird ueberprueft ob es sich bei dem Download um eine von Kastalia verschluesselte Datei handelt (Endung ".kastaliaenc")
        //falls es sich nicht um die von Kastalia verwendete Endung handelt, wird ein Fehler ausgegeben
        if(substr($kastalia_filename, -12) != ".kastaliaenc") {
           echo "Error: File doesn't have the Kastalia file extension!";
           exit(1);
        }
    }
    else {
        echo "Error: \$kastalia_filename in decrypt_menu.php contains illegal characters!";
        exit(1);
    }
}
else {
    echo "Error: \$kastalia_filename in decrypt_menu.php is not set!"; 
    exit(1);
}
?>

<!-- hier werden die JavaScripte zum erzeugen des sha-1 hashes includiert //-->
<script src="js/sha1.js" type="text/javascript" language="JavaScript"></script>

<script type="text/javascript" language="JavaScript">
<!--
//diese function ueberprueft die Werte des Formulars
function FormularCheck() {
    //hier wird ueberprueft ob das Passworteingabefeld leer nicht ist
    if(document.forms['decryptpassword'].elements['kastalia_password'].value != "") {
        //das eingegebene Passwort wird vor dem abschicken durch den sha-1 hash vom Passwort ersetzt
        //damit das Passwort nie im Klartext uebertragen wird, sondern nur der sha-1 hash zum ver/entschluesseln benutzt wird
        document.forms['decryptpassword'].elements['kastalia_password'].value = hex_sha1(document.forms['decryptpassword'].elements['kastalia_password'].value);
        return true;
    }
    else {
        alert('Error: Empty passwords not allowed!');
        return false;
    }
}
//-->
</script>

<form name="decryptpassword" method="post" action="download.php?kastalia_filename=<?php echo $kastalia_filename;?>" onsubmit="return FormularCheck()">
<input name="kastalia_password" type="password" />
<input type="submit" value="send" />
</form>

<?php
//der footer wird includiert
require KASTALIA_TEMPLATES . '/common-footer.inc';
?>