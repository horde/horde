<tr class="<?php echo $style ?>">
  <td>
    <?php
    if ($class['_edit']) {
        $label = _("New Entry");
        echo Horde::link(Horde_Util::addParameter(Horde::url('add.php'), array('class'=>$class['_id'], 'student'=>$student['__key'])), $label) . Horde::img('add.png', $label) . '</a>';
    }
    ?>
  </td>
  <td>
    <?php
    if ($registry->hasMethod('contacts/show')) {
        $label = sprintf(_("Show \"%s\""), $student[$conf['addresses']['name_field']]);
        $url = $registry->link('contacts/show', array('source' => $class['address_book'],
                                                      'key' => $student['__key']));
        echo Horde::link($url, $label) . Horde::img('user.png', $label) . '</a>';
    }
    ?>
  </td>
<?php if (in_array('semesterstart', $class_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('semesterend', $class_columns)): ?>
  <td>&nbsp;</td>
<?php endif; ?>
  <td>
    <?php
    $label = sprintf(_("View Entries for \"%s\""), $student[$conf['addresses']['name_field']]);
    $params = array(
        'actionID' => 'search',
        'class'    => $class['_id'],
        'student'  => $student['__key']
    );
    echo $treeIcon . '&nbsp;' . Horde::link(Horde_Util::addParameter(Horde::url('search.php'), $params), $label) . htmlspecialchars($student[$conf['addresses']['name_field']]) . '</a>';
    ?>
  </td>
<?php if (in_array('lastentry', $student_columns)): ?>
  <td><?php echo isset($student['_lastentry']) ? strftime($dateFormat, $student['_lastentry']) : '' ?>&nbsp;</td>
<?php endif; if (in_array('summarks', $student_columns)): ?>
  <td style="text-align: center;"><?php echo $student['_summarks'] != '' ? $student['_summarks'] : '&nbsp;' ?></td>
<?php endif; if (in_array('sumabsences', $student_columns)): ?>
  <td style="text-align: center;"><?php echo $student['_sumabsences'][2] ?></td>
<?php endif; if (in_array('grade', $class_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('semester', $class_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('location', $class_columns)): ?>
  <td>&nbsp;</td>
<?php endif; if (in_array('category', $class_columns)): ?>
  <td>&nbsp;</td>
<?php endif; ?>
</tr>
