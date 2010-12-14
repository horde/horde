<?php
/**
 * The basic handler for accessing data from Kolab storage.
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
 * The basic handler for accessing data from Kolab storage.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Base
{
    /**
     * The master Kolab storage system.
     *
     * @var Horde_Kolab_Storage_Driver
     */
    private $_master;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Driver $master The primary connection driver.
     * @param string $driver The driver used for the primary storage connection.
     * @param array  $params Additional connection parameters.
     */
    public function __construct(Horde_Kolab_Storage_Driver $master)
    {
        $this->_master = $master;
    }

    /**
     * Returns the list of folders visible to the current user.
     *
     * @return array The list of IMAP folders, represented as
     *               a list of strings.
     */
    public function listFolders()
    {
        return $this->_master->getMailboxes();
    }

}