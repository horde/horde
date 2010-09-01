<tr class="<?php echo $style ?>">
  <td>
    <?php
    if ($class['_edit']) {
        $label = _("New Entry");
        echo Horde::link(Horde_Util::addParameter(Horde::url('add.php'), 'class', $class['_id']), $label) . Horde::img('add.png', $label) . '</a>';
    }
    ?>
  </td>
  <td>
    <?php
    if ($class['_edit']) {
        $label = sprintf(_("Edit \"%s\""), $class['name']);
        echo Horde::link(Horde_Util::addParameter(Horde::url('classes/edit.php'), 'c', $class['_id']), $label) . Horde::img('edit.png', $label) . '</a>';
    }
    ?>
  </td>
<?php if (in_array('semesterstart', $class_columns)): ?>
  <td><?php echo strftime($dateFormat, $class['start']) ?>&nbsp;</td>
<?php endif; if (in_array('semesterend', $class_columns)): ?>
  <td><?php echo strftime($dateFormat, $class['end']) ?>&nbsp;</td>
<?php endif; ?>
  <td>
    <?php
    $label = sprintf(_("View Entries in \"%s\""), $class['name']);
    $params = array(
        'actionID' => 'search',
        'class'    => $class['_id'],
        'student'  => 'all'
    );
    echo Horde::link(Horde_Util::addParameter(Horde::url('search.php'), $params), $label) . htmlspecialchars($class['name']) . '</a>';
    ?>
  </td>
<?php if (in_array('lastentry', $student_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('summarks', $student_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('sumabsences', $student_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('grade', $class_columns)): ?>
  <td><?php echo htmlspecialchars($class['grade']) ?>&nbsp;</td>
<?php endif; if (in_array('semester', $class_columns)): ?>
  <td><?php echo htmlspecialchars($class['semester']) ?>&nbsp;</td>
<?php endif; if (in_array('location', $class_columns)): ?>
  <td><?php echo htmlspecialchars($class['location']) ?>&nbsp;</td>
<?php endif; if (in_array('category', $class_columns)): ?>
  <td class="category<?php echo md5($class['category']) ?>"><?php echo isset($class['category']) ? htmlspecialchars($class['category']) : _("Unfiled") ?></td>
<?php endif; ?>
</tr>
