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
}