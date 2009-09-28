<?php
/**
 * Horde_Form for creating resource calendars.
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
 * The Kronolith_CreateResourceForm class provides the form for
 * creating a calendar.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_CreateResourceGroupForm extends Horde_Form {

    function Kronolith_CreateResourceGroupForm(&$vars)
    {
        parent::Horde_Form($vars, _("Create Resource"));

        $resources = Kronolith::getDriver('Resource')->listResources(PERMS_READ, array('type' => 'Single'));
        $enum = array();
        foreach ($resources as $resource) {
            $enum[$resource->getId()] = htmlspecialchars($resource->get('name'));
        }
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Resources"), 'members', 'multienum', false, false, null, array('enum' => $enum));
        $this->setButtons(array(_("Create")));
    }

    function execute()
    {
        $members = serialize($this->_vars->get('members'));
        $new = array('name' => $this->_vars->get('name'),
                     'description' => $this->_vars->get('description'),
                     'members' => $members);

        $resource = new Kronolith_Resource_Group($new);
        return $results = Kronolith_Resource::addResource($resource);
    }

}
