<?php

/**
 * Sort by.
 */
define('FIMA_SORT_DATE',    'date');
define('FIMA_SORT_ASSET',   'asset_number');
define('FIMA_SORT_ACCOUNT', 'account_number');
define('FIMA_SORT_AMOUNT',  'amount');
define('FIMA_SORT_DESC',    'desc');

/**
 * Order by.
 */
define('FIMA_SORT_ASCEND',  0);
define('FIMA_SORT_DESCEND', 1);

/**
 * Account types.
 */
define('FIMA_ACCOUNTTYPE_ASSET',     'asset');
define('FIMA_ACCOUNTTYPE_INCOME',    'income');
define('FIMA_ACCOUNTTYPE_EXPENSE',   'expense');

/**
 * Posting types.
 */
define('FIMA_POSTINGTYPE_ACTUAL',   'actual');
define('FIMA_POSTINGTYPE_FORECAST', 'forecast');
define('FIMA_POSTINGTYPE_BUDGET',   'budget');

/**
 * Fima Base Class.
 *
 * Copyright 2007-2008 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Thomas Trethan <thomas@trethan.net>
 * @package Fima
 */
class Fima {

    /**
     * Retrieves the current user's ledgers from storage.
     * This function will also sort the resulting list, if requested.
     *
     * @param boolean $filters  Filters for accounts.
     *
     * @return array            A list of the requested accounts.
     *
     * @see Fima_Driver::listAccounts()
     */
    function listAccounts($filters = array())
    {
        $ledger = Fima::getActiveLedger();

        /* Create a Fima storage instance. */
        $storage = &Fima_Driver::singleton($ledger);
        $storage->retrieveAccounts($filters);

        /* Retrieve the accounts from storage. */
        $accounts = $storage->listAccounts();
        if (is_a($accounts, 'PEAR_Error')) {
            return $accounts;
        }

        return $accounts;
    }

    function getAccount($account)
    {
        $ledger = Fima::getActiveLedger();
        $storage = &Fima_Driver::singleton($ledger);
        return $storage->getAccount($account);
    }

    /**
     * Retrieves the current user's postings from storage.
     *
     * @param boolean $filters  Filters for postings.
     * @param integer $page     Page/Recordsset to display.
     *
     * @return array            A list of the requested postings.
     *
     * @see Fima_Driver::listPostings()
     */
    function listPostings($filters = array(), $page = null)
    {
        global $prefs;

        $ledger = Fima::getActiveLedger();
        $postingtype = $prefs->getValue('active_postingtype');
        if ($page == 0) {
            $limit = null;
        } else {
            $limit = array($page, (int)$prefs->getValue('max_postings'));
        }

        /* Create a Fima storage instance. */
        $storage = &Fima_Driver::singleton($ledger);
        $storage->retrievePostings($filters,
                                   array('posting_' . $prefs->getValue('sortby') . ' ' . ($prefs->getValue('sortdir') ? 'DESC' : 'ASC'),
                                         'posting_' . $prefs->getValue('altsortby')),
                                   $limit);

        /* Retrieve the accounts from storage. */
        $postings = $storage->listPostings();
        if (is_a($postings, 'PEAR_Error')) {
            return $postings;
        }

        return $postings;
    }

    /**
     * Get the total number of postings with the selected filters.
     *
     * @return int  The total number of postings.
     */
    function getPostingsCount()
    {
        $ledger = Fima::getActiveLedger();

        /* Create a Fima storage instance. */
        $storage = &Fima_Driver::singleton($ledger);
        return $storage->_postingsCount;
    }


    /**
     * Get grouped results from storage.
     *
     * @param array $groups     Fields to group.
     * @param boolean $filters  Filters for postings.
     *
     * @return array            A matrix of the grouped results.
     *
     * @see Fima_Driver::listPostings()
     */
    function getResults($groups = array(), $filters = array()) {
        $ledger = Fima::getActiveLedger();

        /* Create a Fima storage instance. */
        $storage = &Fima_Driver::singleton($ledger);
        return $storage->getResults($groups, $filters);
    }

