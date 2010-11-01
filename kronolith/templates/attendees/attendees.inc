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
<input type="hidden" name="date" value="<?php echo $date->dateString() . sprintf("%02d%02d00", $date->hour, $date->min) ?>" />
<input type="hidden" name="enddate" value="<?php echo $end->dateString() . sprintf("%02d%02d00", $end->hour, $end->min) ?>" />

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
<?php $i = 0; if (empty($GLOBALS['session']['kronolith:attendees'])): ?>
 <tr><td colspan="4"><em><?php echo _("No attendees") ?></em></td></tr>
<?php else: foreach ($GLOBALS['session']['kronolith:attendees'] as $email => $status): ?>
 <tr>
  <td class="nowrap"><?php $name = strpos($email, '@') === false ? $status['name'] : $email; echo Horde::url('#')->link(array('title' => sprintf(_("Remove %s"), $name), 'onclick' => "performAction('remove', decodeURIComponent('" . rawurlencode($email) . "')); return false;")) . Horde::img('delete.png') . '</a> ' . Horde::url('#')->link(array('title' => sprintf(_("Edit %s"), $name), 'onclick' => "performAction('edit', decodeURIComponent('" . rawurlencode($email) . "')); return false;")) . Horde::img('edit.png') . '</a>' ?></td>
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
 <th width="25%"><?php echo _("Attendance") ?></th>
 <th width="25%"><?php echo _("Response") ?></th>
</tr>

<!--  resources -->
<?php $i = 0; if (empty($resources)): ?>
 <tr><td colspan="4"><em><?php echo _("No attendees") ?></em></td></tr>
<?php else: foreach ($resources as $id => $resource): ?>
 <tr>
  <td class="nowrap"><?php echo Horde::url('#')->link(array('title' => sprintf(_("Remove %s"), $resource['name']), 'onclick' => "performAction('removeResource', " . $id . "); return false;")) . Horde::img('delete.png') ?></a></td>
  <td><?php echo htmlspecialchars($resource['name']) ?></td>
  <td>
  <label for="<?php echo "resourceattendance_$i" ?>" class="hidden"><?php echo _("Attendance") ?></label>
   <select id="<?php echo "resourceattendance_$i" ?>" name="<?php echo "resourceattendance_$i" ?>" onchange="performAction('changeResourceAtt', document.attendeesForm.<?php echo "resourceattendance_$i" ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($id) ?>'));">
    <option value="<?php echo Kronolith::PART_REQUIRED ?>"<?php if ($resource['attendance'] == Kronolith::PART_REQUIRED)  echo ' selected="selected"' ?>><?php echo Kronolith::partToString(Kronolith::PART_REQUIRED) ?></option>
    <option value="<?php echo Kronolith::PART_OPTIONAL ?>"<?php if ($resource['attendance'] == Kronolith::PART_OPTIONAL)  echo ' selected="selected"' ?>><?php echo Kronolith::partToString(Kronolith::PART_OPTIONAL) ?></option>
    <option value="<?php echo Kronolith::PART_NONE ?>"<?php if ($resource['attendance'] == Kronolith::PART_NONE)  echo ' selected="selected"' ?>><?php echo Kronolith::partToString(Kronolith::PART_NONE) ?></option>
   </select>
 </td>
  <td colspan="2">
    <select name="<?php echo "resourceresponse_$i" ?>" onchange="performAction('changeResourceResp', document.attendeesForm.<?php echo "resourceresponse_$i" ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($id) ?>'));">
    <option value="<?php echo Kronolith::RESPONSE_ACCEPTED ?>"<?php if ($resource['response'] == Kronolith::RESPONSE_ACCEPTED)  echo ' selected="selected"' ?>><?php echo Kronolith::responseToString(Kronolith::RESPONSE_ACCEPTED) ?></option>
    <option value="<?php echo Kronolith::RESPONSE_DECLINED ?>"<?php if ($resource['response'] == Kronolith::RESPONSE_DECLINED)  echo ' selected="selected"' ?>><?php echo Kronolith::responseToString(Kronolith::RESPONSE_DECLINED) ?></option>
    <option value="<?php echo Kronolith::RESPONSE_TENTATIVE ?>"<?php if ($resource['response'] == Kronolith::RESPONSE_TENTATIVE)  echo ' selected="selected"' ?>><?php echo Kronolith::responseToString(Kronolith::RESPONSE_TENTATIVE) ?></option>
    <option value="<?php echo Kronolith::RESPONSE_NONE ?>"<?php if ($resource['response'] == Kronolith::RESPONSE_NONE)  echo ' selected="selected"' ?>><?php echo Kronolith::responsetoString(Kronolith::RESPONSE_NONE) ?></option>
   </select>
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
  </td>
<?php if ($registry->hasMethod('contacts/search')): ?>
  <td align="center"><?php echo Horde::url('#')->link(array('class' => 'widget', 'onclick' => 'window.open(\'' . Horde::url('contacts.php') . '\', \'contacts\', \'toolbar=no,location=no,status=no,scrollbars=yes,resizable=yes,width=550,height=270,left=100,top=100\'); return false;')) . Horde::img('addressbook_browse.png') . '<br />' . _("Address Book") . '</a>' ?></td>
<?php endif; ?>
 </tr>
 <tr>
  <td class="rightAlign"><strong><?php echo _("Add resource")?></strong></td>
  <td width="100%">
    <select id="resourceselect" name="resourceselect">
     <option value="0"><?php echo _("Select resource")?></option>
    <?php foreach ($allResources as $id => $resource):?>
        <?php printf('<option value="%s">%s</option>', $resource->getId(), $resource->get('name')) ?>
    <?php endforeach;?>
    </select>
  </td>
 </tr>
</table>

<br />
<div>
 <input type="submit" class="button" name="addNew" value="<?php echo htmlspecialchars(_("Update")) ?>" />
 <input type="submit" class="button" name="addNewClose" value="<?php echo htmlspecialchars(_("Save and Finish")) ?>" />
 <?php if (!empty($GLOBALS['session']['kronolith:attendees'])): ?><input type="submit" class="button" name="clearAll" value="<?php echo htmlspecialchars(_("Clear all")) ?>" /><?php endif; ?>
</div>

<br />
<?php echo $tabs->render($view) . $vfb_html ?>
</form>
