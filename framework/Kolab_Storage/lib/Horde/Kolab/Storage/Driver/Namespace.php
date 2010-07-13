<?php
/**
 * The Horde_Kolab_Storage_Driver_Namespace:: class handles IMAP namespaces and allows
 * to derive folder information from folder names.
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
 * The Horde_Kolab_Storage_Driver_Namespace:: class handles IMAP namespaces and allows
 * to derive folder information from folder names.
 *
 * Copyright 2004-2010 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_Driver_Namespace
implements Iterator
{
    /** The possible namespace types (RFC 2342 [5]) */
    const PERSONAL = 'personal';
    const OTHER    = 'other';
    const SHARED   = 'shared';

    /**
     * The namespaces.
     *
     * @var array
     */
    protected $_namespaces = array();

    /**
     * The characterset this module uses to communicate with the outside world.
     *
     * @var string
     */
    protected $_charset;

    /**
     * A prefix in the shared namespaces that will be ignored/removed.
     *
     * @var string
     */
    protected $_sharedPrefix;

    /**
     * The namespace that matches any folder name not matching to another
     * namespace.
     *
     * @var Horde_Kolab_Storage_Driver_Namespace_Element
     */
    protected $_any;

    /**
     * Indicates the personal namespace that the class will use to create new
     * folders.
     *
     * @var Horde_Kolab_Storage_Driver_Namespace_Element
     */
    protected $_primaryPersonalNamespace;

    /**
     * A helper for iteration over the namespaces.
     *
     * @var array
     */
    protected $_iteration;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->_charset = $GLOBALS['registry']->getCharset();
        if (empty($this->_primaryPersonalNamespace)) {
            $personal = null;
            foreach ($this->_namespaces as $namespace) {
                if ($namespace->getName() == 'INBOX') {
                    $this->_primaryPersonalNamespace = $namespace;
                    break;
                }
                if (empty($personal) && $namespace->getType() == self::PERSONAL) {
                    $personal = $namespace;
                }
            }
            if (empty($this->_primaryPersonalNamespace)) {
                $this->_primaryPersonalNamespace = $personal;
            }
        }
    }

    /**
     * Match a folder name with the corresponding namespace.
     *
     * @param string $name The name of the folder.
     *
     * @return Horde_Kolab_Storage_Driver_Namespace_Element The corresponding namespace.
     *
     * @throws Horde_Kolab_Storage_Exception If the namespace of the folder
     *                                       cannot be determined.
     */
    public function matchNamespace($name)
    {
        foreach ($this->_namespaces as $namespace) {
            if ($namespace->matches($name)) {
                return $namespace;
            }
        }
        if (!empty($this->_any)) {
            return $this->_any;
        }
        throw new Horde_Kolab_Storage_Exception(
            sprintf('Namespace of folder %s cannot be determined.', $name)
        );
    }

    /**
     * Get the character set used/expected when calling the getTitle() or
     * setName() methods.
     *
     * @return string The character set.
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * Return the title of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string The title of the folder.
     */
    public function getTitle($name)
    {
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', $this->_charset);
        return $this->matchNamespace($name)->getTitle($name);
    }

    /**
     * Return the owner of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string The owner of the folder.
     */
    public function getOwner($name)
    {
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', $this->_charset);
        return $this->matchNamespace($name)->getOwner($name);
    }

    /**
     * Get the sub path for the given folder name.
     *
     * @param string $name The folder name.
     *
     * @return string The sub path.
     */
    public function getSubpath($name)
    {
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', $this->_charset);
        return $this->matchNamespace($name)->getSubpath($name);
    }

    /**
     * Generate an IMAP folder name.
     *
     * @param string $name The new folder name.
     *
     * @return string The IMAP folder name.
     */
    public function setName($name)
    {
        $namespace = $this->matchNamespace($name);
        $path = explode(':', $name);
        if (empty($this->_sharedPrefix)
            || (strpos($path[0], $this->_sharedPrefix) === false
                && $namespace->getType() != self::OTHER)) {
            array_unshift($path, $this->_primaryPersonalNamespace->getName());
            $namespace = $this->_primaryPersonalNamespace;
        }
        return Horde_String::convertCharset($namespace->generateName($path), $this->_charset, 'UTF7-IMAP');
    }

    function rewind()
    {
        $this->_iterator = $this->_namespaces;
        $this->_iterator[] = $this->_any;
        return reset($this->_iterator);
    }

    function current()
    {
        return current($this->_iterator);
    }

    function key()
    {
        return key($this->_iterator);
    }

    function next()
    {
        return next($this->_iterator);
    }

    function valid()
    {
        return key($this->_iterator) !== null;
    }
}