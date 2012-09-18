<?php
/**
 * Provides Kolab content as a string that will not be modified.
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
 * Provides Kolab content as a string that will not be modified.
 *
 * Copyright 2011-2012 Horde LLC (http://www.horde.org/)
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
class Horde_Kolab_Storage_Data_Object_Content_Raw
extends Horde_Kolab_Storage_Data_Object_Content
implements Horde_Kolab_Storage_Data_Object_Addable
{
    /**
     * Raw object content.
     *
     * @var string
     */
    private $_raw;

    /**
     * Object UID.
     *
     * @var string
     */
    private $_uid;

    /**
     * Constructor.
     *
     * @param string $raw The raw object content.
     * @param string $uid The object UID.
     */
    public function __construct($raw, $uid)
    {
        $this->_raw = $raw;
        $this->_uid = $uid;
    }

    /**
     * Return the UID of the embedded Kolab object.
     *
     * @return string The UID of the Kolab content.
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Create the Kolab content as a string.
     *
     * @return string The Kolab content.
     */
    public function toString()
    {
        return $this->_raw;
    }
}