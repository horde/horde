<?php

class Horde_Kolab_Storage_Namespace_Element_Private
extends Horde_Kolab_Storage_Namespace_Element
{
    /**
     * Return the type of this namespace (private, other, or shared).
     *
     * @return string The type.
     */
    public function getType()
    {
        return Horde_Kolab_Storage_Namespace::PRIV;
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
        return Horde_Kolab_Storage_Namespace::PRIV;
    }
}