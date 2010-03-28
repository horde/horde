<?php
/**
 * @TODO: Can't figure out what this was meant to do, and in fact the
 * original Z-Push code that instantiates the Z-Push version of this class
 * called methods that don't exist here.
 *
 *  Looks like it's just a sort of placeholder class??
 */
class Horde_ActiveSync_ContentsCache
{
    public function ImportMessageChange($message) { return true; }
    public function ImportMessageDeletion($message) { return true; }
    public function ImportMessageReadFlag($message) { return true; }
    public function ImportMessageMove($message) { return true; }
}