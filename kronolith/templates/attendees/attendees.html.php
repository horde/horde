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
    document.attendeesForm.enddate.value = document.attendeesForm.enddate.value - document.attendeesForm.startdate.value + date;
    document.attendeesForm.startdate.value = date;
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
    document.attendeesForm.enddate.value = document.attendeesForm.enddate.value - document.attendeesForm.startdate.value + date;
    document.attendeesForm.startdate.value = date;
    document.attendeesForm.submit();
    return false;
}
</script>

<form method="post" action="attendees.php" name="attendeesForm">
<?php echo $this->formInput ?>
<input type="hidden" name="actionID" value="add" />
<input type="hidden" name="actionValue" value="" />
<input type="hidden" name="view" value="<?php echo $this->h($this->view) ?>" />
<input type="hidden" name="startdate" value="<?php echo $this->date ?>" />
<input type="hidden" name="enddate" value="<?php echo $this->end ?>" />

<h1 class="header"><?php echo $this->h($this->title) ?></h1>

<table width="100%" cellspacing="0" class="linedRow">

<!-- attendee list header -->
<tr class="nowrap leftAlign">
 <th width="2%">&nbsp;</th>
 <th width="48%"><?php echo $this->h(_("Attendee")) ?></th>
 <th width="25%"><?php echo $this->h(_("Attendance")) ?></th>
 <th width="25%"><?php echo $this->h(_("Response")) ?></th>
</tr>

<!-- attendees -->
<?php if (!count($this->attendees)): ?>
 <tr><td colspan="4"><em><?php echo _("No attendees") ?></em></td></tr>
<?php else: ?>
<?php echo $this->renderPartial('attendee', array('collection' => $this->attendees)) ?>
<?php endif ?>

<?php if ($this->resourcesEnabled): ?>
<!-- resource list header -->
<tr class="item nowrap leftAlign">
 <th width="2%">&nbsp;</th>
 <th width="48%"><?php echo $this->h(_("Resource")) ?></th>
 <th width="25%"><?php echo _("Attendance") ?></th>
 <th width="25%"><?php echo _("Response") ?></th>
</tr>

<!--  resources -->
<?php if (!$this->resources): ?>
 <tr><td colspan="4"><em><?php echo _("No resources") ?></em></td></tr>
<?php else: ?>
<?php echo $this->renderPartial('resource', array('collection' => $this->resources)) ?>
<?php endif ?>
<?php endif ?>
</table>

<br />

<table width="100%" cellspacing="2" class="nowrap control">
 <!-- add users -->
 <tr>
  <td class="rightAlign">&nbsp;<strong>
   <?php echo _("Add user") ?>
  </strong></td>
  <td colspan="2" width="100%">
<?php if ($this->userList): ?>
   <select id="newUser" name="newUser">
    <option value=""><?php echo _("Select user")?></option>
<?php foreach ($this->userList as $user => $name): ?>
    <option value="<?php echo $this->h($user) ?>"><?php echo $this->h($name) ?></option>
<?php endforeach ?>
    </select>
<?php else: ?>
   <input type="text" id="newUser" name="newUser" size="40" />
<?php endif ?>
  </td>
 </tr>
 <!-- add externals -->
 <tr>
  <td class="rightAlign">&nbsp;<strong>
<?php if ($this->editAttendee): ?>
   <?php echo _("Edit external attendee") ?>
<?php else: ?>
   <?php echo _("Add external attendees") ?>
<?php endif ?>
  </strong></td>
  <td>
   <input type="text" id="newAttendees" name="newAttendees" autocomplete="off" size="40" <?php if ($this->editAttendee) echo 'value="' . $this->h($this->editAttendee) . '" '; ?>/>
   <span id="newAttendees_loading_img" style="display:none;"><?php echo Horde::img('loading.gif', _("Loading...")) ?></span>
  </td>
  <td align="center"><?php echo $this->adressbookLink ?></td>
 </tr>
<?php if ($this->resourcesEnabled): ?>
 <!-- add resources -->
 <tr>
  <td class="rightAlign"><strong><?php echo _("Add resource")?></strong></td>
  <td colspan="2">
    <select id="resourceselect" name="resourceselect">
     <option value="0"><?php echo _("Select resource")?></option>
<?php foreach ($this->allResources as $id => $resource): ?>
     <option value="<?php echo $resource->getId() ?>"><?php echo $this->h($resource->get('name')) ?></option>
<?php endforeach ?>
    </select>
  </td>
 </tr>
<?php endif ?>
</table>

<br />
<div class="horde-form-buttons">
 <input type="submit" class="horde-default" name="addNewClose" value="<?php echo $this->h(_("Save and Finish")) ?>" />
 <input type="submit" name="addNew" value="<?php echo $this->h(_("Update")) ?>" />
<?php if (count($this->attendees)): ?>
 <input type="submit" class="horde-delete" name="clearAll" value="<?php echo $this->h(_("Clear all")) ?>" />
<?php endif ?>
</div>

<br />
<?php echo $this->tabs ?>
<?php echo $this->freeBusy ?>
</form>
