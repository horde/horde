<?php
/**
 * A library for accessing a Kolab storage (usually IMAP).
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */

/**
 * The Horde_Kolab_Storage class provides the means to access the
 * Kolab server storage for groupware objects.
 *
 * To get access to the folder handling you would do the following:
 *
 *   <code>
 *     require_once 'Horde/Kolab/Storage.php';
 *     $folder = Horde_Kolab_Storage::getFolder('INBOX/Calendar');
 *   </code>
 *
 *  or (in case you are dealing with share identifications):
 *
 *   <code>
 *     require_once 'Horde/Kolab/Storage.php';
 *     $folder = Horde_Kolab_Storage::getShare(Auth::getAuth(), 'event');
 *   </code>
 *
 * To access data in a share (or folder) you need to retrieve the
 * corresponding data object:
 *
 *   <code>
 *     require_once 'Horde/Kolab/Storage.php';
 *     $folder = Horde_Kolab_Storage::getShareData(Auth::getAuth(), 'event');
 *   </code>
 *
 * Copyright 2004-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Storage
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Kolab_Storage
 */
interface Horde_Kolab_Storage
{
    /** The package version */
    const VERSION = '@version@';

    /**
     * Get the folder list object.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getList();

    /**
     * Get a folder list object for a "system" user.
     *
     * @param string $type The type of system user.
     *
     * @return Horde_Kolab_Storage_List The handler for the list of folders
     *                                  present in the Kolab backend.
     */
    public function getSystemList($type);

    /**
     * Get a folder representation.
     *
     * @param string $folder The folder name.
     *
     * @return Horde_Kolab_Storage_Folder The Kolab folder object.
     */
    public function getFolder($folder);

    /**
     * Return a data handler for accessing data in the specified
     * folder.
     *
     * @param string $folder       The name of the folder.
     * @param string $object_type  The type of data we want to
     *                             access in the folder.
     * @param int    $data_version Format version of the object data.
     *
     * @return Horde_Kolab_Data The data object.
     */
    public function getData($folder, $object_type = null, $data_version = 1);
}

