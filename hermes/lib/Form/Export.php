<?php
/**
 * @package Hermes
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsdl.php.
 *
 */

/**
 * ExportForm:: is the export form which appears with search results on
 * the search screen.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author  Jason M. Felice <jason.m.felice@gmail.com>
 * @package Hermes
 */
class Hermes_Form_Export extends Horde_Form
{
    protected $_useFormToken = false;

    public function __construct(&$vars)
    {
        $perms = $GLOBALS['injector']->getInstance('Horde_Perms');;
        parent::Horde_Form($vars, _("Export Search Results"));

        $formats = array(
            'Hermescsv' => _("Comma-Separated Variable (.csv)"),
            'Hermesxls' => _("Microsoft Excel (.xls)"),
            'Iif' => _("QuickBooks (.iif)"),
            'Hermestsv' => _("Tab-Separated Variable (.tsv, .txt)"),
        );

        $this->addVariable(_("Select the export format"), 'format', 'enum',
                           true, false, null, array($formats));

        if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)) {
            $yesno = array('yes' => _("Yes"),
                           'no' => _("No"));
            $var = &$this->addVariable(_("Mark the time as exported?"),
                                       'mark_exported', 'enum', true, false,
                                       null, array($yesno));
            $var->setDefault('no');
        }

        $this->setButtons(_("Export"));
    }

}
