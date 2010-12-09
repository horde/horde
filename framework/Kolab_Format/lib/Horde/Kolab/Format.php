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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
     * Return the name of the resulting document.
     *
     * @return string The name that may be used as filename.
     */
    public function getName();

    /**
     * Return the mime type of the resulting document.
     *
     * @return string The mime type of the result.
     */
    public function getMimeType();

    /**
     * Return the disposition of the resulting document.
     *
     * @return string The disportion of this document.
     */
    public function getDisposition();

    /**
     * Load an object based on the given XML string.
     *
     * @param string &$xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load(&$xmltext);

    /**
     * Convert the data to a XML string.
     *
     * @param array $object The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object);
}
