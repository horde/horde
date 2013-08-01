<?php
/**
 * Horde_Form for editing resources.
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @package Kronolith
 */

/**
 * The Kronolith_Form_EditResource class provides the form for editing a
 * resource.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Kronolith
 */
class Kronolith_Form_EditResource extends Horde_Form
{
    /**
     * Resource being edited.
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

        $this->addHidden('', 'c', 'text', true);
        $this->addVariable(_("Name"), 'name', 'text', true);
        $this->addVariable(_("Email"), 'email', 'email', false);
        $this->addVariable(_("Description"), 'description', 'longtext', false, false, null, array(4, 60));
        $this->addVariable(_("Response type"), 'responsetype', 'enum', true, false, null, array('enum' => $responses));
        $this->addVariable(_("Groups"), 'category', 'multienum', false, false, null, array('enum' => $enum));

        /* Display URL. */
        $url = Horde::url('month.php', true, -1)
            ->add('display_cal', $resource->get('calendar'));
        $this->addVariable(
             _("Display URL"), '', 'link', false, false, null,
             array(array(
                 'url' => $url,
                 'text' => $url,
                 'title' => _("Click or copy this URL to display the calendar of this resource"),
                 'target' => '_blank')
             )
        );

        $this->setButtons(array(
            _("Save"),
            array('class' => 'horde-delete', 'value' => _("Delete")),
            array('class' => 'horde-cancel', 'value' => _("Cancel"))
        ));
    }

    /**
     * @throws Kronolith_Exception
     */
    public function execute()
    {
        switch ($this->_vars->submitbutton) {
        case _("Save"):
            $new_name = $this->_vars->get('name');
            $this->_resource->set('name', $new_name);
            $this->_resource->set('description', $this->_vars->get('description'));
            $this->_resource->set('response_type', $this->_vars->get('responsetype'));
            $this->_resource->set('email', $this->_vars->get('email'));

            /* Update group memberships */
            $driver = Kronolith::getDriver('Resource');
            $existing_groups = $driver->getGroupMemberships($this->_resource->getId());
            $new_groups = $this->_vars->get('category');
            $new_groups = (is_null($new_groups) ? array() : $new_groups);
            foreach ($existing_groups as $gid) {
                 $i = array_search($gid, $new_groups);
                 if ($i === false) {
                     // No longer in this group
                     $group = $driver->getResource($gid);
                     $members = $group->get('members');
                     $idx = array_search($this->_resource->getId(), $members);
                     if ($idx !== false) {
                         unset($members[$idx]);
                         reset($members);
                         $group->set('members', $members);
                         $group->save();
                     }
                 } else {
                     // We know it's already in the group, remove it so we don't
                     // have to check/add it again later.
                     unset($new_groups[$i]);
                 }
            }

            reset($new_groups);
            foreach ($new_groups as $gid) {
                $group = $driver->getResource($gid);
                $members = $group->get('members');
                $members[] = $this->_resource->getId();
                $group->set('members', $members);
                $group->save();
            }

            try {
                $this->_resource->save();
            } catch (Exception $e) {
                throw new Kronolith_Exception(sprintf(_("Unable to save resource \"%s\": %s"), $new_name, $e->getMessage()));
            }

            return $this->_resource;

        case _("Delete"):
            Horde::url('resources/delete.php')
                ->add('c', $this->_vars->c)
                ->redirect();
            break;

        case _("Cancel"):
            Horde::url($GLOBALS['prefs']->getValue('defaultview') . '.php', true)
                ->redirect();
            break;
        }
    }
}
