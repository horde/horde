<?php
/**
 * Binder for Ansel_Storage
 *
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  Ansel
 */
class Ansel_Injector_Binder_Storage Implements Horde_Injector_Binder
{
    public function create(Horde_Injector $injector)
    {
        return new Ansel_Injector_Factory_Storage($injector);
    }

    /**
     */
    public function equals(Horde_Injector_Binder $binder)
    {
        return false;
    }
}