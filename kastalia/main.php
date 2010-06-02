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




echo $GLOBALS['registry']->getAuth();

echo $registry->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT);
if($registry->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
   echo "TASTA";
   exit(0);
}

?>

<script type="text/javascript" language="JavaScript">
<!--
//diese function ist fuer das auf und zuklappen der Dateiauswahl zustaendig
function OpenCloseMenu(div_name,img_name){
    //falls der Ordner zugeklappt (Auswahlliste ausgeblendet) ist, wird dieser aufgeklappt (Auswahlliste eingeblendet)
    //ansonsten ist er aufgeklappt (Auswahlliste eingeblendet) und wird zugeklappt (Auswahlliste ausgeblendet)
    if (document.getElementById(div_name).style.display=='none') {
        document.getElementById(div_name).style.display='block'; 
        document.getElementById(img_name).src='themes/graphics/directory_open.gif';
    }
    else {
        document.getElementById(div_name).style.display='none';
        document.getElementById(img_name).src='themes/graphics/directory_closed.gif';
    }
}
//-->>
</script>

<div class="header">Kastalia Datastore - Download</div>
<p><b>notice:</b> cookies and javascripts must be enabled to download files!</p>

<?php
//die function die den temporaeren Ordner ausliest und alle temporaeren Dateien
//loescht die zu lange existieren wird aufgerufen. Dies geschieht, da durch 
//Benutzer seitige Fehler (z.B. schliessen des Browsers beim verschluesseln der Datei)
//unverschluesselte temporaere Dateien gespeichert bleiben
CleanDirectory($conf['upload']['tempdir']);

//die function die den Ordner ausliest und ausgibt wird aufgerufen
ScanDirectory($conf['datastore']['location']);

