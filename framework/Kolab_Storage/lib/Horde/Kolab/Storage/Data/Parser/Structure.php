<?php
/**
 * Parses an object by relying on the MIME capabilities of the backend.
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
 * Parses an object by relying on the MIME capabilities of the backend.
er.
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
class Horde_Kolab_Storage_Data_Parser_Structure
implements  Horde_Kolab_Storage_Data_Parser
{
    /**
     * The backend driver.
     *
     * @param Horde_Kolab_Storage_Driver
     */
    private $_driver;

    /**
     * Constructor
     *
     * @param Horde_Kolab_Storage_Driver $driver The backend driver.
     */
    public function __construct(
        Horde_Kolab_Storage_Driver $driver
    ) {
        $this->_driver = $driver;
    }

    /**
     * Fetches the objects for the specified UIDs.
     *
     * @param string $folder The folder to access.
     *
     * @return array The parsed objects.
     */
    public function fetch($folder, $uids, $options = array())
    {
        return $this->_driver->fetchStructure($folder, $uids);
    }
}
