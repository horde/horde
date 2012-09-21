<?php
/**
 * Wicked Base Class.
 *
 * Copyright 2003-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author  Tyler Colbert <tyler@colberts.us>
 * @package Wicked
 */
class Wicked
{
    /** Display mode. */
    const MODE_DISPLAY = 0;

    /** The edit screen. */
    const MODE_EDIT = 1;

    /** Page can be removed. */
    const MODE_REMOVE = 2;

    /** Display the page history. */
    const MODE_HISTORY = 3;

    /** Diff two versions of the page. */
    const MODE_DIFF = 4;

    /** Page can be locked. */
    const MODE_LOCKING = 7;

    /** Page can be unlocked. */
    const MODE_UNLOCKING = 8;

    /** The ability to add a page. */
    const MODE_CREATE = 9;

    /** Raw content mode. */
    const MODE_CONTENT = 10;

    /** Like display, but for a block. */
    const MODE_BLOCK = 11;

    /** Our wiki word regexp (needed many places).
       "(!?" .                       // START WikiPage pattern (1)
       "[A-Z\xc0-\xde]" .            // 1 upper
       "[A-Za-z0-9\xc0-\xfe]*" .     // 0+ alpha or digit
       "[a-z0-9\xdf-\xfe]+" .        // 1+ lower or digit
       "\/?" .                       // 0/1 slash
       "[A-Z\xc0-\xde]" .            // 1 upper
       "[A-Za-z0-9\xc0-\xfe\/]*" .   // 0+ or more alpha or digit or slash
       ")" .                         // END WikiPage pattern (/1)
       "((\#" .                      // START Anchor pattern (2)(3)
       "[A-Za-z0-9\xc0-\xfe]" .      // 1 alpha
       "(" .                         // start sub pattern (4)
       "[-_A-Za-z0-9\xc0-\xfe:.]*" . // 0+ dash, alpha, digit, underscore,
                                     // colon, dot
       "[-_A-Za-z0-9\xc0-\xfe]" .    // 1 dash, alpha, digit, or underscore
       ")?)?)");                     // end subpatterns (/4)(/3)(/2)
     */
    const REGEXP_WIKIWORD = "(!?[A-Z\xc0-\xde][A-Za-z0-9\xc0-\xfe]*[a-z0-9\xdf-\xfe]+\/?[A-Z\xc0-\xde][A-Za-z0-9\xc0-\xfe\/]*)((\#[A-Za-z0-9\xc0-\xfe]([-_A-Za-z0-9\xc0-\xfe:.]*[-_A-Za-z0-9\xc0-\xfe])?)?)";

    /** Where we store our attachments in VFS. */
    const VFS_ATTACH_PATH = '.horde/wicked/attachments';

    /**
     * Puts together the URL to a Wicked page. Uses mod_rewrite or GET
     * style URLs depending on configuration.
     *
     * @param string $page             The name of the page to target.
     * @param boolean $full            @see Horde::url()
     * @param integer $append_session  @see Horde::url()
     *
     * @return Horde_Url  The URL of $page.
     */
    public static function url($page, $full = false, $append_session = 0)
    {
        if ($GLOBALS['conf']['urls']['pretty'] == 'rewrite') {
            $script = str_replace('%2F', '/', urlencode($page));
        } else {
            $script = Horde::url('display.php')->add('page', $page);
        }

        $url = Horde::url($script, $full, array('append_session' => $append_session));
        if (!$full) {
            $url->url = preg_replace('|^([a-zA-Z][a-zA-Z0-9+.-]{0,19})://[^/]*|', '', $url->url);
        }

        return $url;
    }

    /**
     * Mails a notification message after encoding the headers and adding the
     * standard username/time line.
     *
     * @param string $message  The message text to send out.
     * @param array $headers   Additional headers to add to the email.
     */
    public static function mail($message, $headers = array())
    {
        global $conf, $registry;

        /* Make sure there's a place configured to send the email. */
        if (empty($conf['wicked']['notify_address'])) {
            return;
        }

        if ($GLOBALS['registry']->getAuth()) {
            $prefix = $GLOBALS['registry']->getAuth();
        } else {
            $prefix = 'guest [' . $_SERVER['REMOTE_ADDR'] . ']';
        }
        $message = $prefix . '  ' . date('r') . "\n\n" . $message;

        /* In case we don't get a user's email address to send the
         * notification from, what should we fall back to for the From:
         * header? */
        $default_from_addr = !empty($conf['wicked']['guest_address']) ?
            $conf['wicked']['guest_address'] :
            $conf['wicked']['notify_address'];
        if ($GLOBALS['registry']->getAuth()) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
            $from = $identity->getValue('fullname');
            if (empty($from)) {
                $from = $registry->get('name');
            }
            $from_addr = $identity->getValue('from_addr');
            if (empty($from_addr)) {
                $from_addr = $default_from_addr;
            }
        } else {
            $from = $registry->get('name') . ' Guest';
            $from_addr = $default_from_addr;
        }

        $mail = new Horde_Mime_Mail(array(
            'body' => $message,
            'To' => $conf['wicked']['notify_address'],
            'From' => $from . '<' . $from_addr . '>',
            'User-Agent' => 'Wicked ' . $GLOBALS['registry']->getVersion(),
            'Precedence' => 'bulk',
            'Auto-Submitted' => 'auto-replied'));
        foreach (array_keys($headers) as $hkey) {
            $mail->addHeader($hkey, $headers[$hkey]);
        }
        try {
            $mail->send($GLOBALS['injector']->getInstance('Horde_Mail'));
        } catch (Horde_Mime_Exception $e) {
            $GLOBALS['notification']->push($e);
        }
    }

    /**
     * Generate a CAPTCHA string.
     *
     * @param boolean $new  If true, a new CAPTCHA is created and returned.
     *                      The current, to-be-confirmed string otherwise.
     *
     * @return string  A CAPTCHA string.
     */
    public static function getCAPTCHA($new = false)
    {
        global $session;

        if ($new || !$session->get('wicked', 'captcha')) {
            $captcha = '';
            for ($i = 0; $i < 5; ++$i) {
                $captcha .= chr(rand(65, 90));
            }
            $session->set('wicked', 'captcha', $captcha);
        }

        return $session->get('wicked', 'captcha');
    }

    /**
     * Returns the user name that is used for locking, either the current user
     * or the current IP address.
     *
     * @return string  The user name used for locking.
     */
    public static function lockUser()
    {
        return $GLOBALS['registry']->getAuth() ? $GLOBALS['registry']->getAuth() : $GLOBALS['browser']->getIPAddress();
    }

}