//diese function liest das Datastore rekursiv aus
//und gibt den Inhalt aus
function ScanDirectory($dir_name) {
   //die Konfigurationsdatei von Kastalia wird includiert
   //damit die Konfigurationen auch innerhalb dieser Funktion benutzt werden koennen
   require(KASTALIA_BASE . '/config/conf.php');
   if ($dir_handle = opendir($dir_name)) {
      echo "<ul class=\"kastalia_root\">\n";
      //diese beiden Arrays werden dazu benoetigt, die Ordner- bzw
      //Dateinamen zwischen zu speichern und zu sortieren
      $directory_array = array();
      $file_array = array();
      //der Inhalt des Ordners wird in dieser Schleife durchgegangen und jedes Element bearbeitet
      //falls es sich um einen Ordner handelt, wird dieser wieder rekursiv geoeffnet und die Elemente bearbeitet
      while(false !== ($file = readdir($dir_handle))) {
         //Ueberpruefung ob es sich bei dem Element um einen Ordner handelt
         //und ob dieser nicht in der Liste der Ordner ist, die nicht angezeigt werden sollen
         //falls ja, wird der Name in einem Array gespeichert
         if($file != "." && $file != ".." && is_dir($dir_name . '/' . $file) && !in_array($file, $conf['datastore']['directoryexcludes'])) { //SAVE DIRECTORYNAMES
            $directory_array[] = $file;
         }
         //Ueberpruefung ob es sich bei dem Element um eine Datei handelt (und keine .htaccess Datei)
         //falls ja, wird dieses Element in einem Array gespeichert
         if($file != "." && $file != ".." && $file != ".htaccess" && is_file($dir_name . '/' . $file)) { //SAVE FILENAMES
            $file_array[] = $file;
         }
      }
      //die Arrays mit den Ordnernamen und den Dateinamen werden sortiert
      asort($directory_array);
      asort($file_array);
      //in dieser Schleife werden die Ordnernamen aus dem sortierten Array
      //ausgegeben und deren Inhalt jeweils noch einmal
      //mit Aufruf dieser Funktion ausgelesen
      foreach($directory_array as $directory_element) { //PRINT DIRECTORY
         //hier wird der eindeutige Elementname fuer das JavaScript Menu festgelegt 
         //(der Pfad zu dem Ordner mit entfernten Sonderzeichen wie z.B. '/', da dieser einmalig)
         $tempscriptname = Kastalia::ReplaceSpecialChars(substr($dir_name . '/' . $directory_element, strlen($conf['datastore']['location'] . '/')));
         echo "<li class=\"kastalia_directory\">\n";
         echo "<a class=\"kastalia_directory\" href=\"javascript:OpenCloseMenu('". $tempscriptname . "_div','" . $tempscriptname . "_img');\">\n";
         echo "<img id=\"" . $tempscriptname . "_img\" src=\"themes/graphics/directory_closed.gif\" class=\"kastalia_picture\" alt=\"directory image\" />\n";
         echo $directory_element;
         echo "\n</a>\n";
         echo "</li>\n";
         echo "<li style=\"list-style: none; display: inline\">\n";
         echo "<div id=\"". $tempscriptname . "_div\" class=\"kastalia_filelist\" style=\"display: none;\">\n";
         //der Ordner wird nun mit einem erneuten aufrufen der Funktion ausgelesen und der Inhalt bearbeitet
         ScanDirectory($dir_name . '/' . $directory_element);
         echo "</div>\n";
         echo "</li>\n";
      }
      //in dieser Schleife werden die dateinamen aus dem sortierten Array
      //ausgegeben und jeweils nochmal ueberprueft
      //ob diese Dateien mit Kastalia verschluesselt wurden oder nicht
      foreach($file_array as $file_element) {
         //hier wird ueberprueft, ob die Datei von Kastalia verschluesselt wurde (die Endung ".kastaliaenc" wird dafuer benutzt)
         if(substr($file_element, -12) == ".kastaliaenc") { //PRINT ENCRYPTED FILE DOWNLOAD
            echo "<li class=\"kastalia_file_enc\">\n";
            //hier wird der Link fuer das Entschluesselungs Menu fuer diese Datei zusammengesetzt
            //aus dem Link werden vorher alle Zeichen die in der URI nicht stehen duerfen ersetzt (bsp. " " mit "%20")
            echo "<a href=\"decrypt_menu.php?kastalia_filename=" . Kastalia::ConvertToUriString(substr($dir_name . '/' . $file_element, strlen($conf['datastore']['location'] . '/'))) . "\">\n";
            //hier wird bei der Ausgabe des Dateinamens die Endung .kastaliaenc entfernt
            echo substr($file_element, 0, -12);
            //Nutzerfreundliche Ausgabe der Dateigroesse (auf 2 stellen hinter dem Komma gerundet)
            $tempfilesize = filesize($dir_name . "/" . $file_element) / 1024 / 1024;
            if($tempfilesize < 1) {
               //hier wird ueberprueft, ob die ermittelte Dateigroesse negativ ist
               //dies kann passieren, wenn die Datei groesser als 2GB ist und der 32Bit signed integer
               //Wert der Rueckgabe von filesize() zu gross ist
               if($tempfilesize < 0) {
                  echo " (>2GB)";
               }
               else {
                  echo " (" . round(filesize($dir_name . "/" . $file_element) / 1024, 2) . "kB)";
               }
            }
            else {
               echo " (" . round($tempfilesize, 2) . "MB)";
            }
            echo "</a>\n";
            echo "</li>\n";
         }
         //falls es sich nicht um eine von Kastalia verschluesselte Datei handelt
         else { //PRINT UNENCRYPTED FILE DOWNLOAD
            echo "<li class=\"kastalia_file\">\n";
            //hier wird der Link fuer das herunterladen der Datei zusammengesetzt
            //aus dem Link werden vorher alle Zeichen die in der URI nicht stehen duerfen ersetzt (bsp. " " mit "%20")
            echo "<a href=\"download.php?kastalia_filename=" . Kastalia::ConvertToUriString(substr($dir_name . '/' . $file_element, strlen($conf['datastore']['location'] . '/'))) . "\">\n";
            echo $file_element;
            //Nutzerfreundliche Ausgabe der Dateigroesse (auf 2 stellen hinter dem Komma gerundet)
            $tempfilesize = filesize($dir_name . "/" . $file_element) / 1024 / 1024;
            if($tempfilesize < 1) {
               //hier wird ueberprueft, ob die ermittelte Dateigroesse negativ ist
               //dies kann passieren, wenn die Datei groesser als 2GB ist und der 32Bit signed integer
               //Wert der Rueckgabe von filesize() zu gross ist
               if($tempfilesize < 0) {
                  echo " (>2GB)";
               }
               else {
                  echo " (" . round(filesize($dir_name . "/" . $file_element) / 1024, 2) . "kB)";
               }
            }
            else {
               echo " (" . round($tempfilesize, 2) . "MB)";
            }
            echo "</a>\n";
            echo "</li>\n";
         }
      }
      echo "</ul>\n";
      //nachdem der Ordner vollstaendig ausgelesen wurde, wird dieser geschlossen
      closedir($dir_handle);
   }
   else {
      echo "Error: Can't open directory \"$dir_name\"!";
   }
}

