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
class Horde_Kolab_Storage_Namespace
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
    private $_namespaces = array(
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
     * Constructor.
     */
    public function __construct()
    {
        $this->_charset = Horde_Nls::getCharset();
        $this->_sharedPrefix = 'shared.';
    }

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
        $namespace = $this->matchNamespace($name);
        $path = $this->_subpath($name, $namespace);
        return join($path, ':');
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
                $user .= '@' . $domain;
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
        $name = str_replace(':', '/', $name);
        if (substr($name, 0, 5) != 'user/' && substr($name, 0, 7) != 'shared.') {
            $name = 'INBOX/' . $name;
        }
        return Horde_String::convertCharset($name, $this->_charset, 'UTF7-IMAP');
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
        if ($namespace['type'] == self::SHARED && 
            !empty($this->_sharedPrefix)) {
            if (strpos($path[0], $this->_sharedPrefix) === 0) {
                $path[0] = substr($path[0], strlen($this->_sharedPrefix));
            }
        }
        //@todo: What about the potential trailing domain?
        return $path;
    }
}