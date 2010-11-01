<?php
/**
 * Copyright 2010 Alkaloid Networks LLC <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Pastie
 */
class PasteForm extends Horde_Form
{
    /**
     */
    function PasteForm(&$vars)
    {
        parent::Horde_Form($vars, _("New Paste"));

        $engine = 'Pastie_Highlighter_' . $GLOBALS['conf']['highlighter']['engine'];
        $tmp = call_user_func(array($engine, 'getSyntaxes'));
        $types = array();
        foreach ($tmp as $type) {
            $types[$type] = $type;
        }

        // Some highlighters have a long list of supported languages.
        // Default to PHP if one is not already specified
        $curtype = $vars->get('syntax');
        if (empty($curtype)) {
            $vars->set('syntax', 'php');
        }

        $this->addVariable(_("Title"), 'title', 'text', false);

        $this->addVariable(_("Syntax"), 'syntax', 'enum', true,
		           false, null, array($types, false));

        $this->addVariable(_("Paste"), 'paste', 'longtext', true, false, null,
                           array('rows' => 20, 'cols' => 100));

        return true;
    }
}
