<?php
/**
 * Connector class for when we don't care about/don't expect any PIM changes
 * to be present in the request. This is used in the GetItemEstimate request
 * where we have to generate the changes, but don't want to import any
 * changes to the backend.
 *
 *
 * @copyright 2010 The Horde Project (http://www.horde.org)
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Horde_ActiveSync
 */
class Horde_ActiveSync_Connector_NullImporter
{
    public function ImportMessageChange($id, $message) { return true; }
    public function ImportMessageDeletion($id) { return true; }
    public function ImportMessageReadFlag($id, $flags) { return true; }
    public function ImportMessageMove($id, $newfolder) { return true; }
}