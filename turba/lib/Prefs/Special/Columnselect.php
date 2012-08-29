<?php
/**
 * Special prefs handling for the 'columnselect' preference.
 *
 * Copyright 2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (ASL).  If you did
 * did not receive this file, see http://www.horde.org/licenses/apache.
 *
 * @author   Michael Slusarz <slusarz@horde.org>
 * @category Horde
 * @license  http://www.horde.org/licenses/apache ASL
 * @package  Turba
 */
class Turba_Prefs_Special_Columnselect implements Horde_Core_Prefs_Ui_Special
{
    /**
     */
    public function init(Horde_Core_Prefs_Ui $ui)
    {
        global $page_output;

        $page_output->addScriptFile('scriptaculous/effects.js', 'horde');
        $page_output->addScriptFile('scriptaculous/dragdrop.js', 'horde');
        $page_output->addScriptFile('columnprefs.js');
    }

    /**
     */
    public function display(Horde_Core_Prefs_Ui $ui)
    {
        global $attributes, $cfgSources, $injector, $prefs;

        $sources = Turba::getColumns();

        $t = $injector->createInstance('Horde_Template');
        $t->setOption('gettext', true);

        $t->set('columns', htmlspecialchars($prefs->getValue('columns')));

        $col_list = $cols = array();
        foreach ($cfgSources as $source => $info) {
            $col_list[] = array(
                'first' => empty($col_list),
                'source' => htmlspecialchars($source),
                'title' => htmlspecialchars($info['title'])
            );

            // First the selected columns in their current order.
            $i = 0;
            $inputs = array();

            if (isset($sources[$source])) {
                $selected = array_flip($sources[$source]);
                foreach ($sources[$source] as $column) {
                    if ((substr($column, 0, 2) == '__') ||
                        ($column == 'name')) {
                            continue;
                        }

                    $inputs[] = array(
                        'checked' => isset($selected[$column]),
                        'column' => htmlspecialchars($column),
                        'i' => $i,
                        'label' => htmlspecialchars($attributes[$column]['label'])
                    );
                }
            } else {
                // Need to unset this for the loop below, otherwise
                // selected columns from another source could interfere
                unset($selected);
            }

            // Then the unselected columns in source order.
            foreach (array_keys($info['map']) as $column) {
                if ((substr($column, 0, 2) == '__') ||
                    ($column == 'name') ||
                    isset($selected[$column])) {
                        continue;
                    }

                $inputs[] = array(
                    'checked' => isset($selected[$column]),
                    'column' => htmlspecialchars($column),
                    'i' => $i,
                    'label' => htmlspecialchars($attributes[$column]['label'])
                );
            }

            $cols[] = array(
                'first' => empty($cols),
                'inputs' => $inputs,
                'source' => htmlspecialchars($source)
            );
        }

        if (!empty($col_list)) {
            $t->set('col_list', $col_list);
            $t->set('cols', $cols);
        }

        return $t->fetch(TURBA_TEMPLATES . '/prefs/column.html');
    }

    /**
     */
    public function update(Horde_Core_Prefs_Ui $ui)
    {
        global $prefs;

        if (!isset($ui->vars->columns)) {
            return false;
        }

        $prefs->setValue('columns', $ui->vars->columns);
        return true;
    }

}
