<?php
/**
 * Maps a single Horde permission element to a Kolab_Storage ACL.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Maps a single Horde permission element to a Kolab_Storage ACL.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Perms
 */
abstract class Horde_Perms_Permission_Kolab_Element
{
    /**
     * The permission.
     *
     * @var int
     */
    private $_permission;

    /**
     * Constructor.
     *
     * @param int $permission The folder permission as provided by Horde.
     */
    public function __construct($permission)
    {
        $this->_permission = $permission;
    }

    /**
     * Convert the Horde_Perms:: mask to a Acl string.
     *
     * @return string The ACL string.
     */
    public function fromHorde()
    {
        return $this->convertMaskToAcl();
    }

    /**
     * Get the Kolab_Storage ACL id for this permission.
     *
     * @return string The ACL string.
     */
    abstract public function getId();

    /**
     * Unset the element in the provided permission array.
     *
     * @param array &$current The current permission array.
     *
     * @return NULL
     */
    public function unsetInCurrent(&$current)
    {
        unset($current[$this->getId()]);
    }

    /**
     * Convert the a Horde_Perms:: mask to a Acl string.
     *
     * @return string The ACL
     */
    protected function convertMaskToAcl()
    {
        $result = '';
        if ($this->_permission & Horde_Perms::SHOW) {
            $result .= 'l';
        }
        if ($this->_permission & Horde_Perms::READ) {
            $result .= 'r';
        }
        if ($this->_permission & Horde_Perms::EDIT) {
            $result .= 'iswc';
        }
        if ($this->_permission & Horde_Perms::DELETE) {
            $result .= 'd';
        }

        return $result;
    }
}
