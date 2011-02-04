<?php
class Horde_Kolab_Storage_Stub_FactoryQuery
implements Horde_Kolab_Storage_List_Query
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
     * Create a new folder.
     *
     * @param string $folder The path of the folder to create.
     * @param string $type   An optional type for the folder.
     *
     * @return NULL
     */
    public function createFolder($folder, $type = null)
    {
    }

    /**
     * Delete a folder.
     *
     * @param string $folder The path of the folder to delete.
     *
     * @return NULL
     */
    public function deleteFolder($folder)
    {
    }

    /**
     * Rename a folder.
     *
     * @param string $old The old path of the folder.
     * @param string $new The new path of the folder.
     *
     * @return NULL
     */
    public function renameFolder($old, $new)
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