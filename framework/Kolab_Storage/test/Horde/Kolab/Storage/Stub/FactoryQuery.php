<?php
class Horde_Kolab_Storage_Stub_FactoryQuery
implements Horde_Kolab_Storage_Query
{
    public $called = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Queriable $queriable The queriable object.
     */
    public function __construct(Horde_Kolab_Storage_Queriable $queriable)
    {
    }

    /**
     * Inject the factory.
     *
     * @param Horde_Kolab_Storage_Factory $factory The factory.
     *
     * @return NULL
     */
    public function setFactory(Horde_Kolab_Storage_Factory $factory)
    {
        $this->called = true;
    }
}