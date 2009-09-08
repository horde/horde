<script type="text/javascript">
function performAction(id, value)
{
    document.attendeesForm.actionID.value = id;
    document.attendeesForm.actionValue.value = value;
    document.attendeesForm.submit();
    return false;
}

function switchDate(date)
{
    document.attendeesForm.date.value = date;
    document.attendeesForm.submit();
    return false;
}

function switchView(view)
{
    document.attendeesForm.view.value = view;
    document.attendeesForm.submit();
    return false;
}

function switchDateView(view, date)
{
    document.attendeesForm.view.value = view;
    document.attendeesForm.date.value = date;
    document.attendeesForm.submit();
    return false;
}
</script>

<form method="post" action="attendees.php" name="attendeesForm">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="add" />
<input type="hidden" name="actionValue" value="" />
<input type="hidden" name="view" value="<?php echo htmlspecialchars($view) ?>" />
<input type="hidden" name="date" value="<?php echo $date->dateString() ?>" />

<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>

<table width="100%" cellspacing="0" class="linedRow">

<!-- attendee list header -->
<tr class="item nowrap leftAlign">
 <th width="2%">&nbsp;</th>
 <th width="48%"><?php echo htmlspecialchars(_("Attendee")) ?></th>
 <th width="25%"><?php echo htmlspecialchars(_("Attendance")) ?></th>
 <th width="25%"><?php echo htmlspecialchars(_("Response")) ?></th>
</tr>

<!-- attendees -->
<?php $i = 0; if (empty($attendees)): ?>
 <tr><td colspan="4"><em><?php echo _("No attendees") ?></em></td></tr>
<?php else: foreach ($attendees as $email => $status): ?>
 <tr>
  <td class="nowrap"><?php $name = strpos($email, '@') === false ? $status['name'] : $email; echo Horde::link('#', sprintf(_("Remove %s"), $name), '', '', "performAction('remove', decodeURIComponent('" . rawurlencode($email) . "')); return false;") . Horde::img('delete.png', '', null, $registry->getImageDir('horde')) . '</a> ' . Horde::link('#', sprintf(_("Edit %s"), $name), '', '', "performAction('edit', decodeURIComponent('" . rawurlencode($email) . "')); return false;") . Horde::img('edit.png', '', null, $registry->getImageDir('horde')) . '</a>' ?></td>
  <td><?php echo htmlspecialchars(empty($status['name']) ? $email : Horde_Mime_Address::trimAddress($status['name'] . (strpos($email, '@') === false ? '' : ' <' . $email . '>'))) ?></td>
  <td>
<label for="<?php echo "attendance_$i" ?>" class="hidden"><?php echo _("Attendance") ?></label>
   <select id="<?php echo "attendance_$i" ?>" name="<?php echo "attendance_$i" ?>" onchange="performAction('changeatt', document.attendeesForm.<?php echo "attendance_$i" ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($email) ?>'));">
    <option value="<?php echo Kronolith::PART_REQUIRED ?>"<?php if ($status['attendance'] == Kronolith::PART_REQUIRED)  echo ' selected="selected"' ?>><?php echo Kronolith::partToString(Kronolith::PART_REQUIRED) ?></option>
    <option value="<?php echo Kronolith::PART_OPTIONAL ?>"<?php if ($status['attendance'] == Kronolith::PART_OPTIONAL)  echo ' selected="selected"' ?>><?php echo Kronolith::partToString(Kronolith::PART_OPTIONAL) ?></option>
    <option value="<?php echo Kronolith::PART_NONE ?>"<?php if ($status['attendance'] == Kronolith::PART_NONE)  echo ' selected="selected"' ?>><?php echo Kronolith::partToString(Kronolith::PART_NONE) ?></option>
   </select>
  </td>
  <td>
   <select name="<?php echo "response_$i" ?>" onchange="performAction('changeresp', document.attendeesForm.<?php echo "response_$i" ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($email) ?>'));">
    <option value="<?php echo Kronolith::RESPONSE_ACCEPTED ?>"<?php if ($status['response'] == Kronolith::RESPONSE_ACCEPTED)  echo ' selected="selected"' ?>><?php echo Kronolith::responseToString(Kronolith::RESPONSE_ACCEPTED) ?></option>
    <option value="<?php echo Kronolith::RESPONSE_DECLINED ?>"<?php if ($status['response'] == Kronolith::RESPONSE_DECLINED)  echo ' selected="selected"' ?>><?php echo Kronolith::responseToString(Kronolith::RESPONSE_DECLINED) ?></option>
    <option value="<?php echo Kronolith::RESPONSE_TENTATIVE ?>"<?php if ($status['response'] == Kronolith::RESPONSE_TENTATIVE)  echo ' selected="selected"' ?>><?php echo Kronolith::responseToString(Kronolith::RESPONSE_TENTATIVE) ?></option>
    <option value="<?php echo Kronolith::RESPONSE_NONE ?>"<?php if ($status['response'] == Kronolith::RESPONSE_NONE)  echo ' selected="selected"' ?>><?php echo Kronolith::responsetoString(Kronolith::RESPONSE_NONE) ?></option>
   </select>
  </td>
 </tr>
 <?php $i++; ?>
