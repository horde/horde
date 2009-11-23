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
class Ingo_Api extends Horde_Registry_Api
{
    /**
     * Links.
     *
     * @var array
     */
    public $links = array(
        'showBlacklist' => '%application%/blacklist.php',
        'showWhitelist' => '%application%/whitelist.php',
        'showFilters' => '%application%/filters.php',
        'showVacation' => '%application%/vacation.php'
    );

    /**
     * Add addresses to the blacklist.
     *
     * @param string $addresses  The addresses to add to the blacklist.
     */
    public function blacklistFrom($addresses)
    {
        require_once dirname(__FILE__) . '/../lib/base.php';
        if (!empty($GLOBALS['ingo_shares'])) {
            $_SESSION['ingo']['current_share'] = $signature;
        }

        /* Check for '@' entries in $addresses - this would call all mail to
         * be blacklisted which is most likely not what is desired. */
        $addresses = array_unique($addresses);
        $key = array_search('@', $addresses);
        if ($key !== false) {
            unset($addresses[$key]);
        }

        if (!empty($addresses)) {
            $blacklist = &$GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_BLACKLIST);
            $ret = $blacklist->setBlacklist(array_merge($blacklist->getBlacklist(), $addresses));
            if (is_a($ret, 'PEAR_Error')) {
                $GLOBALS['notification']->push($ret, $ret->getCode());
            } else {
                $GLOBALS['ingo_storage']->store($blacklist);
                Ingo::updateScript();
                foreach ($addresses as $from) {
                    $GLOBALS['notification']->push(sprintf(_("The address \"%s\" has been added to your blacklist."), $from));
                }
            }
        }
    }

    /**
     * Add addresses to the whitelist.
     *
     * @param string $addresses  The addresses to add to the whitelist.
     */
    public function whitelistFrom($addresses)
    {
        require_once dirname(__FILE__) . '/../lib/base.php';
        if (!empty($GLOBALS['ingo_shares'])) {
            $_SESSION['ingo']['current_share'] = $signature;
        }

        $whitelist = &$GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_WHITELIST);
        $ret = $whitelist->setWhitelist(array_merge($whitelist->getWhitelist(), $addresses));
        if (is_a($ret, 'PEAR_Error')) {
            $GLOBALS['notification']->push($ret, $ret->getCode());
        } else {
            $GLOBALS['ingo_storage']->store($whitelist);
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
    public function canApplyFilters()
    {
        require_once dirname(__FILE__) . '/../lib/base.php';

        $ingo_script = Ingo::loadIngoScript();
        return $ingo_script
            ? $ingo_script->performAvailable()
            : false;
    }

    /**
     * Perform the filtering specified in the rules.
     *
     * @param array $params  The parameter array.
     *
     * @return boolean  True if filtering was performed, false if not.
     */
    public function applyFilters($params = array())
    {
        require_once dirname(__FILE__) . '/../lib/base.php';
        if (!empty($GLOBALS['ingo_shares'])) {
            $_SESSION['ingo']['current_share'] = $signature;
        }

        $ingo_script = Ingo::loadIngoScript();
        return $ingo_script
            ? $ingo_script->perform($params)
            : false;
    }

    /**
     * Set vacation
     *
     * @param array $info  Vacation details.
     *
     * @return boolean  True on success.
     */
    public function setVacation($info)
    {
        require_once dirname(__FILE__) . '/../lib/base.php';
        if (!empty($GLOBALS['ingo_shares'])) {
            $_SESSION['ingo']['current_share'] = $signature;
        }

        if (empty($info)) {
            return true;
        }

        /* Get vacation filter. */
        $filters = &$GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_rule_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

        /* Set vacation object and rules. */
        $vacation = &$GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_VACATION);

        /* Make sure we have at least one address. */
        if (empty($info['addresses'])) {
            $identity = Horde_Prefs_Identity::singleton('none');
            $info['addresses'] = implode("\n", $identity->getAll('from_addr'));
            /* Remove empty lines. */
            $info['addresses'] = preg_replace('/\n+/', "\n", $info['addresses']);
            if (empty($addresses)) {
                $info['addresses'] = Horde_Auth::getAuth();
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
        $result = $GLOBALS['ingo_storage']->store($filters);
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
    public function disableVacation()
    {
        require_once dirname(__FILE__) . '/../lib/base.php';
        if (!empty($GLOBALS['ingo_shares'])) {
            $_SESSION['ingo']['current_share'] = $signature;
        }

        /* Get vacation filter. */
        $filters = &$GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_rule_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

        $filters->ruleDisable($vacation_rule_id);
        $result = $GLOBALS['ingo_storage']->store($filters);
        if (!is_a($result, 'PEAR_Error')) {
            if ($GLOBALS['prefs']->getValue('auto_update')) {
                Ingo::updateScript();
            }

            /* Update the timestamp for the rules. */
            $_SESSION['ingo']['change'] = time();
        }

        return $result;
    }

}
