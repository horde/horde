<tr class="<?php echo $style ?>">
  <td>
    <?php
    if ($entry['_edit']) {
        $label = _("Edit Entry");
        $params = array(
            'view'  => 'EditEntry',
            'entry' => $entry['_id']
        );
        echo Horde::link(Horde_Util::addParameter(Horde::url('entry.php'), $params), $label) . Horde::img('edit.png', $label) . '</a>';
    }
    ?>
  </td>
  <td><?php echo htmlspecialchars($entry['class']) ?>&nbsp;</td>
  <td>
    <?php
    $params = array(
        'view'  => 'Entry',
        'entry' => $entry['_id']
    );
    echo Horde::link(Horde_Util::addParameter(Horde::url('entry.php'), $params), _("View Entry")) . htmlspecialchars($entry['student']) . '</a>&nbsp;';
    ?>
 </td>
  <td><?php echo htmlspecialchars($entry['date']) ?>&nbsp;</td>
  <td><?php echo htmlspecialchars($entry['typename']) ?>&nbsp;</td>
  <td><?php echo htmlspecialchars($entry['details']) ?>&nbsp;</td>
</tr>
