<?php
class Horde_Kolab_Storage_Stub_FactoryQuery
implements Horde_Kolab_Storage_Query
{
    public $called = false;
    public $synchronized = false;

    /**
     * Constructor.
     *
     * @param Horde_Kolab_Storage_Queriable $queriable The queriable object.
     */
    public function __construct(Horde_Kolab_Storage_Queriable $queriable)
    {
    }

    /**
     * Synchronize the query data with the information from the backend.
     *
     * @return NULL
     */
    public function synchronize()
    {
        $this->synchronized = true;
    }
}