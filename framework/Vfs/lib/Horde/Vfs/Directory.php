<?php
/**
 * @category Horde
 * @package  Horde_Vfs
 */

/**
 * @category Horde
 * @package  Horde_Vfs
 */
class Horde_Vfs_Directory implements IteratorAggregate
{
    /**
     * @return Horde_Vfs_DirectoryIterator
     */
    public function getIterator()
    {
        return new Horde_Vfs_DirectoryIterator($this);
    }

}
