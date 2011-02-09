<?php
/**
 * Bridges a MIME message with Kolab format data parsing.
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
 * Bridges a MIME message with Kolab format data parsing.
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
class Horde_Kolab_Storage_Data_Format_Mime
implements Horde_Kolab_Storage_Data_Format
{
    /**
     * Parses the objects for the specified backend IDs.
     *
     * @param string $folder  The folder to access.
     * @param array  $uid     The object backend ID.
     * @param mixed  $data    The data that should get parsed.
     * @param array  $options Additional options for fetching.
     *
     * @return array The parsed object.
     */
    public function parse($folder, $obid, $data, $options = array())
    {
    }
}
