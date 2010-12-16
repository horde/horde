<?php
/**
 * The Horde_Kolab_Storage_Folder_Namespace_Config:: allows to configure the available
 * IMAP namespaces on the Kolab server.
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
 * The Horde_Kolab_Storage_Folder_Namespace_Config:: allows to configure the available
 * IMAP namespaces on the Kolab server.
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
class Horde_Kolab_Storage_Folder_Namespace_Config
extends  Horde_Kolab_Storage_Folder_Namespace
{
    /**
     * Constructor.
     */
    public function __construct(array $configuration)
    {
        parent::__construct();
        foreach ($configuration as $element) {
            if ($element['type'] == Horde_Kolab_Storage_Folder_Namespace::SHARED
                && isset($element['prefix'])) {
                $namespace_element = new Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix(
                    $element['name'], $element['delimiter'], $element['prefix']
                );
                $this->_sharedPrefix = $element['prefix'];
            } else {
                $class = 'Horde_Kolab_Storage_Folder_Namespace_Element_' . ucfirst($element['type']);
                $namespace_element = new $class($element['name'], $element['delimiter']);
            }
            if (empty($element['name'])) {
                $this->_any = $namespace_element;
            } else {
                $this->_namespaces[] = $namespace_element;
            }
            if (isset($element['add'])) {
                $this->_primaryPersonalNamespace = $namespace_element;
            }
        }
    }
}