<?php
/**
 * A base decorator definition.
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
 * A base decorator definition.
 *
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
class Horde_Kolab_Format_Decorator_Base
implements Horde_Kolab_Format
{
    /**
     * The decorated Kolab format handler.
     *
     * @var Horde_Kolab_Format
     */
    private $_handler;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Format $handler The handler to be decorated.
     */
    public function __construct(Horde_Kolab_Format $handler)
    {
        $this->_handler = $handler;
    }

    /**
     * Return the decorated handler.
     *
     * @return Horde_Kolab_Format The handler.
     */
    public function getHandler()
    {
        return $this->_handler;
    }

    /**
     * Return the name of the resulting document.
     *
     * @return string The name that may be used as filename.
     */
    public function getName()
    {
        return $this->_handler->getName();
    }

    /**
     * Return the mime type of the resulting document.
     *
     * @return string The mime type of the result.
     */
    public function getMimeType()
    {
        return $this->_handler->getMimeType();
    }

    /**
     * Return the disposition of the resulting document.
     *
     * @return string The disportion of this document.
     */
    public function getDisposition()
    {
        return $this->_handler->getDisposition();
    }

    /**
     * Load an object based on the given XML string.
     *
     * @param string &$xmltext The XML of the message as string.
     *
     * @return array The data array representing the object.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function load(&$xmltext)
    {
        return $this->_handler->load($xmltext);
    }

    /**
     * Convert the data to a XML string.
     *
     * @param array &$object The data array representing the note.
     *
     * @return string The data as XML string.
     *
     * @throws Horde_Kolab_Format_Exception
     */
    public function save($object)
    {
        return $this->_handler->save($object);
    }
}