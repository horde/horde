<?php
/**
 * Defines the AJAX interface for Ansel.
 *
 * Copyright 2010-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */
class Ansel_Ajax_Application extends Horde_Core_Ajax_Application
{
    /**
     * Determines if notification information is sent in response.
     *
     * @var boolean
     */
    public $notify = true;

    /**
     * Obtain a gallery
     *
     * @return mixed  False on failure, object representing the gallery with
     *                the following structure:
     * @see Ansel_Gallery::toJson()
     */
    public function getGallery()
    {
        $id = $this->_vars->id;
        try {
            return $GLOBALS['injector']->getInstance('Ansel_Storage')->getGallery($id)->toJson(true);
        } catch (Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }
    }

}
