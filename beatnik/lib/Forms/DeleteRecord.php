<?php
/**
 * Copyright 2005-2007 Alkaloid Networks <http://www.alkaloid.net>
 *
 * See the enclosed file LICENSE for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author Ben Klang <ben@alkaloid.net>
 * @package Beatnik
 */
class DeleteRecord extends Horde_Form
{
    /**
     */
    function DeleteRecord(&$vars)
    {
        parent::__construct($vars, _("Are you sure you want to delete this record?"));

        $rectype = $vars->get('rectype');
        $types = Beatnik::getRecTypes();

        $this->addHidden('', 'id', 'text', $vars->get('id'));
        $this->addHidden('', 'curdomain', 'text', $vars->get('curdomain'));
        $this->addHidden('', 'rectype', 'text', $vars->get('rectype'));
        $this->addVariable(_("Type"), 'rectype', 'text', false, true);

        $recset = Beatnik::getRecFields($rectype);
        foreach ($recset as $field => $fdata) {
            if ($fdata['type'] != 'hidden' && ($fdata['infoset'] == 'basic' || $_SESSION['beatnik']['expertmode'])) {
                $this->addVariable(_($fdata['description']), $field, $fdata['type'], false, true);
            }

        }

        $this->setButtons(array(_("Delete"), _("Cancel")));

        return true;
    }
}
