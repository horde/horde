<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace_Config:: allows to use the information from
 * the IMAP NAMESPACE command to identify the IMAP namespaces on the Kolab
 * server.
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
 * The Horde_Kolab_Storage_Folder_Namespace_Config:: allows to use the information from
 * the IMAP NAMESPACE command to identify the IMAP namespaces on the Kolab
 * server.
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
class Horde_Kolab_Storage_Folder_Namespace_Imap
extends  Horde_Kolab_Storage_Folder_Namespace_Config
{
    /**
     * Constructor.
     */
    public function __construct(array $namespaces, array $configuration = array())
    {
        $c = array();
        foreach ($namespaces as $namespace) {
            if (in_array($namespace['name'], array_keys($configuration))) {
                $namespace = array_merge($namespace, $configuration[$namespace['name']]);
            }
            $c[] = $namespace;
        }
        parent::__construct($c);
    }
}