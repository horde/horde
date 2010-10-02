<?php
/**
 * Wicked Base Class.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
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
       "[A-Z\xc0-\xde]" .            // 1 upper
       "[A-Za-z0-9\xc0-\xfe]*" .     // 0+ or more alpha or digit
       ")" .                         // END WikiPage pattern (/1)
       "((\#" .                      // START Anchor pattern (2)(3)
       "[A-Za-z0-9\xc0-\xfe]" .      // 1 alpha
       "(" .                         // start sub pattern (4)
       "[-_A-Za-z0-9\xc0-\xfe:.]*" . // 0+ dash, alpha, digit, underscore,
                                     // colon, dot
       "[-_A-Za-z0-9\xc0-\xfe]" .    // 1 dash, alpha, digit, or underscore
       ")?)?)");                     // end subpatterns (/4)(/3)(/2)
     */
    const REGEXP_WIKIWORD = "(!?[A-Z\xc0-\xde][A-Za-z0-9\xc0-\xfe]*[a-z0-9\xdf-\xfe]+[A-Z\xc0-\xde][A-Za-z0-9\xc0-\xfe]*)((\#[A-Za-z0-9\xc0-\xfe]([-_A-Za-z0-9\xc0-\xfe:.]*[-_A-Za-z0-9\xc0-\xfe])?)?)";

    /** Where we store our attachments in VFS. */
    const WICKED_VFS_ATTACH_PATH = '.horde/wicked/attachments';

    /**
     * Puts together the URL to a Wicked page. Uses mod_rewrite or GET
     * style URLs depending on configuration.
     *
     * @param string $page             The name of the page to target.
     * @param boolean $full            @see Horde::url()
     * @param integer $append_session  @see Horde::url()
     *
     * @return string  The URL of $page.
     */
    public static function url($page, $full = false, $append_session = 0)
    {
        global $conf;

        if (!empty($conf['options']['use_mod_rewrite'])) {
            $script = str_replace('%2F', '/', urlencode($page));
        } else {
            $script = Horde_Util::addParameter('display.php', 'page', $page);
        }

        return Horde::url($script, $full, $append_session);
    }

    /**
     * Build Wicked's list of menu items.
     */
    public static function getMenu($returnType = 'object')
    {
        global $conf, $page;

        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);

        if (@count($conf['menu']['pages'])) {
            $pages = array('WikiHome' => _("_Home"),
                           'HowToUseWiki' => _("_Usage"),
                           'RecentChanges' => _("_Recent Changes"),
                           'AllPages' => _("_All Pages"));
            foreach ($conf['menu']['pages'] as $pagename) {
                /* Determine who we should say referred us. */
                $curpage = isset($page) ? $page->pageName() : null;
                $referrer = Horde_Util::getFormData('referrer', $curpage);

                /* Determine if we should depress the button. We have to do
                 * this on our own because all the buttons go to the same .php
                 * file, just with different args. */
                if (!strstr($_SERVER['PHP_SELF'], 'prefs.php') &&
                    $curpage === $pagename) {
                    $cellclass = 'current';
                } else {
                    $cellclass = '__noselection';
                }

                $url = Horde_Util::addParameter(Wicked::url($pagename), 'referrer', $referrer);
                $menu->add($url, $pages[$pagename], $pagename . '.png', null, null, null, $cellclass);
            }
        }

        if ($returnType == 'object') {
            return $menu;
        } else {
            return $menu->render();
        }
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

        $lc_time = setlocale(LC_TIME, 'C');
        $message = $prefix . '  ' . date('r') . "\n\n" . $message;
        setlocale(LC_TIME, $lc_time);

        /* In case we don't get a user's email address to send the
         * notification from, what should we fall back to for the From:
         * header? */
        $default_from_addr = !empty($conf['wicked']['guest_address']) ?
            $conf['wicked']['guest_address'] :
            $conf['wicked']['notify_address'];
        if ($GLOBALS['registry']->getAuth()) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();
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

        $mail = new Horde_Mime_Mail(array('body' => $message,
                                          'to' =>
                                          $conf['wicked']['notify_address'],
                                          'from' => $from . '<' . $from_addr
                                          . '>',
                                          'charset' => $GLOBALS['registry']->getCharset()));
        $mail->addHeader('User-Agent', 'Wicked ' . $GLOBALS['registry']->getVersion());
        $mail->addHeader('Precedence', 'bulk');
        $mail->addHeader('Auto-Submitted', 'auto-replied');
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
        if ($new || empty($_SESSION['wickedSession']['CAPTCHA'])) {
            $_SESSION['wickedSession']['CAPTCHA'] = '';
            for ($i = 0; $i < 5; $i++) {
                $_SESSION['wickedSession']['CAPTCHA'] .= chr(rand(65, 90));
            }
        }
        return $_SESSION['wickedSession']['CAPTCHA'];
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
