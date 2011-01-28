<?php
/**
 * Maps a single Horde group permission element to a Kolab_Storage ACL.
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
 * Maps a single Horde group permission element to a Kolab_Storage ACL.
 *
 * Copyright 2006-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Folder_Permission_Element_Group
extends Horde_Kolab_Storage_Folder_Permission_Element
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
     * @param integer $permission  The folder permission as provided by Horde.
     * @param string $id           The group id.
     * @param Horde_Group $groups  The horde group handler.
     */
    public function __construct($permission, $id, Horde_Group $groups)
    {
        $this->_horde_id = $id;
        $this->_kolab_id = 'group:' . $groups->getGroupName($id);
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