    /**
     * Get the results of all asset accounts.
     *
     * @param string $postingtype  Type of postings.
     * @param boolean $perdate     Date of asset results.
     *
     * @return array  Array of asset accounts and results
     */
    function getAssetResults($postingtype, $perdate = null)
    {
        $ledger = Fima::getActiveLedger();

        /* Create a Fima storage instance. */
        $storage = &Fima_Driver::singleton($ledger);
        return $storage->getAssetResults($postingtype, $perdate);
    }

    /**
     * Get the total result of postings with the selected filters.
     *
     * @return float  The total result of postings.
     */
    function getPostingsResult()
    {
        $ledger = Fima::getActiveLedger();

        /* Create a Fima storage instance. */
        $storage = &Fima_Driver::singleton($ledger);
        return $storage->_postingsResult;
    }

    /**
     * Lists all ledgers a user has access to.
     *
     * @param boolean $owneronly  Only return ledgers that this user owns?
     *                            Defaults to false.
     * @param integer $permission The permission to filter ledgers by.
     *
     * @return array  The list of ledgers.
     */
    function listLedgers($owneronly = false, $permission = Horde_Perms::SHOW)
    {
        $ledgers = $GLOBALS['fima_shares']->listShares($GLOBALS['registry']->getAuth(), $permission, $owneronly ? $GLOBALS['registry']->getAuth() : null);
        if (is_a($ledgers, 'PEAR_Error')) {
            Horde::logMessage($ledgers, 'ERR');
            return array();
        }

        return $ledgers;
    }

    /**
     * Returns the active ledger for the current user.
     */
    function getActiveLedger($permission = Horde_Perms::SHOW)
    {
        global $prefs;

        $active_ledger = $prefs->getValue('active_ledger');
        $ledgers = Fima::listLedgers(false, $permission);

        if (isset($ledgers[$active_ledger])) {
            return $active_ledger;
        } elseif ($prefs->isLocked('active_ledger')) {
            return false;
        } elseif (count($ledgers)) {
            return key($ledgers);
        }

        return false;
    }

    /**
     * Get parent account number
     */
    function getAccountParent($accountNumber)
    {
        if ($accountNumber % 100 == 0) {
            $parent = null;
        } else {
            $parent = sprintf('%\'04d', (int)($accountNumber / 100) * 100);
        }
        return $parent;
    }

    /**
     * Builds the HTML for a account selection widget.
     *
     * @param string $name           The name of the widget.
     * @param mixed $value           The value(s) to select by default.
     * @param string $params         Any additional parameters to include in the <select> tag.
     * @param mixed $blank           False or label of blank entry.
     * @param boolean $multiple      Shall multiple selections be enabled?
     * @param array $filters         Filters for acconuts.
     * @param boolean $hideclosed    Do not include closed accounts.
     *
     * @return string  The HTML <select> widget.
     */
    function buildAccountWidget($name, $value = '', $params = null, $blank = false, $multiple = false, $filters = array(), $hideclosed = false)
    {
        $accounts = Fima::listAccounts($filters);

        $html = '<select id="' . $name . '" name="' . $name . '"';
        if (!is_null($params)) {
            $html .= ' ' . $params;
        }
        if ($multiple) {
            $html .= ' multiple="multiple" size="' . min(5, count($accounts)). '"';
        }
        $html .= '>';
        if ($blank !== false) {
            $html .= '<option value="">' . htmlspecialchars($blank) . '</option>';
        }

        $accounts = Fima::listAccounts($filters);
        foreach ($accounts as $accountId => $account) {
            if ($hideclosed && $account['closed'] && $accountId != $value) {
                continue;
            }
            $html .= '<option value="' . $accountId . '" class="' . ($account['eo'] ? 'eo' : '') . $account['type'] . ' ' . ($account['closed'] ? 'closed' : '') . '"';
            if ($multiple && is_array($value)) {
                $html .= (in_array($accountId, $value)) ? ' selected="selected">' : '>';
            } else {
                $html .= ($accountId == $value) ? ' selected="selected">' : '>';
            }
            $html .= htmlspecialchars($account['label']) . '</option>';
        }

        return $html . "</select>\n";
    }

