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
class Horde_Kolab_Storage_Folder_Namespace_Config
extends  Horde_Kolab_Storage_Folder_Namespace
{
    /**
     * Constructor.
     *
     * @param string $user          The current user.
     * @param array  $configuration The namespace configuration.
     */
    public function __construct($user, array $configuration)
    {
        $namespace = array();
        foreach ($configuration as $element) {
            if ($element['type'] == Horde_Kolab_Storage_Folder_Namespace::SHARED
                && isset($element['prefix'])) {
                $namespace_element = new Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix(
                    $element['name'], $element['delimiter'], $user, $element['prefix']
                );
            } else {
                $class = 'Horde_Kolab_Storage_Folder_Namespace_Element_' . ucfirst($element['type']);
                if (!class_exists($class)) {
                    throw new Horde_Kolab_Storage_Exception(
                        sprintf('Unkown namespace type "%s"', $element['type'])
                    );
                }
                $namespace_element = new $class($element['name'], $element['delimiter'], $user);
            }
            $namespaces[] = $namespace_element;
        }
        parent::__construct($namespaces);
    }
}