<?php
class Fima_Application extends Horde_Regsitry_Application
{
    public $version = '1.0.1';

    /**
     * Special preferences handling on update.
     *
     * @param string $item      The preference name.
     * @param boolean $updated  Set to true if preference was updated.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecial($item, $updated)
    {
        switch ($item) {
        case 'ledgerselect':
            $active_ledger = Horde_Util::getFormData('active_ledger');
            if (!is_null($active_ledger)) {
                $ledgers = Fima::listLedgers();
                if (is_array($ledgers) &&
                    array_key_exists($active_ledger, $ledgers)) {
                    $GLOBALS['prefs']->setValue('active_ledger', $active_ledger);
                    return true;
                }
            }
            break;

        case 'closedperiodselect':
            $period = Horde_Util::getFormData('closedperiod');
            if ((int)$period['year'] > 0 && (int)$period['month'] > 0) {
                $period = mktime(0, 0, 0, $period['month'] + 1, 0, $period['year']);
            } else {
                $period = 0;
            }
            $GLOBALS['prefs']->setValue('closed_period', $period);
            return true;
        }

        return $updated;
    }

    /**
     * Generate the menu to use on the prefs page.
     *
     * @return Horde_Menu  A Horde_Menu object.
     */
    public function prefsMenu()
    {
        return Fima::getMenu();
    }

}
