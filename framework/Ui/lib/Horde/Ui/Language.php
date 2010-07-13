<?php
/**
 * The Horde_Ui_Language:: class provides a widget for changing the
 * currently selected language.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Horde_Ui
 */
class Horde_Ui_Language {

    /**
     * Render the language selection.
     *
     * @param boolean $form  Return the selection box as a complete standalone
     *                       form.
     *
     * @return string  The HTML selection box.
     */
    static public function render()
    {
        $html = '';

        if (!$GLOBALS['prefs']->isLocked('language')) {
            $_SESSION['horde_language'] = $GLOBALS['registry']->preferredLang();
            $html = sprintf('<form name="language" action="%s">',
                            Horde::url($GLOBALS['registry']->get('webroot', 'horde') . '/services/language.php', false, -1));
            $html .= '<input type="hidden" name="url" value="' . @htmlspecialchars(Horde::selfUrl(false, false, true)) . '" />';
            $html .= '<select name="new_lang" onchange="document.language.submit()">';
            foreach ($GLOBALS['nls']['languages'] as $key => $val) {
                $sel = ($key == $_SESSION['horde_language']) ? ' selected="selected"' : '';
                $html .= "<option value=\"$key\"$sel>$val</option>";
            }
            $html .= '</select></form>';
        }

        return $html;
    }

}
