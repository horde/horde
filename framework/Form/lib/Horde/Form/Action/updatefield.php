<?php
/**
 * Horde_Form_Action_updatefield is a Horde_Form_Action that updates
 * the value of one Horde_Form variable as the variable the action is
 * attached to is updated.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Form
 */
class Horde_Form_Action_updatefield extends Horde_Form_Action {

    var $_trigger = array('onchange', 'onload', 'onkeyup');

    function getActionScript(&$form, &$renderer, $varname)
    {
        return 'updateField' . $this->id() . '();';
    }

    function setValues(&$vars, $sourceVal, $arrayVal = false)
    {
    }

    function printJavaScript()
    {
        $this->_printJavaScriptStart();
        $pieces = explode('%s', $this->_params['format']);
        $fields = $this->_params['fields'];
        $val_first = (substr($this->_params['format'], 0, 2) == '%s');
        if ($val_first) {
            array_shift($pieces);
        }
        if (substr($this->_params['format'], -2) == '%s') {
            array_pop($pieces);
        }

        $args = array();
        if ($val_first) {
            $args[] = "document.getElementById('" . array_shift($fields) . "').value";
        }
        while (count($pieces)) {
            $args[] = "'" . array_shift($pieces) . "'";
            $args[] = "document.getElementById('" . array_shift($fields) . "').value";
        }
?>
// Updater for <?php echo $this->getTarget() ?>.
function updateField<?php echo $this->id() ?>()
{
    var target = document.getElementById('<?php echo $this->getTarget() ?>');
    if (target) {
        target.value = (<?php echo implode(' + ', str_replace("\n", "\\n", $args)) ?>).replace(/(^ +| +$)/, '').replace(/ +/g, ' ');
    }
}<?php
        $this->_printJavaScriptEnd();
    }

}
