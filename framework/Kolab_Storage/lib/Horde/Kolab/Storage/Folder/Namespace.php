<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace:: class handles IMAP namespaces and allows
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
 * The Horde_Kolab_Storage_Folder_Namespace:: class handles IMAP namespaces and allows
 * to derive folder information from folder names.
 *
 * Copyright 2004-2011 The Horde Project (http://www.horde.org/)
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
abstract class Horde_Kolab_Storage_Folder_Namespace
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
    private $_namespaces = array();

    /**
     * The namespaces with a defined prefix.
     *
     * @var array
     */
    private $_prefix_namespaces = array();

    /**
     * The fallback namespace matching any path if no other namesace matches.
     *
     * @var Horde_Kolab_Storage_Folder_Namespace_Element
     */
    private $_any;

    /**
     * Constructor.
     *
     * @param array $namespaces The namespaces.
     */
    public function __construct(array $namespaces)
    {
        $this->_namespaces = $namespaces;
        foreach ($this->_namespaces as $namespace) {
            if ($namespace->getName() == '') {
                $this->_any = $namespace;
            } else {
                $this->_prefix_namespaces[] = $namespace;
            }
        }
    }

    /**
     * Match a folder name with the corresponding namespace.
     *
     * @param string $name The name of the folder.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace_Element The corresponding namespace.
     *
     * @throws Horde_Kolab_Storage_Exception If the namespace of the folder
     *                                       cannot be determined.
     */
    public function matchNamespace($name)
    {
        foreach ($this->_prefix_namespaces as $namespace) {
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
        throw new Exception('This method is deprecated, assume UTF-8');
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
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', 'UTF-8');
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
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', 'UTF-8');
        return $this->matchNamespace($name)->getSubpath($name);
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
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', 'UTF-8');
        return $this->matchNamespace($name)->getTitle($name);
    }

    /**
     * Generate an IMAP folder name in the personal namespace.
     *
     * @param string $title The new folder title.
     *
     * @return string The IMAP folder name.
     */
    public function setTitle($title)
    {
        return $this->_setTitle(self::PERSONAL, explode(':', $title));
    }

    /**
     * Generate an IMAP folder name in the other namespace.
     *
     * @param string $title The new folder title.
     * @param string $owner The new owner of the folder.
     *
     * @return string The IMAP folder name.
     */
    public function setTitleInOther($title, $owner)
    {
        $path = explode(':', $title);
        array_unshift($path, $owner);
        return $this->_setTitle(self::OTHER, $path);
    }

    /**
     * Generate an IMAP folder name in the shared namespace.
     *
     * @param string $title The new folder title.
     *
     * @return string The IMAP folder name.
     */
    public function setTitleInShared($title)
    {
        return $this->_setTitle(self::SHARED, explode(':', $title));
    }

    /**
     * Generate an IMAP folder name in the specified namespace.
     *
     * @param string $type     The namespace type.
     * @param array  $elements The new path elements.
     *
     * @return string The IMAP folder name.
     */
    private function _setTitle($type, array $elements)
    {
        $matching = array();
        foreach ($this->_namespaces as $namespace) {
            if ($namespace->getType() == $type) {
                $matching[] = $namespace;
            }
        }
        if (count($matching) == 1) {
            $selection = $matching[0];
        } else if (count($matching) > 1) {
            throw new Horde_Kolab_Storage_Exception(
                'Specifying the folder path via title is not supported with multiple namespaces of the same type!'
            );
        } else {
            throw new Horde_Kolab_Storage_Exception(
                sprintf(
                    'No namespace of the type %s!',
                    $type
                )
            );
        }
        return Horde_String::convertCharset(
            $selection->generateName($elements),
            'UTF-8',
            'UTF7-IMAP'
        );
    }

    /**
     * Implementation of the Iterator rewind() method. Rewinds the namespace list.
     *
     * return NULL
     */
    public function rewind()
    {
        return reset($this->_namespaces);
    }

    /**
     * Implementation of the Iterator current(). Returns the current namespace.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace_Element|null The current namespace.
     */
    public function current()
    {
        return current($this->_namespaces);
    }

    /**
     * Implementation of the Iterator key() method. Returns the key of the current namespace.
     *
     * @return mixed The key for the current position.
     */
    public function key()
    {
        return key($this->_namespaces);
    }

    /**
     * Implementation of the Iterator next() method. Returns the next namespace.
     *
     * @return Horde_Kolab_Storage_Folder_Namespace_Element|null The next
     * namespace or null if there are no more namespaces.
     */
    public function next()
    {
        return next($this->_namespaces);
    }

    /**
     * Implementation of the Iterator valid() method. Indicates if the current element is a valid element.
     *
     * @return boolean Whether the current element is valid
     */
    public function valid()
    {
        return key($this->_namespaces) !== null;
    }

    /**
     * Convert the namespace description to a string.
     *
     * @return string The namespace description.
     */
    public function __toString()
    {
        return get_class($this) . ': ' . join(', ', $this->_namespaces);
    }
}