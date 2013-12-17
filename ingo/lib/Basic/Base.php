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
    const INGO_TOKEN = 'ingo_token';

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
     * Add the ingo action token to a URL.
     *
     * @param Horde_Url $url  URL.
     *
     * @return Horde_Url  URL with token added (for chainable calls).
     */
    protected function _addToken(Horde_Url $url)
    {
        global $session;

        return $url->add(self::INGO_TOKEN, $session->getToken());
    }

    /**
     * Check token.
     *
     * @param array $actions  The list of actions that require token checking.
     *
     * @return string  The verified action ID.
     */
    protected function _checkToken($actions)
    {
        global $notification, $session;

        $actionID = $this->vars->actionID;

        /* Run through the action handlers */
        if (!empty($actions) &&
            strlen($actionID) &&
            in_array($actionID, $actions)) {
            try {
                $session->checkToken($this->vars->get(self::INGO_TOKEN));
            } catch (Horde_Exception $e) {
                $notification->push($e);
                $actionID = null;
            }
        }

        return $actionID;
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
