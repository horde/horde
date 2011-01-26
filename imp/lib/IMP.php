<?php
/**
 * IMP Base Class.
 *
 * Copyright 1999-2011 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @author   Jon Parise <jon@horde.org>
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.fsf.org/copyleft/gpl.html GPL
 * @package  IMP
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

    /* Sorting constants. */
    const IMAP_SORT_DATE = 100;

    /**
     * Storage place for an altered version of the current URL.
     *
     * @var string
     */
    static public $newUrl = null;

    /**
     * The current active mailbox (may be search mailbox).
     *
     * @var IMP_Mailbox
     */
    static public $mailbox;

    /**
     * The real IMAP mailbox of the current index.
     *
     * @var IMP_Mailbox
     */
    static public $thismailbox;

    /**
     * The IMAP UID.
     *
     * @var integer
     */
    static public $uid = '';

    /**
     * Returns the current view mode for IMP.
     *
     * @return string  Either 'dimp', 'imp', or 'mimp'.
     */
    static public function getViewMode()
    {
        return ($view = $GLOBALS['session']->get('imp', 'view'))
            ? $view
            : 'imp';
    }

    /**
     * Adds a contact to the user defined address book.
     *
     * @param string $newAddress  The contact's email address.
     * @param string $newName     The contact's name.
     *
     * @return string  A link or message to show in the notification area.
     * @throws Horde_Exception
     */
    static public function addAddress($newAddress, $newName)
    {
        global $registry, $prefs;

        if (empty($newName)) {
            $newName = $newAddress;
        }

        $result = $registry->call('contacts/import', array(array('name' => $newName, 'email' => $newAddress), 'array', $prefs->getValue('add_source')));

        $escapeName = @htmlspecialchars($newName, ENT_COMPAT, 'UTF-8');

        try {
            if ($contact_link = $registry->link('contacts/show', array('uid' => $result, 'source' => $prefs->getValue('add_source')))) {
                return Horde::link(Horde::url($contact_link), sprintf(_("Go to address book entry of \"%s\""), $newName)) . $escapeName . '</a>';
            }
        } catch (Horde_Exception $e) {}

        return $escapeName;
    }

    /**
     * Generates a select form input from a folder list. The &lt;select&gt;
     * and &lt;/select&gt; tags are NOT included in the output.
     *
     * @param array $options  Optional parameters:
     * <pre>
     * 'abbrev' - (boolean) Abbreviate long mailbox names by replacing the
     *            middle of the name with '...'?
     *            DEFAULT: Yes
     * 'filter' - (array) An array of mailboxes to ignore.
     *            DEFAULT: Display all
     * 'heading' - (string) The label for an empty-value option at the top of
     *             the list.
     *             DEFAULT: ''
     * 'inc_notepads' - (boolean) Include user's editable notepads in list?
     *                   DEFAULT: No
     * 'inc_tasklists' - (boolean) Include user's editable tasklists in list?
     *                   DEFAULT: No
     * 'inc_vfolder' - (boolean) Include user's virtual folders in list?
     *                   DEFAULT: No
     * 'new_folder' - (boolean) Display an option to create a new folder?
     *                DEFAULT: No
     * 'selected' - (string) The mailbox to have selected by default.
     *             DEFAULT: None
     * </pre>
     *
     * @return string  A string containing <option> elements for each mailbox
     *                 in the list.
     */
    static public function flistSelect($options = array())
    {
        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter();
        $tree = $imaptree->createTree(strval(new Horde_Support_Randomid()), array(
            'render_type' => 'IMP_Tree_Flist'
        ));
        if (!empty($options['selected'])) {
            $tree->addNodeParams($options['selected'], array('selected' => true));
        }
        $tree->setOption($options);

        return $tree->getTree();
    }

    /**
     * Checks for To:, Subject:, Cc:, and other compose window arguments and
     * pass back an associative array of those that are present.
     *
     * @return string  An associative array with compose arguments.
     */
    static public function getComposeArgs()
    {
        $args = array();
        $fields = array('to', 'cc', 'bcc', 'message', 'body', 'subject');

        foreach ($fields as $val) {
            if (($$val = Horde_Util::getFormData($val))) {
                $args[$val] = $$val;
            }
        }

        return self::_decodeMailto($args);
    }

    /**
     * Checks for mailto: prefix in the To field.
     *
     * @param array $args  A list of compose arguments.
     *
     * @return array  The array with the To: argument stripped of mailto:.
     */
    static protected function _decodeMailto($args)
    {
        if (isset($args['to']) && (strpos($args['to'], 'mailto:') === 0)) {
            $mailto = @parse_url($args['to']);
            if (is_array($mailto)) {
                $args['to'] = isset($mailto['path']) ? $mailto['path'] : '';
                if (!empty($mailto['query'])) {
                    parse_str($mailto['query'], $vals);
                    foreach ($fields as $val) {
                        if (isset($vals[$val])) {
                            $args[$val] = $vals[$val];
                        }
                    }
                }
            }
        }

        return $args;
    }

    /**
     * Prepares the arguments to use for composeLink().
     *
     * @param mixed $args   List of arguments to pass to compose.php. If this
     *                      is passed in as a string, it will be parsed as a
     *                      toaddress?subject=foo&cc=ccaddress (mailto-style)
     *                      string.
     * @param array $extra  Hash of extra, non-standard arguments to pass to
     *                      compose.php.
     *
     * @return array  The array of args to use for composeLink().
     */
    static public function composeLinkArgs($args = array(), $extra = array())
    {
        if (is_string($args)) {
            $string = $args;
            $args = array();
            if (($pos = strpos($string, '?')) !== false) {
                parse_str(substr($string, $pos + 1), $args);
                $args['to'] = substr($string, 0, $pos);
            } else {
                $args['to'] = $string;
            }
        }

        $args = self::_decodeMailto($args);

        /* Merge the two argument arrays. */
        return (is_array($extra) && !empty($extra))
            ? array_merge($args, $extra)
            : $args;
    }

    /**
     * Returns the appropriate link to call the message composition script.
     *
     * @param mixed $args       List of arguments to pass to compose script.
     *                          If this is passed in as a string, it will be
     *                          parsed as a toaddress?subject=foo&cc=ccaddress
     *                          (mailto-style) string.
     * @param array $extra      Hash of extra, non-standard arguments to pass
     *                          to compose script.
     * @param string $simplejs  Use simple JS (instead of Horde.popup() JS
     *                          function)?
     *
     * @return Horde_Url  The link to the message composition script.
     */
    static public function composeLink($args = array(), $extra = array(),
                                       $simplejs = false)
    {
        $args = self::composeLinkArgs($args, $extra);
        $view = self::getViewMode();

        if ($simplejs || ($view == 'dimp')) {
            $args['popup'] = 1;

            $url = Horde::url(($view == 'dimp') ? 'compose-dimp.php' : 'compose.php')->setRaw(true)->add($args);
            $url->toStringCallback = array(__CLASS__, 'composeLinkSimpleCallback');
        } elseif (($view != 'mimp') &&
                  $GLOBALS['prefs']->getValue('compose_popup') &&
                  $GLOBALS['browser']->hasFeature('javascript')) {
            $url = Horde::url('compose.php')->add($args);
            $url->toStringCallback = array(__CLASS__, 'composeLinkJsCallback');
        } else {
            $url = Horde::url(($view == 'mimp') ? 'compose-mimp.php' : 'compose.php')->add($args);
        }

        return $url;
    }

    /**
     * Callback for Horde_Url when generating "simple" compose links. Simple
     * links don't require exterior javascript libraries.
     *
     * @param Horde_Url $url  URL object.
     *
     * @return string  URL string representation.
     */
    static public function composeLinkSimpleCallback($url)
    {
        return "javascript:void(window.open('" . strval($url) . "', '', 'width=820,height=610,status=1,scrollbars=yes,resizable=yes'));";
    }

    /**
     * Callback for Horde_Url when generating javascript compose links.
     *
     * @param Horde_Url $url  URL object.
     *
     * @return string  URL string representation.
     */
    static public function composeLinkJsCallback($url)
    {
        return 'javascript:' . Horde::popupJs(strval($url), array('urlencode' => true));
    }

    /**
     * Filters a string, if requested.
     *
     * @param string $text  The text to filter.
     *
     * @return string  The filtered text (if requested).
     */
    static public function filterText($text)
    {
        if ($GLOBALS['prefs']->getValue('filtering') && strlen($text)) {
            return $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($text, 'words', array(
                'replacement' => $GLOBALS['conf']['msgsettings']['filtering']['replacement'],
                'words_file' => $GLOBALS['conf']['msgsettings']['filtering']['words']
            ));
        }

        return $text;
    }

    /**
     * Build IMP's menu.
     *
     * @return string  The menu output.
     */
    static public function menu()
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->set('forminput', Horde_Util::formInput());
        $t->set('use_folders', $GLOBALS['injector']->getInstance('IMP_Factory_Imap')->create()->allowFolders(), true);
        if ($t->get('use_folders')) {
            Horde::addScriptFile('imp.js', 'imp');
            $menu_view = $GLOBALS['prefs']->getValue('menu_view');
            $ak = $GLOBALS['prefs']->getValue('widget_accesskey')
                ? Horde::getAccessKey(_("Open Fo_lder"))
                : '';

            $t->set('ak', $ak);
            $t->set('flist', self::flistSelect(array('selected' => self::$mailbox, 'inc_vfolder' => true)));
            $t->set('flink', sprintf('%s%s<br />%s</a>', Horde::link('#'), ($menu_view != 'text') ? Horde::img('folders/open.png', _("Open Folder"), ($menu_view == 'icon') ? array('title' => _("Open Folder")) : array()) : '', ($menu_view != 'icon') ? Horde::highlightAccessKey(_("Open Fo_lder"), $ak) : ''));
        }
        $t->set('menu_string', Horde::menu(array('app' => 'imp', 'menu_ob' => true))->render());

        $menu = $t->fetch(IMP_TEMPLATES . '/imp/menu/menu.html');

        /* Need to buffer sidebar output here, because it may add things like
         * cookies which need to be sent before output begins. */
        Horde::startBuffer();
        require HORDE_BASE . '/services/sidebar.php';
        return $menu . Horde::endBuffer();
    }

    /**
     * Outputs IMP's status/notification bar.
     */
    static public function status()
    {
        $GLOBALS['notification']->notify(array('listeners' => array('status', 'audio')));
    }

    /**
     * Outputs IMP's quota information.
     */
    static public function quota()
    {
        $quotadata = self::quotaData(true);
        if (!empty($quotadata)) {
            $t = $GLOBALS['injector']->createInstance('Horde_Template');
            $t->set('class', $quotadata['class']);
            $t->set('message', $quotadata['message']);
            echo $t->fetch(IMP_TEMPLATES . '/quota/quota.html');
        }
    }

    /**
     * Returns data needed to output quota.
     *
     * @param boolean $long  Output long messages?
     *
     * @return array  Array with these keys: class, message, percent.
     */
    static public function quotaData($long = true)
    {
        if (!$GLOBALS['session']->get('imp', 'imap_quota')) {
            return false;
        }

        try {
            $quotaDriver = $GLOBALS['injector']->getInstance('IMP_Quota');
            $quota = $quotaDriver->getQuota();
        } catch (IMP_Exception $e) {
            Horde::logMessage($e, 'ERR');
            return false;
        }

        if (empty($quota)) {
            return false;
        }

        $strings = $quotaDriver->getMessages();
        list($calc, $unit) = $quotaDriver->getUnit();
        $ret = array('percent' => 0);

        if ($quota['limit'] != 0) {
            $quota['usage'] = $quota['usage'] / $calc;
            $quota['limit'] = $quota['limit'] / $calc;
            $ret['percent'] = ($quota['usage'] * 100) / $quota['limit'];
            if ($ret['percent'] >= 90) {
                $ret['class'] = 'quotaalert';
            } elseif ($ret['percent'] >= 75) {
                $ret['class'] = 'quotawarn';
            } else {
                $ret['class'] = 'control';
            }

            $ret['message'] = $long
                ? sprintf($strings['long'], $quota['usage'], $unit, $quota['limit'], $unit, $ret['percent'])
                : sprintf($strings['short'], $ret['percent'], $quota['limit'], $unit);
            $ret['percent'] = sprintf("%.2f", $ret['percent']);
        } else {
            // Hide unlimited quota message?
            if ($GLOBALS['session']->get('imp', 'quota_hide_when_unlimited')) {
                return false;
            }

            $ret['class'] = 'control';
            if ($quota['usage'] != 0) {
                $quota['usage'] = $quota['usage'] / $calc;

                $ret['message'] = $long
                    ? sprintf($strings['nolimit_long'], $quota['usage'], $unit)
                    : sprintf($strings['nolimit_short'], $quota['usage'], $unit);
            } else {
                $ret['message'] = $long
                    ? sprintf(_("Quota status: NO LIMIT"))
                    : _("No limit");
            }
        }

        return $ret;
    }

    /**
     * Generates a URL with necessary mailbox/UID information.
     *
     * @param string|Horde_Url $page  Page name to link to.
     * @param string $mailbox         The base mailbox to use on the linked
     *                                page.
     * @param string $uid             The UID to use on the linked page.
     * @param string $tmailbox        The mailbox associated with $uid.
     * @param boolean $encode         Encode the argument separator?
     *
     * @return Horde_Url  URL to $page with any necessary mailbox information
     *                    added to the parameter list of the URL.
     */
    static public function generateIMPUrl($page, $mailbox, $uid = null,
                                          $tmailbox = null, $encode = true)
    {
        $url = ($page instanceof Horde_Url)
            ? clone $page
            : Horde::url($page);

        return $url->add(self::getIMPMboxParameters($mailbox, $uid, $tmailbox))->setRaw(!$encode);
    }

    /**
     * Returns a list of parameters necessary to indicate current mailbox
     * status.
     *
     * @param string $mailbox   The mailbox to use on the linked page.
     * @param string $uid       The UID to use on the linked page.
     * @param string $tmailbox  The mailbox associated with $uid to use on
     *                          the linked page.
     *
     * @return array  The list of parameters needed to indicate the current
     *                mailbox status.
     */
    static public function getIMPMboxParameters($mailbox, $uid = null,
                                                $tmailbox = null)
    {
        $params = array('mailbox' => $mailbox);
        if (!is_null($uid)) {
            $params['uid'] = $uid;
            if ($mailbox != $tmailbox) {
                $params['thismailbox'] = $tmailbox;
            }
        }
        return $params;
    }

    /**
     * Return a list of valid encrypt HTML option tags.
     *
     * @param string $default      The default encrypt option.
     * @param boolean $returnList  Whether to return a hash with options
     *                             instead of the options tag.
     *
     * @return mixed  The list of option tags. This is empty if no encryption
     *                is available.
     */
    static public function encryptList($default = null, $returnList = false)
    {
        if (is_null($default)) {
            $default = $GLOBALS['prefs']->getValue('default_encrypt');
        }

        $enc_opts = array();
        $output = '';

        if (!empty($GLOBALS['conf']['gnupg']['path']) &&
            $GLOBALS['prefs']->getValue('use_pgp')) {
            $enc_opts += $GLOBALS['injector']->getInstance('IMP_Crypt_Pgp')->encryptList();
        }

        if ($GLOBALS['prefs']->getValue('use_smime')) {
            $enc_opts += $GLOBALS['injector']->getInstance('IMP_Crypt_Smime')->encryptList();
        }

        if (!empty($enc_opts)) {
            $enc_opts = array_merge(
                array(self::ENCRYPT_NONE => _("None")),
                $enc_opts
            );
        }

        if ($returnList) {
            return $enc_opts;
        }

        foreach ($enc_opts as $key => $val) {
             $output .= '<option value="' . $key . '"' . (($default == $key) ? ' selected="selected"' : '') . '>' . $val . "</option>\n";
        }

        return $output;
    }

    /**
     * Sets mailbox/index information for current page load. This information
     * is accessible via IMP::$mailbox, IMP::$thismailbox, and IMP::$uid.
     *
     * @param boolean $mbox  Use this mailbox, instead of form data.
     */
    static public function setCurrentMailboxInfo($mbox = null)
    {
        if (is_null($mbox)) {
            $mbox = Horde_Util::getFormData('mailbox');
            self::$mailbox = is_null($mbox)
                ? IMP_Mailbox::get('INBOX')
                : IMP_Mailbox::formFrom($mbox);

            $mbox = Horde_Util::getFormData('thismailbox');
            self::$thismailbox = is_null($mbox)
                ? self::$mailbox
                : IMP_Mailbox::formFrom($mbox);

            self::$uid = Horde_Util::getFormData('uid');
        } else {
            self::$mailbox = self::$thismailbox = IMP_Mailbox::get($mbox);
            self::$uid = null;
        }
    }

    /**
     * Return a selfURL that has had index/mailbox/actionID information
     * removed/altered based on an action that has occurred on the present
     * page.
     *
     * @return Horde_Url  The self URL.
     */
    static public function selfUrl()
    {
        return self::$newUrl
            ? self::$newUrl->copy()
            : Horde::selfUrl(true);
    }

    /**
     * Determine the status of composing.
     *
     * @return boolean  Is compose allowed?
     * @throws Horde_Exception
     */
    static public function canCompose()
    {
        try {
            return !Horde::callHook('disable_compose', array(), 'imp');
        } catch (Horde_Exception_HookNotSet $e) {
            return true;
        }
    }

    /**
     * Output configured alerts for newmail.
     *
     * @param mixed $var  Either an associative array with mailbox names as
     *                    the keys and the message count as the values or
     *                    an integer indicating the number of new messages
     *                    in the current mailbox.
     *
     * @param integer $msgs  The number of new messages.
     */
    static public function newmailAlerts($var)
    {
        if ($GLOBALS['prefs']->getValue('nav_popup')) {
            Horde::addInlineScript(array(
                self::_getNewMessagePopup($var)
            ), 'dom');
        }

        if ($sound = $GLOBALS['prefs']->getValue('nav_audio')) {
            $GLOBALS['notification']->push(Horde_Themes::sound($sound), 'audio');
        }
    }

    /**
     * Outputs the necessary javascript code to display the new mail
     * notification message.
     *
     * @param mixed $var  See self::newmailAlerts().
     *
     * @return string  The javascript for the popup message.
     */
    static protected function _getNewMessagePopup($var)
    {
        $t = $GLOBALS['injector']->createInstance('Horde_Template');
        $t->setOption('gettext', true);
        if (is_array($var)) {
            if (empty($var)) {
                return;
            }
            $folders = array();
            foreach ($var as $mb => $nm) {
                $folders[] = array(
                    'name' => htmlspecialchars(IMP_Mailbox::get($mb)->display),
                    'new' => intval($nm),
                    'url' => self::generateIMPUrl('mailbox.php', $mb),
                );
            }
            $t->set('folders', $folders);

            $imp_search = $GLOBALS['injector']->getInstance('IMP_Search');
            if (($GLOBALS['session']->get('imp', 'protocol') != 'pop') &&
                ($vinbox = $imp_search['vinbox']) &&
                $vinbox->enabled) {
                $t->set('vinbox', self::generateIMPUrl('mailbox.php', strval($vinbox))->link());
            }
        } else {
            $t->set('msg', ($var == 1) ? _("You have 1 new message.") : sprintf(_("You have %s new messages."), $var));
        }
        $t_html = str_replace("\n", ' ', $t->fetch(IMP_TEMPLATES . '/newmsg/alert.html'));

        Horde::addScriptFile('effects.js', 'horde');
        Horde::addScriptFile('redbox.js', 'horde');

        return 'RedBox.overlay = false; RedBox.showHtml(\'' . addcslashes($t_html, "'/") . '\');';
    }

    /**
     * Determines parameters needed to do an address search
     *
     * @return array  An array with two keys: 'fields' and 'sources'.
     */
    static public function getAddressbookSearchParams()
    {
        $src = json_decode($GLOBALS['prefs']->getValue('search_sources'));
        if (empty($src)) {
            $src = array();
        }

        $fields = json_decode($GLOBALS['prefs']->getValue('search_fields'), true);
        if (empty($fields)) {
            $fields = array();
        }

        return array(
            'fields' => $fields,
            'sources' => $src
        );
    }

}
