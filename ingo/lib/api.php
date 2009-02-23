<?php
/**
 * Ingo external API interface.
 *
 * This file defines Ingo's external API interface. Other applications
 * can interact with Ingo through this API.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 */

$_services['perms'] = array(
    'args' => array(),
    'type' => '{urn:horde}stringArray');

$_services['blacklistFrom'] = array(
    'args' => array('addresses' => '{urn:horde}stringArray'),
    'type' => 'boolean',
);

$_services['showBlacklist'] = array(
    'link' => '%application%/blacklist.php',
);

$_services['whitelistFrom'] = array(
    'args' => array('addresses' => '{urn:horde}stringArray'),
    'type' => 'boolean',
);

$_services['showWhitelist'] = array(
    'link' => '%application%/whitelist.php',
);

$_services['canApplyFilters'] = array(
    'args' => array(),
    'type' => 'boolean',
);

$_services['applyFilters'] = array(
    'args' => array('params' => '{urn:horde}stringArray'),
    'type' => 'boolean',
);

$_services['showFilters'] = array(
    'link' => '%application%/filters.php',
);

$_services['showVacation'] = array(
    'link' => '%application%/vacation.php',
);

$_services['setVacation'] = array(
    'args' => array('info' => '{urn:horde}stringArray'),
    'type' => 'boolean',
);

$_services['disableVacation'] = array(
    'args' => array(),
    'type' => 'boolean',
);

/**
 * Returns a list of available permissions.
 *
 * @return array  An array describing all available permissions.
 */
function _ingo_perms()
{
    $perms = array();
    $perms['tree']['ingo']['allow_rules'] = false;
    $perms['title']['ingo:allow_rules'] = _("Allow Rules");
    $perms['type']['ingo:allow_rules'] = 'boolean';
    $perms['tree']['ingo']['max_rules'] = false;
    $perms['title']['ingo:max_rules'] = _("Maximum Number of Rules");
    $perms['type']['ingo:max_rules'] = 'int';

    return $perms;
}

/**
 * Add addresses to the blacklist
 *
 * @param string $addresses    The addresses to add
 */
function _ingo_blacklistFrom($addresses)
{
    require_once dirname(__FILE__) . '/../lib/base.php';
    if (!empty($GLOBALS['ingo_shares'])) {
        $_SESSION['ingo']['current_share'] = $signature;
    }

    global $ingo_storage;

    /* Check for '@' entries in $addresses - this would call all mail to
     * be blacklisted which is most likely not what is desired. */
    $addresses = array_unique($addresses);
    $key = array_search('@', $addresses);
    if ($key !== false) {
        unset($addresses[$key]);
    }

    if (!empty($addresses)) {
        $blacklist = &$ingo_storage->retrieve(Ingo_Storage::ACTION_BLACKLIST);
        $ret = $blacklist->setBlacklist(array_merge($blacklist->getBlacklist(), $addresses));
        if (is_a($ret, 'PEAR_Error')) {
            $GLOBALS['notification']->push($ret, $ret->getCode());
        } else {
            $ingo_storage->store($blacklist);
            Ingo::updateScript();
            foreach ($addresses as $from) {
                $GLOBALS['notification']->push(sprintf(_("The address \"%s\" has been added to your blacklist."), $from));
            }
        }
    }
}

/**
 * Add addresses to the white list
 *
 * @param string $addresses    The addresses to add
 */
function _ingo_whitelistFrom($addresses)
{
    require_once dirname(__FILE__) . '/../lib/base.php';
    if (!empty($GLOBALS['ingo_shares'])) {
        $_SESSION['ingo']['current_share'] = $signature;
    }

    global $ingo_storage;

    $whitelist = &$ingo_storage->retrieve(Ingo_Storage::ACTION_WHITELIST);
    $ret = $whitelist->setWhitelist(array_merge($whitelist->getWhitelist(), $addresses));
    if (is_a($ret, 'PEAR_Error')) {
        $GLOBALS['notification']->push($ret, $ret->getCode());
    } else {
        $ingo_storage->store($whitelist);
        Ingo::updateScript();
        foreach ($addresses as $from) {
            $GLOBALS['notification']->push(sprintf(_("The address \"%s\" has been added to your whitelist."), $from));
        }
    }
}

