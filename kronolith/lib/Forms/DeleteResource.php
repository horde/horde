<?php
/**
 * Horde_Form for deleting resources.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_DeleteResourceForm class provides the form for deleting a
 * resource.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_DeleteResourceForm extends Horde_Form
{
    /**
     * Resource being deleted.
     *
     * @var Kronolith_Resource_Single
     */
    protected $_resource;

    public function __construct($vars, $resource)
    {
        $this->_resource = $resource;
        parent::Horde_Form($vars, sprintf(_("Delete %s"), $resource->get('name')));

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(sprintf(_("Really delete the resource \"%s\"? This cannot be undone and all data on this resource will be permanently removed."), $this->_resource->get('name')), 'desc', 'description', false);

        $this->setButtons(array(_("Delete"), _("Cancel")));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        // If cancel was clicked, return false.
        if ($this->_vars->get('submitbutton') == _("Cancel")) {
            return;
        }

        if (!($this->_resource->hasPermission($GLOBALS['registry']->getAuth(), Horde_Perms::DELETE))) {
            throw new Kronolith_Exception(_("Permission denied"));
        }

        // Delete the resource.
        try {
            Kronolith::getDriver('Resource')->delete($this->_resource);
        } catch (Exception $e) {
            throw new Kronolith_Exception(sprintf(_("Unable to delete \"%s\": %s"), $this->_resource->get('name'), $e->getMessage()));
        }
    }

}
