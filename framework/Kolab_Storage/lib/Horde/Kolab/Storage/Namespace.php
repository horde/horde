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
     * Return the title of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return sring The title of the folder.
     */
    public function getTitle($name)
    {
        if (substr($name, 0, 6) == 'INBOX/') {
            $name = substr($name, 6);
        }
        $name = str_replace('/', ':', $name);
        return Horde_String::convertCharset($name, 'UTF7-IMAP');
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
    function getSubpath($name)
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
    function setName($name)
    {
        $name = str_replace(':', '/', $name);
        if (substr($name, 0, 5) != 'user/' && substr($name, 0, 7) != 'shared.') {
            $name = 'INBOX/' . $name;
        }
        return Horde_String::convertCharset($name, Horde_Nls::getCharset(), 'UTF7-IMAP');
    }
}