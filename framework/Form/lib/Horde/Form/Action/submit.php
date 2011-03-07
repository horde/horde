<?php
/**
 * Horde_Form_Action_submit is a Horde_Form Action that submits the
 * form after the form element that the action is attached to is
 * modified.
 *
 * Copyright 2002-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Form
 */
class Horde_Form_Action_submit extends Horde_Form_Action {

    var $_trigger = array('onchange');

    function getActionScript($form, $renderer, $varname)
    {
        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('redbox.js', 'horde');
        return 'RedBox.loading(); document.' . $form->getName() . '.submit()';
    }

}
