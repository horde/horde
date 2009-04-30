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
        $auth = Auth::getAuth();
        if (!empty($auth)) {
            header('Location: ' . $this->urlFor(array('controller' => 'index', 'action' => 'index')));
            exit;
        }

        $this->title = _("Login");

        $this->post = $this->urlFor(array('controller' => 'index', 
                                          'action' => 'login'));

        if (isset($_POST['horde_user']) && isset($_POST['horde_pass'])) {
            /* Destroy any existing session on login and make sure to use a
             * new session ID, to avoid session fixation issues. */
            Horde::getCleanSession();
            if ($this->koward->auth->authenticate(Util::getPost('horde_user'),
                                                  array('password' => Util::getPost('horde_pass')))) {
                $entry = sprintf('Login success for %s [%s] to Horde',
                                 Auth::getAuth(), $_SERVER['REMOTE_ADDR']);
                Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);
                header('Location: ' . $this->urlFor(array('controller' => 'index', 'action' => 'index')));
                exit;
            } else {
                $entry = sprintf('FAILED LOGIN for %s [%s] to Horde',
                                 Util::getFormData('horde_user'), $_SERVER['REMOTE_ADDR']);
                Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_ERR);
            }
        }

        if ($reason = $this->koward->auth->getLogoutReasonString()) {
            $this->koward->notification->push(str_replace('<br />', ' ', $reason), 'horde.message');
        }

    }

    public function logout()
    {
        $entry = sprintf('User %s [%s] logged out of Horde',
                         Auth::getAuth(), $_SERVER['REMOTE_ADDR']);
        Horde::logMessage($entry, __FILE__, __LINE__, PEAR_LOG_NOTICE);
        Auth::clearAuth();
        @session_destroy();

        header('Location: ' . $this->urlFor(array('controller' => 'index', 'action' => 'login')));
        exit;
    }
}