<?php
/**
 * A library for reading/writing the Kolab format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */

/**
 * The Horde_Kolab_Format:: interface defines the basic properties of a Kolab
 * format handler.
 *
 * Copyright 2007-2010 Klarälvdalens Datakonsult AB
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Format
 */
interface Horde_Kolab_Format
{
    /** The package version */
    const VERSION = '@version@';

    /**
     * Load an object based on the given XML stream.
     *
     * @param resource $xml The XML stream of the message.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load($xml);

    /**
     * Convert the data to a XML stream.
     *
     * @param array $object The data array representing the object.
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object);
}
