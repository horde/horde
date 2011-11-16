<?php
/**
 * The Ansel_Queue_ProcessThumbs class provides a queue task for generating
 * thumbnails.
 *
 * Copyright 2011 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Michael J Rubinsky <mrubinsk@horde.org>
 * @package Ansel
 */

 class Ansel_Queue_ProcessThumbs implements Horde_Queue_Task
 {

    protected $_images;

    /**
     * Const'r
     *
     * @param array $images  An array of image ids to process.
     */
    public function __construct(array $images)
    {
        $this->_images = $images;
    }

    /**
     * Run the task. Currently generates:
     *  - screen image
     *  - mini image (using the square pref. if set)
     *  - thumb (currently, only the image's gallery's configured style)
     *
     */
     public function run()
     {
         foreach ($this->_images as $id) {
            try {
                $image = $GLOBALS['injector']
                    ->getInstance('Ansel_Storage')
                    ->getImage($id);
                $image->createView('screen');
                $image->createView('thumb');
                $image->createView('mini');
            } catch (Ansel_Exception $e) {
                Horde::logMessage($e->getMessage, 'ERR');
            }
         }
     }

 }