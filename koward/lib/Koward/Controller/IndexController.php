<?php
/**
 * @package Koward
 */

/**
 * @package Koward
 */
class IndexController extends Koward_Controller_Application
{
    protected $auth_handler = 'login';

    public function index()
    {
        $this->title = _("Index");
    }

    public function login()
    {
        $auth = $GLOBALS['registry']->getAuth();
        if (!empty($auth)) {
            $this->urlFor(array('controller' => 'index', 'action' => 'index'))
                ->redirect();
        }

        $this->title = _("Login");

        $this->post = $this->urlFor(array('controller' => 'index',
                                          'action' => 'login'));

        if (isset($_POST['horde_user']) && isset($_POST['horde_pass'])) {
            /* Destroy any existing session on login and make sure to use a
             * new session ID, to avoid session fixation issues. */
            $GLOBALS['registry']->getCleanSession();
            if ($this->koward->auth->authenticate(Horde_Util::getPost('horde_user'),
                                                  array('password' => Horde_Util::getPost('horde_pass')))) {
                $entry = sprintf('Login success for %s [%s] to Horde',
                                 $GLOBALS['registry']->getAuth(), $_SERVER['REMOTE_ADDR']);
                Horde::logMessage($entry, 'NOTICE');

                $type = $this->koward->getType();
                if (!empty($type) && isset($this->koward->objects[$type]['default_view'])) {
                    $url = $this->urlFor($this->koward->objects[$type]['default_view']);
                } else if (isset($this->koward->conf['koward']['default_view'])) {
                    $url = $this->urlFor($this->koward->conf['koward']['default_view']);
                } else {
                    $url = $this->urlFor(array('controller' => 'index', 'action' => 'index'));
                }
                $url->redirect();
            }
            $entry = sprintf('FAILED LOGIN for %s [%s] to Horde',
                             Horde_Util::getFormData('horde_user'), $_SERVER['REMOTE_ADDR']);
            Horde::logMessage($entry, 'ERR');
        }

        if ($reason = $this->koward->auth->getLogoutReasonString()) {
            $this->koward->notification->push(str_replace('<br />', ' ', $reason), 'horde.message');
        }

    }

    public function logout()
    {
        $entry = sprintf('User %s [%s] logged out of Horde',
                         $GLOBALS['registry']->getAuth(), $_SERVER['REMOTE_ADDR']);
        Horde::logMessage($entry, 'NOTICE');
        $GLOBALS['registry']->clearAuth();

        $this->urlFor(array('controller' => 'index', 'action' => 'login'))
            ->redirect();
    }
}
