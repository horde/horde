<?php

class Horde_Kolab_Storage_Driver_Namespace_Element_Other
extends Horde_Kolab_Storage_Driver_Namespace_Element
{
    /**
     * Return the type of this namespace (personal, other, or shared).
     *
     * @return string The type.
     */
    public function getType()
    {
        return Horde_Kolab_Storage_Driver_Namespace::OTHER;
    }

    /**
     * Return the owner of a folder.
     *
     * @param string $name The name of the folder.
     *
     * @return string The owner of the folder.
     */
    public function getOwner($name)
    {
        $path = explode($this->_delimiter, $name);
        $user = $path[1];
        if (strpos($user, '@') === false) {
            $domain = strstr(array_pop($path), '@');
            if (!empty($domain)) {
                $user .= $domain;
            }
        }
        return Horde_Kolab_Storage_Driver_Namespace::OTHER . ':' . $user;
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
        array_shift($path);
        return $path;
    }
}