    /**
     * Builds the HTML for a account type selection widget.
     *
     * @param string $name       The name of the widget.
     * @param string $value      The value to select by default.
     * @param string $params     Any additional parameters to include in the <select >tag.
     * @param boolean $blank     Shall a blank entry be added?
     * @param boolean $multiple  Shall multiple selections be inabled?
     *
     * @return string  The HTML <select> widget.
     */
    function buildAccountTypeWidget($name, $value = '', $params = null, $blank = false, $multiple = false)
    {
        $types = Fima::getAccountTypes();

        $html = '<select id="' . $name . '" name="' . $name . '"';
        if (!is_null($params)) {
            $html .= ' ' . $params;
        }
        if ($multiple) {
            $html .= ' multiple="multiple" size="' . min(5, count($types)). '"';
        }
        $html .= '>';
        if ($blank !== false) {
            $html .= '<option value="">' . htmlspecialchars($blank) . '</option>';
        }

        foreach ($types as $typeValue => $typeLabel) {
            $html .= '<option value="' . $typeValue . '"';
            if ($multiple && is_array($value)) {
                $html .= (in_array($accountId, $value)) ? ' selected="selected">' : '>';
            } else {
                $html .= ($typeValue == $value) ? ' selected="selected">' : '>';
            }
            $html .= htmlspecialchars($typeLabel) . '</option>';
        }

        return $html . "</select>\n";
    }

    /**
     * Builds the HTML for a posting type selection widget.
     *
     * @param string $name       The name of the widget.
     * @param string $value      The value to select by default.
     * @param string $params     Any additional parameters to include in the <select >tag.
     * @param boolean $blank     Shall a blank entry be added?
     * @param boolean $multiple  Shall multiple selections be inabled?
     *
     * @return string  The HTML <select> widget.
     */
    function buildPostingTypeWidget($name, $value = '', $params = null, $blank = false, $multiple = false)
    {
        $types = Fima::getPostingTypes();

        $html = '<select id="' . $name . '" name="' . $name . '"';
        if (!is_null($params)) {
            $html .= ' ' . $params;
        }
        if ($multiple) {
            $html .= ' multiple="multiple" size="' . min(5, count($types)). '"';
        }
        $html .= '>';
        if ($blank !== false) {
            $html .= '<option value="">' . htmlspecialchars($blank) . '</option>';
        }

        foreach ($types as $typeValue => $typeLabel) {
            $html .= '<option value="' . $typeValue . '"';
            if ($multiple && is_array($value)) {
                $html .= (in_array($accountId, $value)) ? ' selected="selected">' : '>';
            } else {
                $html .= ($typeValue == $value) ? ' selected="selected">' : '>';
            }
            $html .= htmlspecialchars($typeLabel) . '</option>';
        }

        return $html . "</select>\n";
    }

    /**
     * Builds the HTML for a date selection widget.
     *
     * @param string $name        The name of the widget.
     * @param integer $value      The value to select by default.
     * @param string $params      Any additional parameters to include in the <select> tag.
     * @param boolean $blank      Shall a blank entry be added?
     * @param string $periodonly  Don't display the day input field
     *
     * @return string  The HTML <select> widget.
     */
    function buildDateWidget($name, $value = 0, $params = null, $blank = false, $periodonly = false)
    {
        $value = ($value !== 0) ? explode('-', date('Y-n-j', $value)) : array(date('Y'), 0, 0);

        /* Year. */
        $html = '<input id="' . $name . '[year]" name="' . $name . '[year]" type="text" value="' . $value[0] . '" onchange="updateWday(\'' . $name . '\');" size="4" maxlength="4"';
        if (!is_null($params)) {
            $html .= ' ' . $params;
        }
        $html .= ' />' . "\n";

        /* Month. */
        $html .= '- <select id="' . $name . '[month]" name="' . $name . '[month]" onchange="updateWday(\'' . $name . '\');"';
        if (!is_null($params)) {
            $html .= ' ' . $params;
        }
        $html .= '>';
        if ($blank !== false) {
            $html .= '<option value="">' . htmlspecialchars($blank) . '</option>';
        }
        for ($i = 1; $i < 13; ++$i) {
            $html .= '<option value="' . $i . '"';
            $html .= ($i == $value[1]) ? ' selected="selected">' : '>';
            $html .= htmlspecialchars(strftime('%b', mktime(0, 0, 0, $i, 1))) . '</option>';
        }
        $html .= '</select>' . "\n";

        /* Period only? */
        if ($periodonly) {
            return $html;
        }

        /* Day. */
        $html .= '- <select id="' . $name . '[day]" name="' . $name . '[day]" onchange="updateWday(\'' . $name . '\');"';
        if (!is_null($params)) {
            $html .= ' ' . $params;
        }
        $html .= '>';
        if ($blank !== false) {
            $html .= '<option value="">' . htmlspecialchars($blank) . '</option>';
        }
        for ($i = 1; $i < 32; ++$i) {
            $html .= '<option value="' . $i . '"';
            $html .= ($i == $value[2]) ? ' selected="selected">' : '>';
            $html .= $i . '</option>';
        }
        $html .= '</select>' . "\n";

        return $html;
    }

