<?php
/**
 * Copyright 2005-2007 Ben Klang <ben@alkaloid.net>
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 */

if (Beatnik::needCommit($_SESSION['beatnik']['curdomain']['zonename'])) {
    $commit = Horde_Util::addParameter(Horde::url('commit.php'), array('domain' => 'current'));
    echo Horde::link($commit, _('Commit Changes'), 'button') . _('Commit Changes') . '</a><br /><br />';
}
?>

<div class="header">
<?php
$params = array('id' => $_SESSION['beatnik']['curdomain']['id'],
                'rectype' => 'soa');
echo $_SESSION['beatnik']['curdomain']['zonename'] . '&nbsp;'
     . Horde::link(Horde_Util::addParameter($edit, $params))
     . Horde::img('edit.png', _("Edit")) . '</a> '
     . Horde::link(Horde_Util::addParameter($autogen, $params))
     . Horde::img('devel.png', _("Autogenerate")) . '</a> '
     . Horde::link(Horde_Util::addParameter($delete, $params))
     . Horde::img('delete.png', _("Delete")) . '</a></td>';
?>
</div>

<?php
if ($_SESSION['beatnik']['expertmode']) {
    echo '<table class="striped">' . "\n";
    foreach (Beatnik::getRecFields('soa') as $key => $value) {
        if (isset($_SESSION['beatnik']['curdomain'][$key])) {
            echo '<tr><td>' . $value['name'] . '</td><td>' . $_SESSION['beatnik']['curdomain'][$key] . '</td></tr>' . "\n";
        }
    }
    echo '</table><br />' . "\n";
}
?>
<table class="striped">
<thead>
<tr>
    <th><?php echo _("Actions")?></th>
    <th><?php echo _("Type") ?></th>
    <?php
    foreach ($fields as $field => $fdata) {
        if ((($fdata['infoset'] == 'basic') || $_SESSION['beatnik']['expertmode']) && $fdata['type'] != 'hidden') {
            echo '<th>' . $fdata['name'] . '</th>' . "\n";
        }
    }
    ?>
</tr>
</thead>
<tbody>
