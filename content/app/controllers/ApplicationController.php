<?php
/**
 * @category Horde
 * @package  Content
 */

/**
 * @category Horde
 * @package  Content
 */
class Content_ApplicationController extends Horde_Controller_Base
{
    /**
     */
    protected function _initializeApplication()
    {
        $CONTENT_DIR = __DIR__ . '/../';
        $this->tagger = $GLOBALS['injector']->getInstance('Content_Tagger');
    }
}