    /**
     * Get account types.
     *
     * @param string $accountType  Get a specific account type or all.
     *
     * @return mixed  Array of account types or a specific account type.
     */
    function getAccountTypes($accountType = null)
    {
        $types = array(FIMA_ACCOUNTTYPE_ASSET     => _("Asset"),
                       FIMA_ACCOUNTTYPE_INCOME    => _("Income"),
                       FIMA_ACCOUNTTYPE_EXPENSE   => _("Expense"));

        if ($accountType !== null) {
            if (isset($types[$accountType])) {
                return $types[$accountType];
            } else {
                return null;
            }
        } else {
            return $types;
        }
    }

    /**
     * Get posting types.
     *
     * @param string $postingType  Get a specific posting type or all.
     *
     * @return mixed  Array of posting types or a specific posting type.
     */
    function getPostingTypes($postingType = null)
    {
        $types = array(FIMA_POSTINGTYPE_ACTUAL   => _("Actual"),
                       FIMA_POSTINGTYPE_FORECAST => _("Forecast"),
                       FIMA_POSTINGTYPE_BUDGET   => _("Budget"));

        if ($postingType !== null) {
            if (isset($types[$postingType])) {
                return $types[$postingType];
            } else {
                return null;
            }
        } else {
            return $types;
        }
    }

    /**
     * Convert an amount from the interface to a float value.
     *
     * @param string $amount  Amount to convert.
     *
     * @return float  Float value of the amount.
     */
    function convertAmountToValue($amount)
    {
        global $prefs;

        $format = $prefs->getValue('amount_format');
        return (float)str_replace(array($format{0}, $format{1}), array('', '.'), $amount);
    }

    /**
     * Convert a float number to an amount for the interface.
     *
     * @param float $value  Float value to convert.
     *
     * @return string  Amount.
     */
    function convertValueToAmount($value)
    {
        global $prefs;

        $format = $prefs->getValue('amount_format');
        return number_format($value, 2, $format{1}, $format{0});
    }

    /*
     * Convert a formatted date to a unix timestamp.
     *
     * @param string $date    Formatted date.
     * @param string $format  Date format.
     *
     * @return int  Unix timestamp.
     */
    function convertDateToStamp($date, $format)
    {
        if ($date == '') {
            return false;
        }

        if (preg_match('/[^%a-zA-Z]/', $format, $seperator) === false) {
            return false;
        }

        $formatparts = explode($seperator[0], $format);
        $dateparts = explode($seperator[0], $date);

        foreach ($formatparts as $key => $fmt) {
            $dateparts[$fmt] = $dateparts[$key];
        }

        $stamp = mktime(0, 0, 0, $dateparts['%m'], $dateparts['%d'], $dateparts['%Y']);

        return $stamp;
    }