//diese Funktion liest den temporaeren Ordner von Kastalia aus
//und loescht alle Dateien die zu lange existieren
function CleanDirectory($dir_name) {
    //die Konfigurationsdatei von Kastalia wird includiert
    //damit die Konfigurationen auch innerhalb dieser Funktion benutzt werden koennen
    require(KASTALIA_BASE . '/config/conf.php');
    if($dir_handle = opendir($dir_name)) {
        //der Inhalt des Ordners wird in dieser Schleife durchgegangen und jedes Element bearbeitet
        //falls es sich um eine Datei handelt, wird die ctime von dieser Datei ermittelt
        while(false !== ($file = readdir($dir_handle))) {
            //Ueberpruefung ob es sich bei dem Element um eine Datei handelt (und keine .htaccess Datei)
            if($file != "." && $file != ".." && $file != ".htaccess" && is_file($dir_name . '/' . $file)) {
                //hier wird die ctime der Datei fuer weitere Pruefungen ermittelt
                if($file_ctime = filectime($dir_name . '/' . $file)) {
                    //die ctime der Datei (wird angegeben in Sekunden seit January 1 1970 00:00:00 GMT)
                    //wird mit der in der Config angegebenen Dauer (in Minuten)
                    //wie lange eine temporaere Datei existieren darf addiert...
                    $file_ctime = $file_ctime + (60 * $conf['upload']['tempctime']);
                    //... um zu ueberpruefen, ob die aktuelle Zeit in Sekunden seit der Unix Epoche
                    //(January 1 1970 00:00:00 GMT) groesser oder gleich der errechneten Zeit ist
                    //die eine temporaere Datei existieren darf
                    if($file_ctime <= time()) {
                        //wenn die aktuelle Zeit in Sekunden seit der Unix Epoche groesser ist
                        //wird die temporaere Datei geloescht
                        if(!unlink($dir_name . '/' . $file)) {
                            //falls ein Fehler beim loeschen auftritt, wird eine Error Nachricht ausgegeben
                            //aber das Skript nicht abgebrochen, da der Rest noch abgearbeitet werden soll
                            echo "Error: Unable to delete temporary file!<br />\n";
                            echo "This constitutes a <b>breach of security</b> because the content of the uploaded file stored unencrypted.<br />\n";
                            echo "Please contact the administrator to have the temporary file " . $dir_name . '/' . $file . " deleted.\n";
                        }
                    }
                }
                else {
                    //falls die ctime der Datei nicht ermittelt werden kann, wird eine Nachricht ausgegeben
                    //aber nicht abgebrochen, da der Rest des Skriptes noch abgearbeitet werden soll
                    echo "Error: Unable to get the ctime of the file: " . $dir_name . '/' . $file . "!<br />";
                    echo "This constitutes a <b>breach of security</b> because the uploaded file will not be deleted and the content stored unencrypted.<br />\n";
                    echo "Please contact the administrator to have the file deleted.\n";
                }
            }
        }
    }
    else {
        echo "Error: Can't open directory \"$dir_name\"!";
    }
}
?>

<?php
//der footer wird includiert
require KASTALIA_TEMPLATES . '/common-footer.inc';
?>