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

//nun wird ueberprueft, ob das hochladen von Dateien ueberhaupt erlaubt ist
//falls nicht, wird eine Meldung ausgegeben und das Skript beendet
if (!$conf['upload']['uploadenabled']) {
    echo "<br />";
    echo "File uploads are disabled!";
    exit(0);
}
?>

<!-- hier werden die JavaScripte zum erzeugen des sha-1 hashes includiert //-->
<script src="js/sha1.js" type="text/javascript" language="JavaScript"></script>

<script type="text/javascript" language="JavaScript">
<!--
//diese function ueberprueft die Werte des Formulars
function FormularCheck() {
   //hier wird ueberprueft ob eine Datei ausgewaehlt wurde
   if(document.forms['upload'].elements['userfile'].value != "") {
      //nun wird die Dateiendung ".kastaliaenc" extrahiert um zu pruefen, ob die Datei sie besitzt
      var file_extension = document.forms['upload'].elements['userfile'].value;
      file_extension = file_extension.substring(file_extension.length-12,file_extension.length);
      file_extension = file_extension.toLowerCase();
      //hier wird browserseitig ueberprueft, ob die Dateiendung ".kastaliaenc" nicht hochgeladen werden soll
      if(file_extension == '.kastaliaenc') {
         alert('Error: File extension \".kastaliaenc\" not allowed!');
         return false;
      }
      //hier wird ueberprueft ob ein Zielordner fuer den upload ausgewaehlt wurde
      if(document.forms['upload'].elements['kastalia_targetlocation'].selectedIndex != -1) {
         <?php
            //hier wird nun mit PHP ueberprueft, ob die Dateienverschluesselung aktiviert und das temporaere
            //Verzeichnis schreibbar ist, da ansonsten das Element welches im JavaScript abgefragt wird nicht
            //existiert und das JavaScript einen Fehler verursacht
            if($conf['upload']['securestore'] && is_writable($conf['upload']['tempdir'])) {
               //hier wird nun browserseitig ueberprueft, ob das verschluesselte speichern der Daten aktiviert wurde
               echo "if(document.forms['upload'].elements['kastalia_secure_store'].checked == true) {\n";
               //hier wird nun ueberprueft, ob das Passwort leer ist, falls ja, wird ein Error
               //zurueck gegeben und abgebrochen
               echo "if(document.forms['upload'].elements['kastalia_password'].value == '') {\n";
                  echo "alert('Error: Empty passwords not allowed!');\n";
                  echo "return false;\n";
               echo "}\n";
               echo "else {\n";
                  //falls das Passwort gueltig ist und das verschluesselte speichern der Daten aktiviert wurde
                  //wird das eingegebene Passwort vor dem abschicken durch einen sha-1 hash des Passwortes ersetzt
                  //damit das Passwort nie im Klartext uebertragen wird, sondern nur der sha-1 hash zum ver/entschluesseln benutzt wird
                  echo "document.forms['upload'].elements['kastalia_password'].value = hex_sha1(document.forms['upload'].elements['kastalia_password'].value);\n";
                  echo "}\n";
               echo "}\n";
            }
         ?>
         //falls das verschluesselte speichern von Daten nicht aktiviert wurde
         //oder die Ueberpruefungen des Passworts schon stattfanden
         //wird true zurueck gegeben und der Loader sichtbar gemacht und das Menu unsichtbar
         document.getElementById("loader").style.display='block';
         document.getElementById("upload_menu").style.display='none';
         return true;
      }
      else {
         alert('Error: No upload directory chosen!');
         return false;
      }
   }
   else {
      alert('Error: No file chosen!');
      return false;
   }
}

//diese Funktion zeigt im Browser die Passworteingabe an
//sobald die checkbox zum verschluesselten speichern aktiviert wurde
function ShowPasswordInput(input_id) {
    //hier wird ueberprueft, ob die checkbox aktiviert ist...
    if(document.forms['upload'].elements['kastalia_secure_store'].checked == true) {
        //...falls ja, wird die Passworteingabe sichtbar
        document.getElementById(input_id).style.display='block';
    }
    else {
       //...falls nein, wird die Passworteingabe unsichtbar
       document.getElementById(input_id).style.display='none';
    }
}

//diese Funktion kopiert das von Kastalia generierte Passwort in das Passworteingabefeld
function CopySuggestedPassword() {
    document.forms['upload'].elements['kastalia_password'].value = document.forms['upload'].elements['kastalia_generated_password'].value;
}
//-->
</script>

<!--##################### <Informations Header> #####################//-->
<p><b>notice:</b> cookies and javascripts must be enabled to upload files! | maximal upload size:

<?php
//Nutzerfreundlichere Ausgabe der maximalen upload file size auf zwei stellen hinter dem Komma gerundet
$tempfilesize = $conf['upload']['maxfilesize'] / 1024 / 1024;
if ($tempfilesize < 1) {
    echo round($conf['upload']['maxfilesize'] / 1024, 2) . "kB";
}
else {
    echo round($tempfilesize, 2) . "MB";
}
?>

</p>
<!--##################### </Informations Header> #####################//-->

<!--##################### <Loader> #####################//-->
<div id="loader" style="display:none;">
<p>please wait...</p>
<img border="0" src="themes/graphics/loader.gif" />
</div>
<!--##################### </Loader> #####################//-->

