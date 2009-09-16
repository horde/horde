<?php
/**
 * $Horde: beatnik/lib/Forms/Autogenerate.php,v 1.6 2009/07/03 10:05:30 duck Exp $
 *
 * Copyright 2006-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */
class Autogenerate extends Horde_Form
{
    /**
     */
    function Autogenerate(&$vars)
    {
        require BEATNIK_BASE . '/config/autogenerate.php';

        parent::Horde_Form($vars, _("Choose a template for autogenerating the records:"), 'autogenerate');
        $this->setButtons(array(_("Autogenerate"), _("Cancel")));

        // Create an array of template => description for the enum
        $template_keys = array_keys($templates);
        foreach ($template_keys as $template) {
            $t[$template] = $templates[$template]['description'];
        }
        $this->addVariable(_("Template"), 'template', 'enum', true, false, null, array($t, true));

        return true;
    }
}
