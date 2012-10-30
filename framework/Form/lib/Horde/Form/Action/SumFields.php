<?php
/**
 * Horde_Form_Action_sum_fields is a Horde_Form_Action that sets the target
 * field to the sum of one or more other numeric fields.
 *
 * The params array should contain the names of the fields which will be
 * summed.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @author  Matt Kynaston <matt@kynx.org>
 * @package Form
 */
class Horde_Form_Action_SumFields extends Horde_Form_Action {

    var $_trigger = array('onload');

    function getActionScript(&$form, $renderer, $varname)
    {
        $GLOBALS['injector']->getInstance('Horde_PageOutput')->addScriptFile('form_helpers.js', 'horde');

        $form_name = $form->getName();
        $fields = "'" . implode("','", $this->_params) . "'";
        $js = array();
        $js[] = sprintf('document.forms[\'%s\'].elements[\'%s\'].disabled = true;',
                        $form_name,
                        $varname);
        foreach ($this->_params as $field) {
            $js[] = sprintf("addEvent(document.forms['%1\$s'].elements['%2\$s'], \"onchange\", \"sumFields(document.forms['%1\$s'], '%3\$s', %4\$s);\");",
                            $form_name,
                            $field,
                            $varname,
                            $fields);
        }

        return implode("\n", $js);
    }

}
