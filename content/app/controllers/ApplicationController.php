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
        $CONTENT_DIR = dirname(__FILE__) . '/../';

        $this->db = $GLOBALS['injector']->getInstance('db-writer');
        $context = array('dbAdapter' => $this->db);

        $this->typeManager = new Content_Types_Manager($context);
        $context['typeManager'] = $this->typeManager;

        $this->userManager = new Content_Users_Manager($context);
        $context['userManager'] = $this->userManager;

        $this->objectManager = new Content_Objects_Manager($context);
        $context['objectManager'] = $this->objectManager;

        $this->tagger = new Content_Tagger($context);
    }
}
