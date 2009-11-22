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
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * The Autoloader allows us to omit "require/include" statements.
 */
require_once 'Horde/Autoloader.php';

/**
 * The Horde_Kolab_Format:: class provides the means to read/write the
 * Kolab format.
 *
 * Copyright 2007-2009 Klarälvdalens Datakonsult AB
 *
 * See the enclosed file COPYING for license information (LGPL). If you did not
 * receive this file, see
 * http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html.
 *
 * @category Kolab
 * @package  Kolab_Format
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
abstract class Horde_Kolab_Format
{

    /**
     * Attempts to return a concrete Horde_Kolab_Format instance based on
     * $format_type.
     *
     * @param string $format_type The format type that should be handled.
     * @param string $object_type The object type that should be handled.
     * @param array  $params      An array of  additional parameters.
     *
     *                                  Supported parameters:
     *
     *                                    'version' - The format version.
     *
     * @return mixed    The newly created concrete Horde_Kolab_Format_XML instance
     *
     * @throws Horde_Exception If the specified driver could not be loaded.
     */
    static public function &factory($format_type = '', $object_type = '',
                                    $params = null)
    {
        $class = 'Horde_Kolab_Format_' . ucfirst(strtolower($format_type));
        if (class_exists($class)) {
            $driver = call_user_func(array($class, 'factory'), $object_type,
                                     $params);
        } else {
            throw new Horde_Exception(sprintf(_("Failed to load Kolab Format driver %s"),
                                              $format_type));
        }

        return $driver;
    }

    /**
     * Return the name of the resulting document.
     *
     * @return string The name that may be used as filename.
     */
    abstract public function getName();

    /**
     * Return the mime type of the resulting document.
     *
     * @return string The mime type of the result.
     */
    abstract public function getMimeType();

    /**
     * Return the disposition of the resulting document.
     *
     * @return string The disportion of this document.
     */
    abstract public function getDisposition();

    /**
     * Load an object based on the given XML string.
     *
     * @param string &$xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Exception
     */
    abstract public function load(&$xmltext);

    /**
     * Convert the data to a XML string.
     *
     * @param array $object The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Exception
     */
    abstract public function save($object);

}
