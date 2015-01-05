<?php
/**
 * Horde_Form_Action_ConditionalEnable is a Horde_Form_Action that
 * enables or disables an element based on the value of another element
 *
 * Format of the $params passed to the constructor:
 * <pre>
 *  $params = array(
 *      'target'  => '[name of element this is conditional on]',
 *      'enabled' => 'true' | 'false',
 *      'values'  => array([target values to check])
 *  );
 * </pre>
 *
 * So $params = array('foo', 'true', array(1, 2)) will enable the field this
 * action is attached to if the value of 'foo' is 1 or 2, and disable it
 * otherwise.
 *
 * Copyright 2002-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Matt Kynaston <matt@kynx.org>
 * @package Form
 */
class Horde_Form_Action_ConditionalEnable extends Horde_Form_Action {

    var $_trigger = array('onload');

    function getActionScript(&$form, $renderer, $varname)
    {
        $GLOBALS['injector']->getInstance('Horde_PageOutput')->addScriptFile('form_helpers.js', 'horde');

        $form_name = $form->getName();
        $target = $this->_params['target'];
        $enabled = $this->_params['enabled'];
        if (!is_string($enabled)) {
            $enabled = ($enabled) ? 'true' : 'false';
        }
        $vals = $this->_params['values'];
        $vals = (is_array($vals)) ? $vals : array($vals);
        $args = "'$varname', $enabled, '" . implode("','", $vals) . "'";

        return "if (addEvent(document.getElementById('$form_name').$target, 'onchange', \"checkEnabled(this, $args);\")) { "
            . "  checkEnabled(document.getElementById('$form_name').$varname, $args); };";
    }

}
