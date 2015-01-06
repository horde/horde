<?php
/**
 * Copyright 2000-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */

/**
 * This class provides a set of methods for dealing with contact groups.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jon Parise <jon@csh.rit.edu>
 * @category  Horde
 * @copyright 2000-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Turba
 */
class Turba_Object_Group extends Turba_Object
{
    /**
     * Constructs a new Turba_Object_Group.
     *
     * @param Turba_Driver $driver  The driver object that this group comes
     *                              from.
     * @param array $attributes     Hash of attributes for this group.
     * @param array $options        Hash of options for this object. @since
     *                              Turba 4.2
     */
    public function __construct(Turba_Driver $driver,
                                array $attributes = array(),
                                array $options = array())
    {
        parent::__construct($driver, $attributes, $options);
        $this->attributes['__type'] = 'Group';
    }

    /**
     * Returns true if this object is a group of multiple contacts.
     *
     * @return boolean  True.
     */
    public function isGroup()
    {
        return true;
    }

    /**
     * Contact url.
     *
     * @return Horde_Url
     */
    public function url($view = null, $full = false)
    {
        return Horde::url('browse.php', $full)->add(array(
            'source' => $this->getSource(),
            'key' => $this->getValue('__key')
        ));
    }

    /**
     * Adds a new contact entry to this group.
     *
     * @param string $contactId  The id of the contact to add.
     * @param string $sourceId   The source $contactId is from.
     *
     * @throws Turba_Exception
     * @throws Horde_Exception_NotFound
     */
    public function addMember($contactId, $sourceId = null)
    {
        // Default to the same source as the group.
        if (is_null($sourceId)) {
            $sourceId = $this->getSource();
        }

        // Can't add a group to itself.
        if ($contactId == $this->attributes['__key']) {
            throw new Turba_Exception(_("Can't add a contact list to itself."));
        }

        // Try to find the contact being added.
        if ($sourceId == $this->getSource()) {
            $contact = $this->driver->getObject($contactId);
        } else {
            $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($sourceId);
            $contact = $driver->getObject($contactId);
        }

        // Explode members.
        $members = @unserialize($this->attributes['__members']);
        if (!is_array($members)) {
            $members = array();
        }

        // If the contact is from a different source, store its source
        // id as well.
        $members[] = ($sourceId == $this->getSource())
            ? $contactId
            : $sourceId . ':' . $contactId;

        // Remove duplicates.
        $this->attributes['__members'] = serialize(array_unique($members));
    }

    /**
     * Deletes a contact from this group.
     *
     * @param string $contactId  The id of the contact to remove.
     * @param string $sourceId   The source $contactId is from.
     */
    public function removeMember($contactId, $sourceId = null)
    {
        $members = @unserialize($this->attributes['__members']);

        if (is_null($sourceId) || $sourceId == $this->getSource()) {
            $i = array_search($contactId, $members);
        } else {
            $i = array_search($sourceId . ':' . $contactId, $members);
        }

        if ($i !== false) {
            unset($members[$i]);
        }

        $this->attributes['__members'] = serialize($members);

        return true;
    }

    /**
     * Count the number of contacts in this group.
     *
     * @return integer
     */
    public function count()
    {
        $children = @unserialize($this->attributes['__members']);
        if (!is_array($children)) {
            return 0;
        } else {
            return count($children);
        }
    }

    /**
     * Retrieve the Objects in this group
     *
     * @param array $sort   The requested sort order which is passed to
     *                      Turba_List::sort().
     *
     * @return Turba_List   List containing the members of this group
     */
    public function listMembers($sort = null)
    {
        $list = new Turba_List();

        $children = unserialize($this->attributes['__members']);
        if (!is_array($children)) {
            $children = array();
        }

        reset($children);
        $modified = false;
        foreach ($children as $member) {
            if (strpos($member, ':') === false) {
                try {
                    $contact = $this->driver->getObject($member);
                } catch (Horde_Exception_NotFound $e) {
                    if (!empty($this->_options['removeMissing'])) {
                        // Remove the contact if it no longer exists
                        $this->removeMember($member);
                        $modified = true;
                    }
                    continue;
                }
            } else {
                list($sourceId, $contactId) = explode(':', $member, 2);
                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Factory_Driver')->create($sourceId);
                } catch (Turba_Exception $e) {
                    continue;
                }
                try {
                    $contact = $driver->getObject($contactId);
                } catch (Horde_Exception_NotFound $e) {
                    if (!empty($this->_options['removeMissing'])) {
                        // Remove the contact if it no longer exists
                        $this->removeMember($member);
                        $modified = true;
                    }
                    continue;
                }
            }

            $list->insert($contact);
        }

        // If we've pruned any dead entries, store the changes.
        if ($modified) {
            $this->store();
        }

        $list->sort($sort);
        return $list;
    }

    /**
     * Add members to a group, creating the group if necessary.
     *
     * @param string $source  Destination source.
     * @param array $members  Members of the group. Array of two-element
     *                        arrays: source and key.
     * @param array $opts     Additional options:
     * <pre>
     *  - attr: (array) Array of attributes to use to create a new group.
     *          This should include 'name' at a minimum.
     *  - gid: (string) Existing Group ID.
     *  - name: (string) Group Name.
     * </pre>
     *
     * @return object  Object with the following properties:
     * <pre>
     *   - errors: (array) List of errors when adding members.
     *   - group: (Turba_Object_Group) Group object.
     *   - success: (integer) Number of members sucessfully added.
     * </pre>
     *
     * @throws Turba_Exception
     */
    public static function createGroup(
        $source, $members, array $opts = array()
    )
    {
        global $injector;

        /* Throws Turba_Exception */
        $driver = $injector->getInstance('Turba_Factory_Driver')
            ->create($source);

        if (isset($opts['gid'])) {
            /* Throws Turba_Exception */
            $group = $driver->getObject($opts['gid']);
        } elseif (isset($opts['attr']) && is_array($opts['attr'])) {
            if ($error = Turba::hasMaxContacts($driver)) {
                throw new Turba_Exception($error);
            }

            $newGroup = array_merge($opts['attr'], array(
                '__owner' => $driver->getContactOwner(),
                '__type' => 'Group'
            ));

            try {
                $group = $driver->getObject($driver->add($newGroup));
            } catch (Turba_Exception $e) {}
        } else {
            $group = null;
        }

        if (empty($group) || !$group->isGroup()) {
            throw new Turba_Exception(_("Could not create or add to group."));
        }

        $out = new stdClass;
        $out->errors = array();
        $out->success = 0;
        $out->group = $group;

        // Adding contact to an existing list.
        foreach ($members as $key) {
            try {
                $group->addMember($key[1], $key[0]);
                ++$out->success;
            } catch (Turba_Exception $e) {
                $out->errors[] = $e;
            }
            $group->store();
        }

        return $out;
    }

}
