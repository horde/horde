<?php
/**
 * @package Horde_Rdo
 */

/**
 * @package Horde_Rdo
 */
class Horde_Rdo_Lens extends Horde_Lens implements IteratorAggregate
{
    /**
     * Implement the IteratorAggregate pattern. When a single Rdo
     * object is iterated over, we return an iterator that loops over
     * each property of the object.
     *
     * @return ArrayIterator The Iterator instance.
     */
    public function getIterator()
    {
        return new Horde_Rdo_Iterator($this);
    }

}
