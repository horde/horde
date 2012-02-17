<?php
/**
 * Maps a single Horde group permission element to a Kolab_Storage ACL.
 *
 * PHP version 5
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */

/**
 * Maps a single Horde group permission element to a Kolab_Storage ACL.
 *
 * Copyright 2006-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category Horde
 * @package  Perms
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @link     http://pear.horde.org/index.php?package=Perms
 */
class Horde_Perms_Permission_Kolab_Element_Group
extends Horde_Perms_Permission_Kolab_Element
{
    /**
     * The Horde group id.
     *
     * @var string
     */
    private $_horde_id;

    /**
     * The Kolab group id.
     *
     * @var string
     */
    private $_kolab_id;

    /**
     * Constructor.
     *
     * @param integer $permission       The folder permission as provided by
     *                                  Horde.
     * @param string $id                The group id.
     * @param Horde_Group_Base $groups  The horde group handler.
     */
    public function __construct($permission, $id, Horde_Group_Base $groups)
    {
        $this->_horde_id = $id;
        $this->_kolab_id = 'group:' . $groups->getName($id);
        parent::__construct($permission);
    }

    /**
     * Get the Kolab_Storage ACL id for this permission.
     *
     * @return string The ACL string.
     */
    public function getId()
    {
        return $this->_kolab_id;
    }

    /**
     * Unset the element in the provided permission array.
     *
     * @param array &$current The current permission array.
     *
     * @return NULL
     */
    public function unsetInCurrent(&$current)
    {
        unset($current['groups'][$this->_horde_id]);
    }
}
