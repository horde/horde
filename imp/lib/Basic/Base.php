<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * Base class for basic view pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
abstract class IMP_Basic_Base
{
    /**
     * @var IMP_Indices_Mailbox
     */
    public $indices;

    /**
     * @var string
     */
    public $output;

    /**
     * @var string
     */
    public $title;

    /**
     * @var Horde_Variables
     */
    public $vars;

    /**
     */
    public function __construct(Horde_Variables $vars)
    {
        global $page_output;

        $this->vars = $vars;

        $this->indices = new IMP_Indices_Mailbox($vars);

        $page_output->addLinkTag(array(
            'href' => IMP_Basic_Search::url(),
            'rel' => 'search',
            'type' => null
        ));

        $mimecss = new Horde_Themes_Element('mime.css');
        $page_output->addStylesheet($mimecss->fs, $mimecss->uri);

        $this->_init();
    }

    /**
     */
    public function render()
    {
        echo $this->output;
    }

    /**
     */
    public function status()
    {
        Horde::startBuffer();
        IMP::status();
        return Horde::endBuffer();
    }

    /**
     */
    abstract protected function _init();

    /**
     */
    static public function url(array $opts = array())
    {
    }

}
