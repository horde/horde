<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 */
?>
<tr>
<td>
    <?php
        echo Horde::link($autourl) .
             Horde::img('devel.png', _("Autogenerate"), '', $img_dir) . '</a> ' .
             Horde::link($deleteurl) .
             Horde::img('delete.png', _("Delete"), '', $img_dir) . '</a> ' .
             Horde::link($editurl) .
             Horde::img('edit.png', _("Edit"), '', $img_dir) . '</a>';
    ?>
</td>
<?php
foreach ($fields as $key => $field) {
    if ($key == 'zonename') {
        echo '<td>' . Horde::link($viewurl) . $domain['zonename'] . '</a></td>';
    } else {
        echo '<td>' . $domain[$key] . '</td>';
    }
}
?>
</tr>
