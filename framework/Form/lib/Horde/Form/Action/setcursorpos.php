<?php
/**
 * Horde_Form_Action_setcursorpos is a Horde_Form_Action that places
 * the cursor in a text field.
 *
 * The params array contains the desired cursor position.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Form
 */
class Horde_Form_Action_setcursorpos extends Horde_Form_Action {

    var $_trigger = array('onload');

    function getActionScript(&$form, $renderer, $varname)
    {
        Horde::addScriptFile('form_helpers.js', 'horde');

        $pos = implode(',', $this->_params);
        return 'form_setCursorPosition(document.forms[\'' .
            htmlspecialchars($form->getName()) . '\'].elements[\'' .
            htmlspecialchars($varname) . '\'].id, ' . $pos . ');';
    }

}
