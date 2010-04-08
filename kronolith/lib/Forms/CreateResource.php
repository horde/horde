<?php
/**
 * Horde_Form for creating resources.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_CreateResourceForm class provides the form for creating a
 * resource.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_CreateResourceForm extends Horde_Form
{
    /**
     * @throws Kronolith_Exception
     */
    public function __construct($vars)
    {
        parent::Horde_Form($vars, _("Create Resource"));

        $responses =  array(Kronolith_Resource::RESPONSETYPE_ALWAYS_ACCEPT => _("Always Accept"),
                            Kronolith_Resource::RESPONSETYPE_ALWAYS_DECLINE => _("Always Decline"),
                            Kronolith_Resource::RESPONSETYPE_AUTO => _("Automatically"),
                            Kronolith_Resource::RESPONSETYPE_MANUAL => _("Manual"),
                            Kronolith_Resource::RESPONSETYPE_NONE => _("None"));

        /* Get a list of available resource groups */
        $groups = Kronolith::getDriver('Resource')
            ->listResources(Horde_Perms::READ,
                            array('type' => Kronolith_Resource::TYPE_GROUP));
        $enum = array();
        foreach ($groups as $id => $group) {
            $enum[$id] = $group->get('name');
        }

        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Email"), 'email', 'email', false);
        $v = &$this->addVariable(_("Response type"), 'responsetype', 'enum', true, false, null, array('enum' => $responses));
        $v->setDefault(Kronolith_Resource::RESPONSETYPE_AUTO);
        $this->addVariable(_("Groups"), 'category', 'multienum', false, false, null, array('enum' => $enum));
        $this->setButtons(array(_("Create")));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        $new = array('name' => $this->_vars->get('name'),
                     'description' => $this->_vars->get('description'),
                     'response_type' => $this->_vars->get('responsetype'),
                     'email' => $this->_vars->get('email'));
        $resource = Kronolith_Resource::addResource(new Kronolith_Resource_Single($new));

        /* Do we need to add this to any groups? */
        $groups = $this->_vars->get('category');
        if (!empty($groups)) {
            foreach ($groups as $group_id) {
                $group = Kronolith::getDriver('Resource')->getResource($group_id);
                $members = $group->get('members');
                $members[] = $resource->getId();
                $group->set('members', $members);
                $group->save();
            }
        }
    }

}