    /**
     * Convert a date format to a format useable when entering postings.
     *
     * @param string $format  Date format.
     *
     * @return string  The converted date format.
     */
    function convertDateFormat($format)
    {
        switch($format) {
        case '%x':
        case '%Y-%m-%d':
        case '%d/%m/%Y':
        case '%d.%m.%Y':
        case '%m/%d/%Y':
            break;
        case '%a %Y-%m-%d':
            $format = '%Y-%m-%d';
            break;
        case '%A, %d %B %Y':
        case '%a, %e %b %Y':
        case '%a, %e %b %y':
        case '%a %d %b %Y':
        case '%e %b %Y':
        case '%e. %m %Y':
            $format = '%d/%m/%Y';
            break;
        case '%A, %d. %B %Y':
        case '%e. %b %Y':
        case '%e. %m.':
        case '%e. %B':
        case '%e. %B %Y':
        case '%e. %B %y':
            $format = '%d.%m.%Y';
            break;
        case '%A %B %d, %Y':
        case '%a, %b %e, %Y':
        case '%a, %b %e, %y':
        case '%a, %b %e':
        case '%B %e, %Y':
            $format = '%m/%d/%Y';
            break;
        case '%a %x':
        default:
            $format = '%x';
            break;
        }

        if ($format == '%x') {
            $fmts = array('%Y-%m-%d', '%d/%m/%Y', '%d.%m.%Y', '%m/%d/%Y');
            foreach ($fmts as $fmt) {
                if (strftime($format) == strftime($fmt)) {
                    $format = $fmt;
                    break;
                }
            }
            if ($format == '%x') {
                $format = $fmts[0];
            }
        }

        return $format;
    }

    /**
     * Convert a date format to a period format useable for reports.
     *
     * @param string $format  Date format.
     *
     * @return string  The converted period format.
     */
    function convertDateToPeriodFormat($format)
    {
        if ($format == '%x') {
            $fmts = array('%Y-%m-%d', '%d/%m/%Y', '%d.%m.%Y', '%m/%d/%Y');
            foreach ($fmts as $fmt) {
                if (strftime($format) == strftime($fmt)) {
                    $format = $fmt;
                    break;
                }
            }
            if ($format == '%x') {
                $format = $fmts[0];
            }
        }
        $format .= ' ';

        $p = preg_match_all('/(%[YymBb])(.)/', $format, $matches);
        $format = '';
        for ($i = 0; $i < $p; $i++) {
            $format .= $matches[1][$i] . (($i < $p - 1) ? $matches[2][$i] : '');
        }

        return $format;
    }

    /**
     * Convert wildcards in a text to SQL wildcards.
     *
     * @param string $text  Text containing wildcards.
     *
     * @return string  Converted text with SQL wildcards.
     */
    function convertWildcards($text)
    {
        global $prefs;

        $wildcards = $prefs->getValue('wildcard_format');
        if ($wildcards == 'dos') {
            $text = str_replace(array('\\*', '\\?'), array(chr(0xe), chr(0xf)), $text);
            $text = str_replace(array('%', '_'), array('\\%', '\\_'), $text);
            $text = str_replace(array('*', '?'), array('%', '_'), $text);
            $text = str_replace(array(chr(0xe), chr(0xf)), array('*', '?'), $text);
        } elseif ($wildcards == 'sql') {
        } elseif ($wildcards == 'none') {
            $text = str_replace(array('%', '_'), array('\\%', '\\_'), $text);
        }
        return $text;
    }

