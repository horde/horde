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
 * Copyright 2010 The Horde Project (http://www.horde.org/)
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
    public function __construct()
    {
        parent::__construct(
            array(
                new Horde_Kolab_Storage_Folder_Namespace_Element_Personal('INBOX/', '/'),
                new Horde_Kolab_Storage_Folder_Namespace_Element_Other('user/', '/'),
                new Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix('', '/', 'shared.')
            )
        );
    }
}