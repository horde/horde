<?php
class Horde_Kolab_Storage_Stub_DataQuery
implements Horde_Kolab_Storage_Data_Query
{
    public $synchronized = false;

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