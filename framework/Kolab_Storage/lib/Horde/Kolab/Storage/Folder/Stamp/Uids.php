<?php
/**
 * A folder stamp that includes a list of UIDs.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * A folder stamp that includes a list of UIDs.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Thomas Jarosch <thomas.jarosch@intra2net.com>
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
class Horde_Kolab_Storage_Folder_Stamp_Uids
implements Horde_Kolab_Storage_Folder_Stamp
{
    /** The UID validity status */
    const UIDVALIDITY = 'uidvalidity';

    /** The next UID status */
    const UIDNEXT = 'uidnext';

    /**
     * The folder status.
     *
     * @var array
     */
    private $_status;

    /**
     * The list of backend object IDs.
     *
     * @var array
     */
    private $_ids;

    /**
     * Constructor.
     *
     * @param array $status The folder status.
     * @param array $ids    The list of undeleted objects in the folder.
     */
    public function __construct($status, $ids)
    {
        $this->_status = $status;
        $this->_ids    = $ids;
    }

    /**
     * Return the folder UID validity.
     *
     * @return string The folder UID validity marker.
     */
    public function uidvalidity()
    {
        return $this->_status[self::UIDVALIDITY];
    }

    /**
     * Return the folder next UID number.
     *
     * @return string The next UID number.
     */
    public function uidnext()
    {
        return $this->_status[self::UIDNEXT];
    }

    /**
     * Return the backend object IDs in the folder.
     *
     * @return array The list of backend IDs.
     */
    public function ids()
    {
        return $this->_ids;
    }

    /**
     * Indicate if there was a complete folder reset.
     *
     * @param Horde_Kolab_Storage_Folder_Stamp_Uids The stamp to compare against.
     *
     * @return boolean True if there was a complete folder reset stamps are
     *                 different, false if not.
     */
    public function isReset(Horde_Kolab_Storage_Folder_Stamp $stamp)
    {
        if (!$stamp instanceOf Horde_Kolab_Storage_Folder_Stamp_Uids) {
            throw new Horde_Kolab_Storage_Exception('This stamp can only be compared against stamps of its own type.');
        }
        if ($this->uidvalidity() != $stamp->uidvalidity()) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * What changed between this old stamp and the new provided stamp?
     *
     * @param Horde_Kolab_Storage_Folder_Stamp_Uids The new stamp to compare against.
     *
     * @return array|boolean False if there was no change, an array of two
     *                       elements (added IDs, deleted IDs) otherwise.
     */
    public function getChanges(Horde_Kolab_Storage_Folder_Stamp $stamp)
    {
        if (!$stamp instanceOf Horde_Kolab_Storage_Folder_Stamp_Uids) {
            throw new Horde_Kolab_Storage_Exception('This stamp can only be compared against stamps of its own type.');
        }
        if ($this->uidnext() != $stamp->uidnext()
            || count($this->ids()) != count($stamp->ids())) {
            return array(
                self::DELETED => array_values(
                    array_diff($this->ids(), $stamp->ids())
                ),
                self::ADDED => array_values(
                    array_diff($stamp->ids(), $this->ids())
                )
            );
        }
        return false;
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array($this->_status, $this->_ids));
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        list($this->_status, $this->_ids) = @unserialize($data);
    }

    /**
     * Convert the instance into a string.
     *
     * @return string The string representation for this instance.
     */
    public function __toString()
    {
        return sprintf(
            "uidvalidity: %s\nuidnext: %s\nuids: %s",
            $this->uidvalidity(),
            $this->uidnext(),
            join(', ', $this->ids())
        );
    }
}
