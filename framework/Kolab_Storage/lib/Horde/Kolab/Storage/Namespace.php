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
                if (strpos($namespace, $name) === 0) {
                    return array(
                        'namespace' => $namespace,
                        'delimiter' => $delimiter,
                        'type'      => $type,
                    );
                }
            }
        }
        throw new Horde_Kolab_Storage_Exception(
            'Namespace of folder %s cannot be determined.', $name
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
        if (substr($name, 0, 6) == 'INBOX/') {
            $name = substr($name, 6);
        }
        $name = str_replace('/', ':', $name);
        return $name;
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
        if (!preg_match(";(shared\.|INBOX[/]?|user/([^/]+)[/]?)([^@]*)(@.*)?;", $name, $matches)) {
            throw new Horde_Kolab_Storage_Exception(
                'Owner of folder %s cannot be determined.', $name
            );
        }

        if (substr($matches[1], 0, 6) == 'INBOX/') {
            return Horde_Auth::getAuth();
        } elseif (substr($matches[1], 0, 5) == 'user/') {
            $domain = strstr(Horde_Auth::getAuth(), '@');
            $user_domain = isset($matches[4]) ? $matches[4] : $domain;
            return $matches[2] . $user_domain;
        } elseif ($matches[1] == 'shared.') {
            return  'anonymous';
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
        if (!preg_match(";(shared\.|INBOX[/]?|user/([^/]+)[/]?)([^@]*)(@.*)?;", $name, $matches)) {
            throw new Horde_Kolab_Storage_Exception(
                'Subpath of folder %s cannot be determined.', $name
            );
        }
        return $matches[3];
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
}