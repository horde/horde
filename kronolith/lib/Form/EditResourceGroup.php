<?php
/**
 * Horde_Form for editing resource groups.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_EditResourceGroupForm class provides the form for editing
 * resource groups.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Form_EditResourceGroup extends Horde_Form
{
    /**
     * Resource group being edited.
     *
     * @var Kronolith_Resource_Single
     */
    protected $_resource;

    /**
     * @throws Kronolith_Exception
     */
    public function __construct($vars, $resource)
    {
        $this->_resource = $resource;
        parent::__construct($vars, sprintf(_("Edit %s"), $resource->get('name')));

        $resources = Kronolith::getDriver('Resource')->listResources(Horde_Perms::READ, array('type' => Kronolith_Resource::TYPE_SINGLE));
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

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        $original_name = $this->_resource->get('name');
        $new_name = $this->_vars->get('name');
        $this->_resource->set('name', $new_name);
        $this->_resource->set('description', $this->_vars->get('description'));
        $this->_resource->set('members', $this->_vars->get('members'));

        try {
            $this->_resource->save();
        } catch (Exception $e) {
            throw new Kronolith_Exception(sprintf(_("Unable to save resource \"%s\": %s"), $new_name, $e->getMessage()));
        }

        return $this->_resource;
    }

}
