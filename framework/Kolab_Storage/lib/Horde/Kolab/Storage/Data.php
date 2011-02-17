<?php
/**
 * Defines the Kolab data handler.
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
 * Defines the Kolab data handler.
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
interface Horde_Kolab_Storage_Data
extends Horde_Kolab_Storage_Queriable
{
    /** Identifies the basic list query */
    const QUERY_BASE  = 'Data';

    /**
     * Return the ID of this data handler.
     *
     * @return string The ID.
     */
    public function getId();

    /**
     * Retrieves the objects for the given UIDs.
     *
     * @param array $uids The message UIDs.
     *
     * @return array An array of objects.
     */
    public function fetch($uids);
}