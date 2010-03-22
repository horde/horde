<?php
/**
 * The Horde_Kolab_Storage_Namespace:: class handles IMAP namespaces and allows
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
 * The Horde_Kolab_Storage_Namespace:: class handles IMAP namespaces and allows
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
abstract class Horde_Kolab_Storage_Namespace
{
    /** The possible namespace types (RFC 2342 [5]) */
    const PRIV   = 'private';
    const OTHER  = 'other';
    const SHARED = 'shared';

    /**
     * The namespaces.
     *
     * @var array
     */
    protected $_namespaces = array(
        self::PRIV => array(
            'INBOX' => '/',
        ),
        self::OTHER => array(
            'user' => '/',
        ),
        self::SHARED => array(
            '' => '/',
        ),
    );

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
     * Indicates the personal namespace that the class will use to create new
     * folders.
     *
     * @var string
     */
    protected $_primaryPersonalNamespace;

    /**
     * Match a folder name with the corresponding namespace.
     *
     * @param string $name The name of the folder.
     *
     * @return array The corresponding namespace.
     *
     * @throws Horde_Kolab_Storage_Exception If the namespace of the folder
     *                                       cannot be determined.
     */
    protected function matchNamespace($name)
    {
        foreach (array(self::PRIV, self::OTHER, self::SHARED) as $type) {
            foreach ($this->_namespaces[$type] as $namespace => $delimiter) {
                if ($namespace === '' || strpos($name, $namespace) === 0) {
                    return array(
                        'namespace' => $namespace,
                        'delimiter' => $delimiter,
                        'type'      => $type,
                    );
                }
            }
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
        return join($this->_subpath($name, $this->matchNamespace($name)), ':');
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
        $namespace = $this->matchNamespace($name);
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', $this->_charset);
        $path = explode($namespace['delimiter'], $name);
        if ($namespace['type'] == self::PRIV) {
            return self::PRIV;
        }
        if ($namespace['type'] == self::OTHER) {
            $user = $path[1];
            $domain = strstr(array_pop($path), '@');
            if (!empty($domain)) {
                $user .= $domain;
            }
            return self::OTHER . ':' . $user;
        }
        if ($namespace['type'] == self::SHARED) {
            return self::SHARED;
        }
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
        $namespace = $this->matchNamespace($name);
        $path = $this->_subpath($name, $namespace);
        return join($path, $namespace['delimiter']);
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
                && !in_array($path[0], array_keys($this->_namespaces[self::OTHER])))) {
            $namespace = $this->_getPrimaryPersonalNamespace();
            array_unshift($path, $namespace['namespace']);
        } else {
            $namespace = $this->matchNamespace($name);
        }
        return Horde_String::convertCharset(
            join($path, $namespace['delimiter']), $this->_charset, 'UTF7-IMAP'
        );
    }

    /**
     * Returns the primary personal namespace.
     *
     * @return array The primary personal namespace.
     */
    protected function _getPrimaryPersonalNamespace()
    {
        foreach ($this->_namespaces[self::PRIV] as $namespace => $delimiter) {
            if ($namespace == $this->_primaryPersonalNamespace) {
                return array(
                    'namespace' => $namespace,
                    'delimiter' => $delimiter,
                    'type'      => self::PRIV,
                );
            }
        }
        return array(
            'namespace' => array_shift(array_keys($this->_namespaces[self::PRIV])),
            'delimiter' => reset($this->_namespaces[self::PRIV]),
            'type'      => self::PRIV,
        );
    }

    /**
     * Return an array describing the path elements of the folder.
     *
     * @param string $name      The name of the folder.
     * @param array  $namespace The namespace of the folder.
     *
     * @return array The path elements.
     */
    protected function _subpath($name, array $namespace)
    {
        $name = Horde_String::convertCharset($name, 'UTF7-IMAP', $this->_charset);
        $path = explode($namespace['delimiter'], $name);
        if ($path[0] == $namespace['namespace']) {
            array_shift($path);
        }
        if ($namespace['type'] == self::OTHER) {
            array_shift($path);
        }
        if (!empty($this->_sharedPrefix) 
            && $namespace['type'] == self::SHARED) {
            if (strpos($path[0], $this->_sharedPrefix) === 0) {
                $path[0] = substr($path[0], strlen($this->_sharedPrefix));
            }
        }
        //@todo: What about the potential trailing domain?
        return $path;
    }
}