<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

foreach ($zonedata[$type] as $record) {

    $params = array('id' => $record['id'], 'rectype' => $type);
    echo '<tr>' . "\n";
    echo '<td>' . Horde::link(Horde_Util::addParameter($edit, $params))
                . Horde::img('edit.png', _("Edit")) . '</a> '
                . Horde::link(Horde_Util::addParameter($delete, $params))
                . Horde::img('delete.png', _("Delete")) . '</a></td>';
    echo '<td>' . $rectypes[$type] . '</td>';

    foreach ($fields as $field => $fdata) {

        if ((($fdata['infoset'] != 'basic') && !$_SESSION['beatnik']['expertmode']) || $fdata['type'] == 'hidden') {
            continue;
        }

        echo '<td>' . "\n";

        if (!isset($record[$field])) {
            continue;
        }

        if (is_array($record[$field])) {
            foreach ($record[$field] as $entry) {
                echo $entry;
            }
        } else {
            echo $record[$field];
        }

        echo '</td>' . "\n";
    }

    echo '</tr>' . "\n";
}