/**
 * Can this driver perform on-demand filtering?
 *
 * @return boolean  True if perform() is available, false if not.
 */
function _ingo_canApplyFilters()
{
    require_once dirname(__FILE__) . '/../lib/base.php';

    $ingo_script = Ingo::loadIngoScript();
    if ($ingo_script) {
        return $ingo_script->performAvailable();
    } else {
        return false;
    }
}

/**
 * Perform the filtering specified in the rules.
 *
 * @param array $params  The parameter array.
 *
 * @return boolean  True if filtering was performed, false if not.
 */
function _ingo_applyFilters($params = array())
{
    require_once dirname(__FILE__) . '/../lib/base.php';
    if (!empty($GLOBALS['ingo_shares'])) {
        $_SESSION['ingo']['current_share'] = $signature;
    }

    $ingo_script = Ingo::loadIngoScript();
    if ($ingo_script) {
        return $ingo_script->perform($params);
    }
}

/**
 * Set vacation
 *
 * @param array $info  Vacation details
 *
 * @return boolean  True on success.
 */
function _ingo_setVacation($info)
{
    require_once dirname(__FILE__) . '/../lib/base.php';
    if (!empty($GLOBALS['ingo_shares'])) {
        $_SESSION['ingo']['current_share'] = $signature;
    }

    global $ingo_storage;

    /* Get vacation filter. */
    $filters = &$ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
    $vacation_rule_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

    if (empty($info)) {
        return true;
    }

    /* Set vacation object and rules. */
    $vacation = &$ingo_storage->retrieve(Ingo_Storage::ACTION_VACATION);

    /* Make sure we have at least one address. */
    if (empty($info['addresses'])) {
        require_once 'Horde/Identity.php';
        $identity = &Identity::singleton('none');
        $info['addresses'] = implode("\n", $identity->getAll('from_addr'));
        /* Remove empty lines. */
        $info['addresses'] = preg_replace('/\n+/', "\n", $info['addresses']);
        if (empty($addresses)) {
            $info['addresses'] = Auth::getAuth();
        }
    }

    $vacation->setVacationAddresses($addresses);

    if (isset($info['days'])) {
        $vacation->setVacationDays($info['days']);
    }
    if (isset($info['excludes'])) {
        $vacation->setVacationExcludes($info['excludes']);
    }
    if (isset($info['ignorelist'])) {
        $vacation->setVacationIgnorelist(($info['ignorelist'] == 'on'));
    }
    if (isset($info['reason'])) {
        $vacation->setVacationReason($info['reason']);
    }
    if (isset($info['subject'])) {
        $vacation->setVacationSubject($info['subject']);
    }
    if (isset($info['start'])) {
        $vacation->setVacationStart($info['start']);
    }
    if (isset($info['end'])) {
        $vacation->setVacationEnd($info['end']);
    }

    $filters->ruleEnable($vacation_rule_id);
    $result = $ingo_storage->store($filters);
    if (!is_a($result, 'PEAR_Error')) {
        if ($GLOBALS['prefs']->getValue('auto_update')) {
            Ingo::updateScript();
        }

        /* Update the timestamp for the rules. */
        $_SESSION['ingo']['change'] = time();
    }

    return $result;
}

/**
 * Disable vacation
 *
 * @return boolean  True on success.
 */
function _ingo_disableVacation()
{
    require_once dirname(__FILE__) . '/../lib/base.php';
    if (!empty($GLOBALS['ingo_shares'])) {
        $_SESSION['ingo']['current_share'] = $signature;
    }

    global $ingo_storage;

    /* Get vacation filter. */
    $filters = &$ingo_storage->retrieve(Ingo_Storage::ACTION_FILTERS);
    $vacation_rule_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

    $filters->ruleDisable($vacation_rule_id);
    $result = $ingo_storage->store($filters);
    if (!is_a($result, 'PEAR_Error')) {
        if ($GLOBALS['prefs']->getValue('auto_update')) {
            Ingo::updateScript();
        }

        /* Update the timestamp for the rules. */
        $_SESSION['ingo']['change'] = time();
    }

    return $result;
}
