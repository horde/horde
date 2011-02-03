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
     * The namespace configuration.
     *
     * @var array
     */
    protected $configuration;

    /**
     * Constructor.
     *
     * @param string $user          The current user.
     * @param array  $configuration The namespace configuration.
     */
    public function __construct($user, array $configuration)
    {
        $this->user = $user;
        $this->configuration = $configuration;
        parent::__construct($this->_initializeData());
    }

    /**
     * Initialize the namespace elements.
     *
     * @return array The namespace elements.
     */
    private function _initializeData()
    {
        $namespace = array();
        foreach ($this->configuration as $element) {
            if ($element['type'] == Horde_Kolab_Storage_Folder_Namespace::SHARED
                && isset($element['prefix'])) {
                $namespace_element = new Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix(
                    $element['name'], $element['delimiter'], $this->user, $element['prefix']
                );
            } else {
                $class = 'Horde_Kolab_Storage_Folder_Namespace_Element_' . ucfirst($element['type']);
                if (!class_exists($class)) {
                    throw new Horde_Kolab_Storage_Exception(
                        sprintf('Unkown namespace type "%s"', $element['type'])
                    );
                }
                $namespace_element = new $class($element['name'], $element['delimiter'], $this->user);
            }
            $namespaces[] = $namespace_element;
        }
        return $namespaces;
    }

    /**
     * Serialize this object.
     *
     * @return string  The serialized data.
     */
    public function serialize()
    {
        return serialize(array($this->user, $this->configuration));
    }

    /**
     * Reconstruct the object from serialized data.
     *
     * @param string $data  The serialized data.
     */
    public function unserialize($data)
    {
        list($this->user, $this->configuration) = @unserialize($data);
        $this->initialize($this->_initializeData());
    }
}