<?php

/** Horde_Form_Action */
require_once 'Horde/Form/Action.php';

/**
 * Horde_Form_Action_whups_reload is a Horde_Form Action that reloads the
 * form with the current (not the original) value after the form element
 * that the action is attached to is modified.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Horde_Form
 */
class Horde_Form_Action_whups_reload extends Horde_Form_Action {

    var $_trigger = array('onchange');

    function getActionScript($form, $renderer, $varname)
    {
        Horde::addScriptFile('effects.js', 'horde', true);
        Horde::addScriptFile('redbox.js', 'horde', true);
        return 'if (this.value) { document.' . $form->getName()
            . '.formname.value=\'' . $this->_params['formname']
            . '\'; RedBox.loading(); document.' . $form->getName()
            . '.submit() }';
    }

}
