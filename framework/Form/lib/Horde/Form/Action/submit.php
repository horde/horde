<?php
/**
 * Horde_Form_Action_submit is a Horde_Form Action that submits the
 * form after the form element that the action is attached to is
 * modified.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
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