<!--##################### <Form> #####################//-->
<div id="upload_menu" style="display:block;">
<form name="upload" enctype="multipart/form-data" method="post" action="upload.php" onsubmit="return FormularCheck()">
<u>choose file:</u><input type="hidden" name="max_file_size" value="<?php echo $conf['upload']['maxfilesize']?>" />
<input name="userfile" type="file" />
<input type="submit" value="send" />
<br />

<?php
//hier wird ueberprueft, ob die Option die Daten verschluesselt speichern zu lassen aktiviert ist
//um die Eingabefelder fuer die Verschluesselung anzeigen zu lassen oder nicht
if($conf['upload']['securestore']) {
    //hier wird ueberprueft ob das temporaere Verzeichnis auch beschreibbar ist
    //denn ansonsten wuerde ein verschluesseln der Daten nicht funktionieren
    if(is_writable($conf['upload']['tempdir'])) {
        //wenn das temporaere Verzeichnis beschreibbar ist, werden die EIngabefelder fuer die Verschluesselung ausgegeben
        echo "<br /><u>encrypt file?:</u>\n";
        echo "<input type=\"checkbox\" name=\"kastalia_secure_store\" onclick=\"ShowPasswordInput('password_input')\" />\n";
        //dieser Teil wird nur angezeigt, wenn die checkbox zum 
        //verschluesselten speichern der Daten aktiviert ist
        echo "<div id=\"password_input\" style=\"display:none\">\n";
        echo "<br /><u>password:</u>\n";
        echo "<input name=\"kastalia_password\" type=\"password\" />\n";
        echo "<input type=\"button\" value=\"insert suggested password\" onclick=\"CopySuggestedPassword()\" />\n";
        echo "<br /><u>suggested password:</u>\n";
        echo "<input name=\"kastalia_generated_password\" type=\"text\" value=\"" . CreateRandomPassword(10) . "\" disabled=\"disabled\" />\n";
        echo "</div>\n";
    }
    //falls das temporaere Verzeichnis nicht beschreibbar ist, wird eine Warnmeldung ausgegeben
    //und die Option Daten verschluesselt zu speichern wird ausgeblendet
    else {
        echo "<br />Warning: The option to store files encrypted is enabled by the administrator but still deactivated by Kastalia itself!<br />\n";
        echo "Check if the temporary directory exists and is writable.\n";
    }
}
?>

<br />
<br />
<p><u>choose folder:</u></p>
<select name="kastalia_targetlocation" size="20" class="kastalia_upload">

<?php
//diese if-Verzweigung ueberprueft das datastore selber
//nach schreibrechten (Sonderfall der in der Scanfunction nicht geprueft werden kann) 
if(is_dir($conf['datastore']['location']) && is_writable($conf['datastore']['location'])) {
    echo "<option>/</option>";
}
//diese function scannt den Inhalt vom datastore
//nach Ordnern mit schreibrechten und gibt diese dann aus
ScanDirectory($conf['datastore']['location']);
?>

</select>
</form>
</div>
<!--##################### </Form> #####################//-->

<?php
//diese function liest das datastore rekursiv aus
//und gibt alle Ordner mit schreibzugriff in einer Auswahlliste aus
function ScanDirectory($dir_name) {
   //die Konfigurationsdatei von Kastalia wird includiert
   //damit die Konfigurationen innerhalb dieser Funktion auch benutzt werden koennen
   require(KASTALIA_BASE . '/config/conf.php');
   if($dir_handle = opendir($dir_name)) {
      //hier wird unser Array in dem die Ordnernamen zwischengespeichert werden deklariert
      $directory_array = array();
      //das Verzeichnis wird durchlaufen und der Inhalt untersucht
      while(false !== ($file = readdir($dir_handle))) {
         //Ueberpruefung ob es sich bei dem Element um einen Ordner handelt
         //und dieser nicht in der Liste der nicht aufzulistenden Ordner steht...
         if($file != "." && $file != ".." && is_dir($dir_name . '/' . $file) && !in_array($file, $conf['datastore']['directoryexcludes'])) {
            //...falls ja, wird dieser zu unserem Array hinzugefuegt
            $directory_array[] = $dir_name . '/' . $file;
         }
      }
      //wenn das Verzeichnis ausgelesen wurde, wird unser Array sortiert
      asort($directory_array);
      //nun wird jedes einzelne Element unseres Arrays durchlaufen...
      foreach($directory_array as $directory_element) {
         //...und auf Schreibrechte ueberprueft, sind welche vorhanden
         //wird der Ordner als Uploadordner ausgegeben
         if(is_writable($directory_element)) {
            echo "<option>";
            echo substr($directory_element, strlen($conf['datastore']['location']));
            echo "</option>";
         }
         //die Funktion wird noch einmal aufgerufen mit dem aktuellen Ordner um zu ueberpruefen
         //ob Unterordner Schreibrechte enthalten
         ScanDirectory($directory_element);
      }
      closedir($dir_handle);
   }
   else {
      echo "Error: Can't open directory \"$dir_name\"!";
      exit(1);
   }
}

//diese Funktion generiert ein zufaelliges Passwort mit der uebergebenden laenge
function CreateRandomPassword($passwordlength) {
    //diese Zeichen koennen alle in dem Passwort vorkommen (beliebig veraenderbar)
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-#*!$%=";
    $password = '';
    for($i=0;$i<$passwordlength;$i++) {
        $randomnumber = mt_rand() % strlen($chars);
        $password = $password . substr($chars, $randomnumber, 1);
    }
    return $password;
}
?>

<?php
//der footer wird includiert
require KASTALIA_TEMPLATES . '/common-footer.inc';
?>