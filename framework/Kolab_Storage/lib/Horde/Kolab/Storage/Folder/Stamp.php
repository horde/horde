<?php
/**
 * Defines unique stamps that allow to determine if folder data has changed or
 * not.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * Defines unique stamps that allow to determine if folder data has changed or
 * not.
 *
 * Copyright 2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
interface Horde_Kolab_Storage_Folder_Stamp
extends Serializable
{
    /** List of deleted IDs */
    const DELETED = 'd';

    /** List of added IDs */
    const ADDED = 'a';

    /**
     * Indicate if there was a complete folder reset.
     *
     * @param Horde_Kolab_Storage_Folder_Stamp_Uids The stamp to compare against.
     *
     * @return boolean True if there was a complete folder reset stamps are
     *                 different, false if not.
     */
    public function isReset(Horde_Kolab_Storage_Folder_Stamp $stamp);

    /**
     * Return the backend object IDs in the folder.
     *
     * @return array The list of backend IDs.
     */
    public function ids();

    /**
     * What changed between this old stamp and the new provided stamp?
     *
     * @param Horde_Kolab_Storage_Folder_Stamp_Uids The new stamp to compare against.
     *
     * @return array|boolean False if there was no change, an array of two
     *                       elements (added IDs, deleted IDs) otherwise.
     */
    public function getChanges(Horde_Kolab_Storage_Folder_Stamp $stamp);
}
