<?php
/**
 * ContextForm Class
 *
 * $Id$
 *
 * Copyright 2005 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Shout
 */
class ContextForm extends Horde_Form {

    var $_useFormToken = false;

    function ContextForm(&$vars)
    {
        global $shout;

        parent::Horde_Form($vars, _("Select System Context"));

        $contextfilter = SHOUT_CONTEXT_CUSTOMERS | SHOUT_CONTEXT_EXTENSIONS|
                         SHOUT_CONTEXT_MOH | SHOUT_CONTEXT_CONFERENCE;
        $contexts = &$shout->getContexts($contextfilter);
        foreach ($contexts as $context) {
            $tmpcontexts[$context] = $context;
        }
        if (count($contexts)) {
            $contexts = &$this->addVariable(_("System Context: "), 'syscontext',
                'enum', true, false, null, array($tmpcontexts, _("Choose:")));

            require_once 'Horde/Form/Action.php';
            $contexts->setAction(Horde_Form_Action::factory('submit'));
        } else {
            $this->addVariable(_("System Context: "), 'syscontext', 'invalid',
                true, false, null,
                array(_("There are no system contexts which you can view.")));
        }
    }
}

class SettingsForm extends Horde_Form {

    var $_useFormToken = false;

    function SettingsForm(&$vars)
    {
        global $shout, $syscontext;

        parent::Horde_Form($vars, _("Edit System Settings"));

        $cols = Shout::getContextTypes();
        $rows = array($syscontext);
        $matrix = array();
        $matrix[0] =
            Shout::integerToArray($shout->getContextProperties($syscontext));

        $this->addVariable(_("Context Properties"), 'properties', 'matrix',
            false, false, null, array($cols, $rows, $matrix, false));
    }

}
