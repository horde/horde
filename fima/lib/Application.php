<?php
class Fima_Application extends Horde_Regsitry_Application
{
    public $version = '1.0.1';

    /**
     * Special preferences handling on update.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return boolean  True if preference was updated.
     */
    public function prefsSpecialUpdate($ui, $item)
    {
        switch ($item) {
        case 'ledgerselect':
            if (isset($ui->vars->active_ledger)) {
                $ledgers = Fima::listLedgers();
                if (is_array($ledgers) &&
                    array_key_exists($ui->vars->active_ledger, $ledgers)) {
                    $GLOBALS['prefs']->setValue('active_ledger', $ui->vars->active_ledger);
                    return true;
                }
            }
            break;

        case 'closedperiodselect':
            $period = $ui->vars->closedperiod;
            $period = ((int)$period['year'] > 0 && (int)$period['month'] > 0)
                ? mktime(0, 0, 0, $period['month'] + 1, 0, $period['year'])
                : 0;
            $GLOBALS['prefs']->setValue('closed_period', $period);
            return true;
        }
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
