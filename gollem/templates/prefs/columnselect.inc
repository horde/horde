<?php
/**
 * $Horde: gollem/templates/prefs/columnselect.inc,v 1.16 2009/07/09 20:51:08 slusarz Exp $
 *
 * Prefs_UI:: always calls a .inc file, so we need to use this file to
 * generate the data needed to display the template.
 */

if (!$prefs->isLocked('columns')) {
    $js_columns = '';
    $sources = Gollem::displayColumns();
    $source_count = 0;

    foreach ($GLOBALS['gollem_backends'] as $source => $info) {
        $js_columns .= "columns[$source_count] = [];";
        $js_columns .= "columns[$source_count][0] = '$source';";

        $column_count = 1;
        $selected = isset($sources[$source]) ? array_flip($sources[$source]) : array();
        foreach ($info['attributes'] as $null => $column) {
            $marked = isset($selected[$column]) ? 'true' : 'false';
            $js_columns .= "columns[$source_count][$column_count] = ['$column', '" . addslashes($column) . "', $marked, " . (($marked === 'true') ? $selected[$column] : 'null') . "];";
            ++$column_count;
        }
        ++$source_count;
    }

    Horde::addScriptFile('columnselect.js', 'gollem');
    Horde::addInlineScript($js_columns);

    $t = new Horde_Template();
    $t->setOption('gettext', true);

    $be = array();
    foreach ($GLOBALS['gollem_backends'] as $key => $info) {
        $be[] = array('val' => $key, 'label' => $info['name']);
    }
    $t->set('be', $be);

    $t->set('columns', $prefs->getValue('columns'));

    $imagedir = $registry->getImageDir('horde');
    $t->set('add', Horde::img('rhand.png', _("Add column"), null, $imagedir));
    $t->set('remove', Horde::img('lhand.png', _("Remove column"), null, $imagedir));
    $t->set('left', Horde::img('nav/up.png', _("Move left"), null, $imagedir));
    $t->set('right', Horde::img('nav/down.png', _("Move right"), null, $imagedir));

    echo $t->fetch(GOLLEM_TEMPLATES . '/prefs/columnselect.html');
}
