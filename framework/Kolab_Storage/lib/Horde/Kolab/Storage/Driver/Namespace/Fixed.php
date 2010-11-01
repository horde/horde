<?php
/**
 * The Horde_Kolab_Storage_Driver_Namespace_Fixed:: implements the default IMAP
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
 * The Horde_Kolab_Storage_Driver_Namespace_Fixed:: implements the default IMAP
 * namespaces on the Kolab server.
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
class Horde_Kolab_Storage_Driver_Namespace_Fixed
extends  Horde_Kolab_Storage_Driver_Namespace
{
    /**
     * Indicates the personal namespace that the class will use to create new
     * folders.
     *
     * @var string
     */
    protected $_primaryPersonalNamespace = 'INBOX';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $personal = new Horde_Kolab_Storage_Driver_Namespace_Element_Personal('INBOX/', '/');
        $other    = new Horde_Kolab_Storage_Driver_Namespace_Element_Other('user/', '/');
        $shared   = new Horde_Kolab_Storage_Driver_Namespace_Element_SharedWithPrefix('', '/', 'shared.');

        $this->_namespaces = array($personal, $other);
        $this->_any = $shared;
        $this->_primaryPersonalNamespace = $personal;
        $this->_sharedPrefix = 'shared.';
    }
}