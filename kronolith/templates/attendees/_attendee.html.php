 <tr>
  <td class="nowrap"><?php echo $attendee['deleteLink'] . ' ' . $attendee['editLink'] ?></td>
  <td><?php echo $this->h($attendee['name']) ?></td>
  <td>
   <label for="attendance_<?php echo $attendeeCounter ?>" class="hidden"><?php echo _("Attendance") ?></label>
   <select id="attendance_<?php echo $attendeeCounter ?>" name="attendance_<?php echo $attendeeCounter ?>" onchange="performAction('changeatt', document.attendeesForm.attendance_<?php echo $attendeeCounter ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($attendee['id']) ?>'));">
<?php foreach ($attendee['roles'] as $role => $info): ?>
     <option value="<?php echo $role ?>"<?php if ($info['selected']) echo ' selected="selected"' ?>><?php echo $info['label'] ?></option>
<?php endforeach ?>
   </select>
  </td>
  <td>
   <select name="response_<?php echo $attendeeCounter ?>" onchange="performAction('changeresp', document.attendeesForm.response_<?php echo $attendeeCounter ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($attendee['id']) ?>'));">
<?php foreach ($attendee['responses'] as $response => $info): ?>
    <option value="<?php echo $response ?>"<?php if ($info['selected']) echo ' selected="selected"' ?>><?php echo $info['label'] ?></option>
<?php endforeach ?>
   </select>
  </td>
 </tr>
