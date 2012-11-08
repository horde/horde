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
class EditRecord extends Horde_Form
{
    /**
     */
    function EditRecord(&$vars)
    {
        $isnew = !$vars->exists('id');
        $rectype = $vars->get('rectype');
        $recset = Beatnik::getRecFields($rectype);
        if ($isnew) {
            // Pre-load the field defaults on a new record
            foreach ($recset as $field => $fdata) {
                if (isset($fdata['default'])) {
                    $vars->set($field, $fdata['default']);
                }
            }
        }

        parent::__construct($vars, $isnew ? _("Add DNS Record") : _("Edit DNS Record"));

        $types = Beatnik::getRecTypes();
        if (empty($_SESSION['beatnik']['curdomain'])) {
            // Without an active domain, limit the form to creating a new zone.
            $types = array('soa' => _('SOA (Start of Authority)'));
        }
        $action = &Horde_Form_Action::factory('reload');
        $select = &$this->addVariable(_("Record Type"), 'rectype', 'enum', true,
		                              false, null, array($types, true));
        $select->setAction($action);
        $select->setOption('trackchange', true);

        // Do not show record-specific fields until a record type is chosen
        if (!$rectype) {
            return true;
        }

        foreach ($recset as $field => $fdata) {
            if ($fdata['type'] == 'hidden' || ($fdata['infoset'] != 'basic' &&
			    !$_SESSION['beatnik']['expertmode'])) {
                $this->addHidden(_($fdata['description']), $field, 'text',
				                 $fdata['required']);
            } else {
                $this->addVariable(_($fdata['description']), $field,
				                   $fdata['type'], $fdata['required']);
            }

        }

        return true;
    }
}
