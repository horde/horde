<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace_Fixed:: implements the default IMAP
 * namespaces on the Kolab server.
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
 * The Horde_Kolab_Storage_Folder_Namespace_Fixed:: implements the default IMAP
 * namespaces on the Kolab server.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Storage_Folder_Namespace_Fixed
extends  Horde_Kolab_Storage_Folder_Namespace
{
    /**
     * Constructor.
     */
    public function __construct($user)
    {
        $this->user = $user;
        parent::__construct($this->_initializeData());
    }

    /**
     * Initialize the namespace elements.
     *
     * @return array The namespace elements.
     */
    private function _initializeData()
    {
        return array(
            new Horde_Kolab_Storage_Folder_Namespace_Element_Personal('INBOX/', '/', $this->user),
            new Horde_Kolab_Storage_Folder_Namespace_Element_Other('user/', '/', $this->user),
            new Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix('', '/', $this->user, 'shared.')
        );
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize($this->user);
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        $this->user = @unserialize($data);
        $this->initialize($this->_initializeData());
    }
}