    /**
     * Initial app setup code.
     */
    function initialize()
    {
        /* Store the request timestamp if it's not already present. */
        if (!isset($_SERVER['REQUEST_TIME'])) {
            $_SERVER['REQUEST_TIME'] = time();
        }

        // Update the preference for what ledgers to display. If the user
        // doesn't have any selected ledger for view then fall back to
        // some available ledger.
        $GLOBALS['display_ledgers'] = @unserialize($GLOBALS['prefs']->getValue('display_ledgers'));
        if (!$GLOBALS['display_ledgers']) {
            $GLOBALS['display_ledgers'] = array();
        }
        if (($ledgerId = Horde_Util::getFormData('display_ledger')) !== null) {
            if (is_array($ledgerId)) {
                $GLOBALS['display_ledgers'] = $ledgerId;
            } else {
                if (in_array($ledgerId, $GLOBALS['display_ledgers'])) {
                    $key = array_search($ledgerId, $GLOBALS['display_ledgers']);
                    unset($GLOBALS['display_ledgers'][$key]);
                } else {
                    $GLOBALS['display_ledgers'][] = $ledgerId;
                }
            }
        }

        // Make sure all ledgers exist now, to save on checking later.
        $_temp = $GLOBALS['display_ledgers'];
        $GLOBALS['all_ledgers'] = Fima::listLedgers();
        $GLOBALS['display_ledgers'] = array();
        foreach ($_temp as $id) {
            if (isset($GLOBALS['all_ledgers'][$id])) {
                $GLOBALS['display_ledgers'][] = $id;
            }
        }

        if (count($GLOBALS['display_ledgers']) == 0) {
            $ledgerss = Fima::listLedgers(true);
            if (!$GLOBALS['registry']->getAuth()) {
                /* All ledgers for guests. */
                $GLOBALS['display_ledgers'] = array_keys($ledgers);
            } else {
                /* Make sure at least the active ledger is visible. */
                $active_ledger = Fima::getActiveLedger(Horde_Perms::READ);
                if ($active_ledger) {
                    $GLOBALS['display_ledgers'] = array($active_ledger);
                }

                /* If the user's personal ledger doesn't exist, then create it. */
                if (!$GLOBALS['fima_shares']->exists($GLOBALS['registry']->getAuth())) {
                    $identity = $GLOBALS['injector']->getInstance('Horde_Prefs_Identity')->getIdentity();
                    $name = $identity->getValue('fullname');
                    if (trim($name) == '') {
                        $name = $GLOBALS['registry']->getAuth('original');
                    }
                    $share = &$GLOBALS['fima_shares']->newShare($GLOBALS['registry']->getAuth());
                    $share->set('name', sprintf(_("%s's Ledger"), $name));
                    $GLOBALS['fima_shares']->addShare($share);

                    /* Make sure the personal ledger is displayed by default. */
                    if (!in_array($GLOBALS['registry']->getAuth(), $GLOBALS['display_ledgers'])) {
                        $GLOBALS['display_ledgers'][] = $GLOBALS['registry']->getAuth();
                    }
                }
            }
        }

        $GLOBALS['prefs']->setValue('display_ledgers', serialize($GLOBALS['display_ledgers']));

        /* Update active ledger. */
        if (($changeledger = Horde_Util::getFormData('changeledger')) !== null) {
            $GLOBALS['prefs']->setValue('active_ledger', $changeledger);
        }
    }

    /**
     * Build Fima's list of menu items.
     */
    function getMenu()
    {
        global $conf, $browser, $print_link;

        $actionID = Horde_Util::getFormData('actionID');
        $hordeimg = Horde_Themes::img(null, 'horde');

        $menu = new Horde_Menu(Horde_Menu::MASK_ALL);
        $menu->add(Horde::applicationUrl('postings.php'), _("_List Postings"), 'list.png', null, null, null, (basename($_SERVER['PHP_SELF']) == 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) != 'ledgers') ? 'current' : ($actionID === null ? null : '__noselection'));
        $menu->add(Horde_Util::addParameter(Horde::applicationUrl('postings.php'), 'actionID', 'add_postings'), _("Add _Postings"), 'add.png', null, null, null, $actionID == 'add_postings' ? 'current' : '__noselection');
        $menu->add(Horde::applicationUrl('search.php'), _("Search"), 'search.png', $hordeimg);
        $menu->add(Horde::applicationUrl('accounts.php'), _("_Accounts"), 'accounts.png');

        if ($GLOBALS['registry']->getAuth()) {
            $menu->add(Horde::applicationUrl('ledgers/index.php'), _("_My Ledgers"), 'accounts.png');
        }

        /* Reports. */
        $menu->add(Horde::applicationUrl('report.php'), _("_Reports"), 'report.png');

        /* Import/Export. */
        $menu->add(Horde::applicationUrl('data.php'), _("_Import/Export"), 'data.png', $hordeimg);

        /* Print. */
        if (isset($print_link)) {
            $menu->add($print_link, _("_Print"), 'print.png', $hordeimg, '_blank', Horde::popupJs($print_link, array('urlencode' => true)) . 'return false;', '__noselection');
        }

        return $menu;
    }

}
