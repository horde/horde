<?php
class Fima_Application extends Horde_Regsitry_Application
{
    public $version = '1.0.1';

    /**
     * Populate dynamically-generated preference values.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     */
    public function prefsEnum($ui)
    {
        switch ($ui->group) {
        case 'share':
            if (!$GLOBALS['prefs']->isLocked('active_ledger')) {
                $ui->override['active_ledger'] = Fima::listLedgers();
            }
            break;
        }
    }

    /**
     * Generate code used to display a special preference.
     *
     * @param Horde_Core_Prefs_Ui $ui  The UI object.
     * @param string $item             The preference name.
     *
     * @return string  The HTML code to display on the prefs page.
     */
    public function prefsSpecial($ui, $item)
    {
        switch ($item) {
        case 'closedperiodselect':
            return _("Closed by period:") .
                '<br />' .
                Fima::buildDateWidget('closedperiod', (int)$GLOBALS['prefs']->getValue('closed_period'), '', _("None"), true) .
                '</select><br /><br />';
        }

        return '';
    }

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
