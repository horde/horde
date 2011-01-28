<?php
/**
 * Interface for query results.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * Interface for query results.
 *
 * Copyright 2008-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
interface Horde_Kolab_Server_Result_Interface
{
    /**
     * The number of result entries.
     *
     * @return int The number of elements.
     */
    public function count();

    /**
     * Test if the last search exceeded the size limit.
     *
     * @return boolean True if the last search exceeded the size limit.
     */
    public function sizeLimitExceeded();

    /**
     * Return the result as an array.
     *
     * @return array The resulting array.
     */
    public function asArray();
}