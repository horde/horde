<?php
/**
 * IMP-specific prefs handling.
 *
 * Copyright 2010-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  IMP
 */
class IMP_Prefs_Ui
{
    /**
     * Determine active prefs when displaying a group.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsGroup($ui)
    {
        global $conf, $injector, $prefs, $registry, $session;

        $cprefs = $ui->getChangeablePrefs();
        $imp_imap = $injector->getInstance('IMP_Factory_Imap')->create();

        foreach ($cprefs as $val) {
            switch ($val) {
            case 'composetemplates_new':
                $ui->prefs[$val]['xurl'] = IMP::composeLink(array(), array(
                    'actionID' => 'template_new',
                    'type' => 'template_new'
                ));
                break;

            case 'filters_blacklist_link':
                try {
                    $ui->prefs[$val]['url'] = $registry->link('mail/showBlacklist');
                } catch (Horde_Exception $e) {}
                break;

            case 'filters_link':
                try {
                    $ui->prefs[$val]['url'] = $registry->link('mail/showFilters');
                } catch (Horde_Exception $e) {}
                break;

            case 'filters_whitelist_link':
                try {
                    $ui->prefs[$val]['url'] = $registry->link('mail/showWhitelist');
                } catch (Horde_Exception $e) {}
                break;

            case 'time_format':
                /* Set the timezone on this page so the output uses the
                 * configured time zone's time, not the system's time zone. */
                $registry->setTimeZone();
                break;
            }
        }
    }
}
