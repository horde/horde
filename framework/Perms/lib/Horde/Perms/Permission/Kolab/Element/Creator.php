<?php
/**
 * Maps a single Horde creator permission element to a Kolab_Storage ACL.
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
 * Maps a single Horde creator permission element to a Kolab_Storage ACL.
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
class Horde_Perms_Permission_Kolab_Element_Creator
extends Horde_Perms_Permission_Kolab_Element
{
    /**
     * The creator id.
     *
     * @var string
     */
    private $_creator;

    /**
     * Constructor.
     *
     * @param int    $permission The folder permission as provided by Horde.
     * @param string $creator    The folder owner.
     */
    public function __construct($permission, $creator)
    {
        $this->_creator = $creator;
        parent::__construct($permission);
    }

    /**
     * Convert the Horde_Perms:: mask to a Acl string.
     *
     * @return string The ACL string.
     */
    public function fromHorde()
    {
        return 'a' . $this->convertMaskToAcl();
    }

    /**
     * Get the Kolab_Storage ACL id for this permission.
     *
     * @return string The ACL string.
     */
    public function getId()
    {
        return $this->_creator;
    }
}
