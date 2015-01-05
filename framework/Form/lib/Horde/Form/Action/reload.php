<?php
/**
 * Horde_Form_Action_reload is a Horde_Form Action that reloads the
 * form with the current (not the original) value after the form element
 * that the action is attached to is modified.
 *
 * Copyright 2003-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Jan Schneider <jan@horde.org>
 * @package Form
 */
class Horde_Form_Action_reload extends Horde_Form_Action {

    var $_trigger = array('onchange');

    function getActionScript($form, $renderer, $varname)
    {
        $page_output = $GLOBALS['injector']->getInstance('Horde_PageOutput');
        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $page_output->addScriptFile('redbox.js', 'horde');
        return 'if (this.value) { document.' . $form->getName() . '.formname.value=\'\'; RedBox.loading(); document.' . $form->getName() . '.submit() }';
    }

}
