<?php
/**
 * Copyright 1999-2014 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */

/**
 * IMP base class.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @author    Jon Parise <jon@horde.org>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 1999-2014 Horde LLC
 * @license   http://www.horde.org/licenses/gpl GPL
 * @package   IMP
 */
class IMP
{
    /* Encrypt constants. */
    const ENCRYPT_NONE = 'encrypt_none';

    /* IMP Mailbox view constants. */
    const MAILBOX_START_FIRSTUNSEEN = 1;
    const MAILBOX_START_LASTUNSEEN = 2;
    const MAILBOX_START_FIRSTPAGE = 3;
    const MAILBOX_START_LASTPAGE = 4;

    /* Folder list actions. */
    const NOTEPAD_EDIT = "notepad\0";
    const TASKLIST_EDIT = "tasklist\0";

    /* Initial page constants. */
    const INITIAL_FOLDERS = "initial\0folders";

    /* Sorting constants. */
    const IMAP_SORT_DATE = 100;

    /**
     * Filters a string, if requested.
     *
     * @param string $text  The text to filter.
     *
     * @return string  The filtered text (if requested).
     */
    public static function filterText($text)
    {
        global $injector, $prefs;

        if ($prefs->getValue('filtering') && strlen($text)) {
            try {
                return $injector->getInstance('Horde_Core_Factory_TextFilter')->filter(
                    $text,
                    'words',
                    $injector->getInstance('Horde_Core_Hooks')->callHook('msg_filter', 'imp')
                );
            } catch (Horde_Exception_HookNotSet $e) {}
        }

        return $text;
    }

    /**
     *
     * @param integer $size  The byte size of data.
     *
     * @return string  A formatted size string.
     */
    public static function sizeFormat($size)
    {
        return ($size >= 1048576)
            ? sprintf(_("%s MB"), self::numberFormat($size / 1048576, 1))
            : sprintf(_("%s KB"), self::numberFormat($size / 1024, 0));
    }

    /**
     * Workaround broken number_format() prior to PHP 5.4.0.
     *
     * @param integer $number    Number to format.
     * @param integer $decimals  Number of decimals to display.
     *
     * @return string  See number_format().
     */
    public static function numberFormat($number, $decimals)
    {
        $localeinfo = Horde_Nls::getLocaleInfo();

        return str_replace(
            array('X', 'Y'),
            array($localeinfo['decimal_point'], $localeinfo['thousands_sep']),
            number_format($decimals ? $number : ceil($number), $decimals, 'X', 'Y')
        );
    }

    /**
     * Wrapper around Horde_Mail_Rfc822#parseAddressList(). Ensures all
     * addresses have a default mail domain appended.
     *
     * @param mixed $in    The address string or an address list object.
     * @param array $opts  Options to override the default.
     *
     * @return Horde_Mail_Rfc822_List  See Horde_Mail_Rfc822#parseAddressList().
     *
     * @throws Horde_Mail_Exception
     */
    public static function parseAddressList($in, array $opts = array())
    {
        $md = $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->config->maildomain;

        if ($in instanceof Horde_Mail_Rfc822_List) {
            $res = clone $in;
            foreach ($res->raw_addresses as $val) {
                if (is_null($val->host)) {
                    $val->host = $md;
                 }
            }
        } else {
            $rfc822 = $GLOBALS['injector']->getInstance('Horde_Mail_Rfc822');
            $res = $rfc822->parseAddressList($in, array_merge(array(
                'default_domain' => $md,
                'validate' => false
            ), $opts));
        }

        $res->setIteratorFilter(Horde_Mail_Rfc822_List::HIDE_GROUPS);

        return $res;
    }

    /**
     * Returns the initial page for IMP.
     *
     * @return object  Object with the following properties:
     *   - mbox (IMP_Mailbox)
     *   - url (Horde_Url)
     */
    public static function getInitialPage()
    {
        global $injector, $prefs, $registry;

        $init_url = $prefs->getValue('initial_page');
        if (!$init_url ||
            !$injector->getInstance('IMP_Factory_Imap')->create()->access(IMP_Imap::ACCESS_FOLDERS)) {
            $init_url = 'INBOX';
        }

        if ($init_url == IMP::INITIAL_FOLDERS) {
            $mbox = null;
        } else {
            $mbox = IMP_Mailbox::get($init_url);
            if (!$mbox->exists) {
                $mbox = IMP_Mailbox::get('INBOX');
            }
        }

        $result = new stdClass;
        $result->mbox = $mbox;

        switch ($registry->getView()) {
        case Horde_Registry::VIEW_BASIC:
            $result->url = is_null($mbox)
                ? IMP_Basic_Folders::url()
                : $mbox->url('mailbox');
            break;

        case Horde_Registry::VIEW_DYNAMIC:
            $result->url = IMP_Dynamic_Mailbox::url(array(
                'mailbox' => is_null($mbox) ? 'INBOX' : $mbox
            ));
            break;

        case Horde_Registry::VIEW_MINIMAL:
            $result->url = is_null($mbox)
                ? IMP_Minimal_Folders::url()
                : IMP_Minimal_Mailbox::url(array('mailbox' => $mbox));
            break;

        case Horde_Registry::VIEW_SMARTMOBILE:
            $result->url = is_null($mbox)
                ? Horde::url('smartmobile.php', true)
                : $mbox->url('mailbox');
            break;
        }

        return $result;
    }

}
