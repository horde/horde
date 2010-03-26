<?php
/**
 * This simply collects all changes so that they can be retrieved later, for
 * statistics gathering for example
 */
/**
 * File      :   memimporter.php
 * Project   :   Z-Push
 * Descr     :   Classes that collect changes
 *
 * Created   :   01.10.2007
 *
 * © Zarafa Deutschland GmbH, www.zarafaserver.de
 * This file is distributed under GPL v2.
 * Consult LICENSE file for details
 */
class Horde_ActiveSync_HierarchyCache
{
    public $changed;
    public $deleted;
    public $count;

    public function __construct()
    {
        $this->changed = array();
        $this->deleted = array();
        $this->count = 0;

        return true;
    }

    public function FolderChange($folder)
    {
        array_push($this->changed, $folder);
        $this->count++;

        return true;
    }

    public function FolderDeletion($id)
    {
        array_push($this->deleted, $id);
        $this->count++;

        return true;
    }
}