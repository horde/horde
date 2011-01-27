<?php
/**
 * The Turba_Object_Group:: class provides a set of methods for dealing with
 * contact groups.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Jon Parise <jon@csh.rit.edu>
 * @package Turba
 */
class Turba_Object_Group extends Turba_Object
{
    /**
     * Constructs a new Turba_Object_Group.
     *
     * @param Turba_Driver $driver  The driver object that this group comes
     *                              from.
     * @param array $attributes     Hash of attributes for this group.
     */
    public function __construct(Turba_Driver $driver, array $attributes = array())
    {
        parent::Turba_Object($driver, $attributes);
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
     */
    public function addMember($contactId, $sourceId = null)
    {
        // Default to the same source as the group.
        if (is_null($sourceId)) {
            $sourceId = $this->getSource();
        }

        // Can't add a group to itself.
        if ($contactId == $this->attributes['__key']) {
            throw new Turba_Exception(_("Can't add a group to itself."));
        }

        // Try to find the contact being added.
        if ($sourceId == $this->getSource()) {
            $contact = $this->driver->getObject($contactId);
        } else {
            $driver = $GLOBALS['injector']->getInstance('Turba_Injector_Factory_Driver')->create($sourceId);
            $contact = $driver->getObject($contactId);
        }

        // Explode members.
        $members = @unserialize($this->attributes['__members']);
        if (!is_array($members)) {
            $members = array();
        }

        // If the contact is from a different source, store its source
        // id as well.
        if ($sourceId == $this->getSource()) {
            $members[] = $contactId;
        } else {
            $members[] = $sourceId . ':' . $contactId;
        }

        // Remove duplicates.
        $members = array_unique($members);

        $this->attributes['__members'] = serialize($members);

        return true;
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
                } catch (Turba_Exception $e) {
                    // Remove the contact if it no longer exists
                    $this->removeMember($member);
                    $modified = true;
                    continue;
                }
            } else {
                list($sourceId, $contactId) = explode(':', $member, 2);
                if (strpos($contactId, ':')) {
                    list($owner, $contactId) = explode(':', $contactId, 2);
                    $sourceId .= ':' . $owner;
                }

                try {
                    $driver = $GLOBALS['injector']->getInstance('Turba_Injector_Factory_Driver')->create($sourceId);
                } catch (Turba_Exception $e) {
                    continue;
                }

                try {
                    $contact = $driver->getObject($contactId);
                } catch (Turba_Exception $e) {
                    // Remove the contact if it no longer exists
                    $this->removeMember($member);
                    $modified = true;
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

}
