<?php
/**
 * Horde_Form for deleting calendars.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/** Horde_Form */
require_once 'Horde/Form.php';

/** Horde_Form_Renderer */
require_once 'Horde/Form/Renderer.php';

/**
 * The Kronolith_DeleteResourceGroupForm class provides the form for
 * deleting a calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_DeleteResourceGroupForm extends Horde_Form {

    /**
     * Calendar being deleted
     */
    var $_calendar;

    function Kronolith_DeleteResourceGroupForm(&$vars, &$resource)
    {
        $this->_resource = &$resource;
        parent::Horde_Form($vars, sprintf(_("Delete %s"), $resource->get('name')));

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(sprintf(_("Really delete the resource \"%s\"? This cannot be undone and all data on this resource will be permanently removed."), $this->_resource->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return false;
        }

        if (!($this->_resource->hasPermission(Horde_Auth::getAuth(), PERMS_DELETE))) {
            return PEAR::raiseError(_("Permission denied"));
        }

        // Delete the resource.
        $result = Kronolith::getDriver('Resource')->delete($this->_resource);
        if ($result instanceof PEAR_Error) {
            return PEAR::raiseError(sprintf(_("Unable to delete \"%s\": %s"), $this->_resource->get('name'), $result->getMessage()));
        }

        return true;
    }

}
