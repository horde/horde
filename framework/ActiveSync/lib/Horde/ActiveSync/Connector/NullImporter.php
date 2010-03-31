<?php
/**
 * 
 */
class Horde_ActiveSync_Connector_NullImporter
{
    public function ImportMessageChange($id, $message) { return true; }
    public function ImportMessageDeletion($id) { return true; }
    public function ImportMessageReadFlag($id, $flags) { return true; }
    public function ImportMessageMove($id, $newfolder) { return true; }
}