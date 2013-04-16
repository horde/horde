<?php
/**
 * Copyright 2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Base class for basic view pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */
abstract class Ingo_Basic_Base
{
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
        $this->vars = $vars;

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
        global $notification;

        Horde::startBuffer();
        $notification->notify(array(
            'listeners' => array('status', 'audio')
        ));
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
