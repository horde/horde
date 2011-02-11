<?php
/**
 * Ingo external API interface.
 *
 * This file defines Ingo's external API interface. Other applications
 * can interact with Ingo through this API.
 *
 * See the enclosed file LICENSE for license information (ASL).  If you
 * did not receive this file, see http://www.horde.org/licenses/asl.php.
 *
 * @package Ingo
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
        if (!empty($addresses)) {
            try {
                $blacklist = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_BLACKLIST);
                $blacklist->setBlacklist(array_merge($blacklist->getBlacklist(), $addresses));
                $GLOBALS['ingo_storage']->store($blacklist);
                Ingo::updateScript();
                foreach ($addresses as $from) {
                    $GLOBALS['notification']->push(sprintf(_("The address \"%s\" has been added to your blacklist."), $from));
                }
            } catch (Ingo_Exception $e) {
                $GLOBALS['notification']->push($e);
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
        try {
            $whitelist = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_WHITELIST);
            $whitelist->setWhitelist(array_merge($whitelist->getWhitelist(), $addresses));
            $GLOBALS['ingo_storage']->store($whitelist);
            Ingo::updateScript();
            foreach ($addresses as $from) {
                $GLOBALS['notification']->push(sprintf(_("The address \"%s\" has been added to your whitelist."), $from));
            }
        } catch (Ingo_Exception $e) {
            $GLOBALS['notification']->push($e);
        }
    }

    /**
     * Can this driver perform on-demand filtering?
     *
     * @return boolean  True if perform() is available, false if not.
     */
    public function canApplyFilters()
    {
        try {
            return Ingo::loadIngoScript()->performAvailable();
        } catch (Ingo_Exception $e) {
            return false;
        }
    }

    /**
     * Perform the filtering specified in the rules.
     *
     * @param array $params  The parameter array:
     * <pre>
     * 'filter_seen' - TODO
     * 'show_filter_msg' - TODO
     * </pre>
     *
     * @return boolean  True if filtering was performed, false if not.
     */
    public function applyFilters($params = array())
    {
        try {
            $ingo_script = Ingo::loadIngoScript();
        } catch (Ingo_Exception $e) {
            return false;
        }

        $params = array_merge(array(
            'filter_seen' => $GLOBALS['prefs']->getValue('filter_seen'),
            'show_filter_msg' => $GLOBALS['prefs']->getValue('show_filter_msg')
        ), $params);

        return $ingo_script->perform($params);
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
        if (empty($info)) {
            return true;
        }

        /* Get vacation filter. */
        $filters = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_rule_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

        /* Set vacation object and rules. */
        $vacation = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_VACATION);

        /* Make sure we have at least one address. */
        if (empty($info['addresses'])) {
            $identity = $GLOBALS['injector']->getInstance('Horde_Core_Factory_Identity')->create();
            /* Remove empty lines. */
            $info['addresses'] = preg_replace('/\n{2,}/', "\n", implode("\n", $identity->getAll('from_addr')));
            if (empty($addresses)) {
                $info['addresses'] = $GLOBALS['registry']->getAuth();
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
            $vacation->setVacationIgnorelist($info['ignorelist'] == 'on');
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

        try {
            $GLOBALS['ingo_storage']->store($filters);

            if ($GLOBALS['prefs']->getValue('auto_update')) {
                Ingo::updateScript();
            }

            /* Update the timestamp for the rules. */
            $GLOBALS['session']->set('ingo', 'change', time());

            return true;
        } catch (Ingo_Exception $e) {}

        return false;
    }

    /**
     * Disable vacation
     *
     * @return boolean  True on success.
     */
    public function disableVacation()
    {
        /* Get vacation filter. */
        $filters = $GLOBALS['ingo_storage']->retrieve(Ingo_Storage::ACTION_FILTERS);
        $vacation_rule_id = $filters->findRuleId(Ingo_Storage::ACTION_VACATION);

        $filters->ruleDisable($vacation_rule_id);

        try {
            $GLOBALS['ingo_storage']->store($filters);

            if ($GLOBALS['prefs']->getValue('auto_update')) {
                Ingo::updateScript();
            }

            /* Update the timestamp for the rules. */
            $GLOBALS['session']->set('ingo', 'change', time());

            return true;
        } catch (Ingo_Exception $e) {}

        return false;
    }

}
