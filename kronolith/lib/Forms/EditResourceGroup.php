<?php
/**
 * Horde_Form for editing resource calendars.
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
 * The Kronolith_EditResourceForm class provides the form for
 * editing a calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @package Kronolith
 */
class Kronolith_EditResourceGroupForm extends Horde_Form {

    /**
     * Calendar being edited
     */
    var $_resource;

    function Kronolith_EditResourceGroupForm(&$vars, &$resource)
    {
        $this->_resource = &$resource;
        parent::Horde_Form($vars, sprintf(_("Edit %s"), $resource->get('name')));

        $resources = Kronolith_Resource::listResources(PERMS_READ, array('type' => 'Single'));
        $enum = array();
        foreach ($resources as $r) {
            $enum[$r->getId()] = htmlspecialchars($r->get('name'));
        }

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Resources"), 'members', 'multienum', false, false, null, array('enum' => $enum));

        $this->setButtons(array(_("Save")));
    }

    function execute()
    {
        $original_name = $this->_resource->get('name');
        $new_name = $this->_vars->get('name');
        $this->_resource->set('name', $new_name);
        $this->_resource->set('description', $this->_vars->get('description'));
        $this->_resource->set('members', serialize($this->_vars->get('members')));
        if ($original_name != $new_name) {
            $result = Kronolith::getDriver()->rename($original_name, $new_name);
            if (is_a($result, 'PEAR_Error')) {
                return PEAR::raiseError(sprintf(_("Unable to rename \"%s\": %s"), $original_name, $result->getMessage()));
            }
        }

        $result = $this->_resource->save();
        if (is_a($result, 'PEAR_Error')) {
            return PEAR::raiseError(sprintf(_("Unable to save resource \"%s\": %s"), $new_name, $result->getMessage()));
        }

        return $this->_resource;

    }

}
