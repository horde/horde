<?php
/**
 * Copyright 2013-2015 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
 * @license   http://www.horde.org/licenses/apache ASL
 * @package   Ingo
 */

/**
 * Base class for basic view pages.
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2013-2015 Horde LLC
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
     * Validates an IMAP mailbox provided by user input.
     *
     * @param string $name  The form name of the input.
     *
     * @return string  The IMAP mailbox name.
     * @throws Horde_Exception
     */
    public function validateMbox($name)
    {
        global $registry;

        $new_mbox = $this->vars->get($name . '_new');

        if (strlen($new_mbox)) {
            if ($registry->hasMethod('mail/createMailbox') &&
                $registry->call('mail/createMailbox', array($new_mbox))) {
                return $new_mbox;
            }
        } elseif (strlen($this->vars->$name)) {
            return $this->vars->$name;
        }

        throw new Ingo_Exception(_("Could not validate IMAP mailbox."));
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
     * Assert category.
     *
     * @param integer $type  Category type.
     * @param string $label  Category label.
     */
    protected function _assertCategory($type, $label)
    {
        global $notification, $session;

        if (!in_array($type, $session->get('ingo', 'script_categories'))) {
            $notification->push(
                sprintf(
                    _("%s is not supported in the current filtering driver."),
                    $label
                ),
                'horde.error'
            );
            Ingo_Basic_Filters::url()->redirect();
        }
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