<?php endforeach; ?>
<?php endif; ?>

<!-- resource list header -->
<tr class="item nowrap leftAlign">
 <th width="2%">&nbsp;</th>
 <th width="48%"><?php echo htmlspecialchars(_("Resource")) ?></th>
 <th colspan="2" width="50%"><?php echo htmlspecialchars(_("Status")) ?></th>
</tr>

<!--  resources -->
<?php $i = 0; if (empty($resources)): ?>
 <tr><td colspan="4"><em><?php echo _("No attendees") ?></em></td></tr>
<?php else: foreach ($resources as $id => $resource): ?>
 <tr>
  <td class="nowrap"><?php echo Horde::img('delete.png', '', null, $registry->getImageDir('horde')) . ' ' . Horde::img('edit.png', '', null, $registry->getImageDir('horde')) . ' ' ?></td>
  <td><?php echo htmlspecialchars($resource['name']) ?></td>
  <td colspan="2">
    <?php switch ($resource['response']) {
        case Kronolith::RESPONSE_ACCEPTED:
            echo _("Accepted");
            break;
        case Kronolith::RESPONSE_DECLINED:
            echo _("Declined");
            break;
    } ?>
  </td>
 </tr>

 <?php $i++; ?>
<?php endforeach; ?>
<?php endif; ?>
</table>

<br />

<table width="100%" cellspacing="2" class="nowrap control">
 <!-- Add Attendees -->
 <tr>
  <td class="rightAlign">&nbsp;<strong><?php $label = empty($editAttendee) ? _("Add attendees") : _("Edit attendee"); echo Horde::highlightAccessKey($label, $ak = Horde::getAccessKey($label)) ?></strong></td>
  <td>
   <input type="text" id="newAttendees" name="newAttendees" autocomplete="off" size="40" <?php if (!empty($ak)) echo 'accesskey="' . $ak . '" '; if (!empty($editAttendee)) echo 'value="' . htmlspecialchars($editAttendee) . '" '; ?>/>
   <span id="newAttendees_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
   <div id="newAttendees_results" class="autocomplete"></div>
  </td>
<?php if ($registry->hasMethod('contacts/search')): ?>
  <td align="center"><?php echo Horde::link('#', '', 'widget', null, 'window.open(\'' . Horde::applicationUrl('contacts.php') . '\', \'contacts\', \'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=270,left=100,top=100\'); return false;') . Horde::img('addressbook_browse.png') . '<br />' . _("Address Book") . '</a>' ?></td>
  <td width="100%"><?php echo _("resources link") ?></td>
<?php endif; ?>
 </tr>
</table>

<br />
<div>
 <input type="submit" class="button" name="addNew" value="<?php echo htmlspecialchars(_("Save Attendees")) ?>" />
 <input type="submit" class="button" name="addNewClose" value="<?php echo htmlspecialchars(_("Save and Finish")) ?>" />
 <?php if (!empty($attendees)): ?><input type="submit" class="button" name="clearAll" value="<?php echo htmlspecialchars(_("Clear all attendees")) ?>" /><?php endif; ?>
</div>

<br />
<?php echo $tabs->render($view) . $vfb_html ?>
</form>
