<?php

class Horde_Kolab_Storage_Folder_Namespace_Element_SharedWithPrefix
extends Horde_Kolab_Storage_Folder_Namespace_Element_Shared
{
    /**
     * The prefix to hide when referencing this namespace.
     *
     * @var string
     */
    protected $_prefix;

    /**
     * Constructor.
     *
     * @param string $name      The prefix identifying this namespace.
     * @param string $delimiter The delimiter used for this namespace.
     * @param string $prefix The prefix to hide.
     */
    public function __construct($name, $delimiter, $prefix)
    {
        parent::__construct($name, $delimiter);
        $this->_prefix = $prefix;
    }

    /**
     * Return an array describing the path elements of the folder.
     *
     * @param string $name The name of the folder.
     *
     * @return array The path elements.
     */
    protected function _subpath($name)
    {
        $path = parent::_subpath($name);
        if (strpos($path[0], $this->_prefix) === 0) {
            $path[0] = substr($path[0], strlen($this->_prefix));
        }
        return $path;
    }
}