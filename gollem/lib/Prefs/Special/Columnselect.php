<?php
/**
 * Special prefs handling for the 'columnselect' preference.
 *
 * Copyright 2012-2013 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/gpl GPL
 * @package  Gollem
 */
class Gollem_Prefs_Special_Columnselect implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
        Horde_Core_Prefs_Ui_Widgets::sourceInit();
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        $sources = array();
        foreach (Gollem_Auth::getBackend() as $source => $info) {
            $selected = $unselected = array();
            $selected_list = array_flip(Gollem::getColumns($source));
            foreach ($info['attributes'] as $column) {
                if (isset($selected_list[$column])) {
                    $selected[$column] = $column;
                } else {
                    $unselected[$column] = $column;
                }
            }
            $sources[$source] = array(
                'label' => $info['name'],
                'selected' => $selected,
                'unselected' => $unselected,
            );
        }

        return Horde_Core_Prefs_Ui_Widgets::source(array(
            'mainlabel' => _("Choose which columns to display, and in what order:"),
            'selectlabel' => _("These columns will display in this order:"),
            'sourcelabel' => _("Select a backend:"),
            'sources' => $sources,
            'unselectlabel' => _("Columns that will not be displayed:")
        ));
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        if (!isset($ui->vars->sources)) {
            return false;
        }

        $backends = Gollem_Auth::getBackend();
        if (count($backends) == 1) {
            $sources = json_decode($ui->vars->sources);
            array_unshift($sources, key($backends));
            $sources = json_encode(array($sources));
        } else {
            $sources = $ui->vars->sources;
        }
        $GLOBALS['prefs']->setValue('columns', $sources);

        return true;
    }

}
