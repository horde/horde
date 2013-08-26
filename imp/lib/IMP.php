<?php
/**
 * Copyright 1999-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @category  Horde
 * @copyright 1999-2013 Horde LLC
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
 * @copyright 1999-2013 Horde LLC
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
     * Generates a select form input from a mailbox list. The &lt;select&gt;
     * and &lt;/select&gt; tags are NOT included in the output.
     *
     * @param array $options  Optional parameters:
     *   - abbrev: (boolean) Abbreviate long mailbox names by replacing the
     *             middle of the name with '...'?
     *             DEFAULT: Yes
     *   - basename: (boolean)  Use raw basename instead of abbreviated label?
     *               DEFAULT: false
     *   - filter: (array) An array of mailboxes to ignore.
     *             DEFAULT: Display all
     *   - heading: (string) The label for an empty-value option at the top of
     *              the list.
     *              DEFAULT: ''
     *   - inc_notepads: (boolean) Include user's editable notepads in list?
     *                   DEFAULT: No
     *   - inc_tasklists: (boolean) Include user's editable tasklists in list?
     *                    DEFAULT: No
     *   - inc_vfolder: (boolean) Include user's virtual folders in list?
     *                  DEFAULT: No
     *   - new_mbox: (boolean) Display an option to create a new mailbox?
     *               DEFAULT: No
     *   - selected: (string) The mailbox to have selected by default.
     *               DEFAULT: None
     *   - optgroup: (boolean) Whether to use <optgroup> elements to group
     *               mailbox types.
     *               DEFAULT: false
     *
     * @return string  A string containing <option> elements for each mailbox
     *                 in the list.
     */
    static public function flistSelect(array $options = array())
    {
        $imaptree = $GLOBALS['injector']->getInstance('IMP_Imap_Tree');
        $imaptree->setIteratorFilter();
        $tree = $imaptree->createTree(strval(new Horde_Support_Randomid()), array(
            'basename' => !empty($options['basename']),
            'render_type' => 'IMP_Tree_Flist'
        ));
        if (!empty($options['selected'])) {
            $tree->addNodeParams(IMP_Mailbox::formTo($options['selected']), array('selected' => true));
        }
        $tree->setOption($options);

        return $tree->getTree();
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
        global $injector, $prefs;

        if ($prefs->getValue('filtering') && strlen($text)) {
            try {
                return $injector->getInstance('Horde_Core_Factory_TextFilter')->filter($text, 'words', Horde::callHook('msg_filter', array(), 'imp'));
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
    static public function sizeFormat($size)
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
    static public function numberFormat($number, $decimals)
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
     * @return array  See Horde_Mail_Rfc822#parseAddressList().
     *
     * @throws Horde_Mail_Exception
     */
    static public function parseAddressList($in, array $opts = array())
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

}
