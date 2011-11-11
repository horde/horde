<?php
/**
 * A library for reading/writing the Kolab format.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */

/**
 * The Horde_Kolab_Format:: interface defines the basic properties of a Kolab
 * format handler.
 *
 * Copyright 2007-2010 Klarälvdalens Datakonsult AB
 * Copyright 2010-2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.horde.org/licenses/lgpl21.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://www.horde.org/libraries/Horde_Kolab_Format
 */
interface Horde_Kolab_Format
{
    /** The package version */
    const VERSION = '@version@';

    /**
     * Load an object based on the given XML stream. The stream may only contain
     * UTF-8 data.
     *
     * @param resource $xml The XML stream of the message.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load($xml, $options = array());

    /**
     * Convert the data to a XML stream. Strings contained in the data array may
     * only be provided as UTF-8 data.
     *
     * @param array $object The data array representing the object.
     *
     * @return resource The data as XML stream.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object, $options = array());
}
