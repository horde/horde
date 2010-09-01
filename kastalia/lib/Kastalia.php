<?php
/**
 * Kastalia Base Class.
 *
 *
 * This product includes software developed by the Horde Project (http://www.horde.org/).
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Andre Pawlowski aka sqall <sqall@h4des.org>
 * @package Kastalia
 */
class Kastalia {

    /**
     * Build Kastalia's list of menu items.
     */
    function getMenu()
    {
        global $conf, $registry, $browser, $print_link;

        $menu = new Horde_Menu(HORDE_MENU_MASK_ALL);

        $menu->add(Horde::url('upload_menu.php'), _("Upload"), 'menu/upload.png', Horde_Themes::img());

        return $menu;
    }

    //diese Funktion entfernt aus einem String alle hier angegebenen Sonderzeichen
    function ReplaceSpecialChars($text) {
        $charstochange = array("Ü","Ö","Ä","ä","ü","ö","ß"," ","'","\\","+","/");
        $changetochars = array("Ue","Oe","Ae","ae","ue","oe","ss","_","_","_","_","_");
        $text = str_replace($charstochange,$changetochars,$text);
        return $text;
    }

    //diese Funktion entfernt aus einem String alle hier angegebenen Sonderzeichen
    function ConvertToUriString($text) {
        $charstochange = array(" ");
        $changetochars = array("%20");
        $text = str_replace($charstochange,$changetochars,$text);
        return $text;
    }
}
