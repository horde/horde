<form method="post" name="eventform" action="search.php">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="actionID" value="search_calendar" />
<input type="hidden" name="search_mode" value="advanced" />

<br /><br />
<h1 class="header">
 <span class="smallheader rightFloat">
  <?php echo Horde::widget(Horde::url('search.php')->add('search_mode', 'basic'), _("Basic Search"), 'smallheader', '', '', _("_Basic Search")) ?>
 </span>
 <?php echo _("Advanced Search") ?>
</h1>

<table cellspacing="0" class="striped">

<!-- general header -->
<tr><td align="left" colspan="5" class="control">
 <strong><?php echo _("General") ?></strong>
</td></tr>

<!-- title -->
<tr>
 <td class="rightAlign"><strong><?php echo Horde::label('title', _("Tit_le")) ?></strong></td>
 <td colspan="4">
  <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($event->title) ?>" size="40" maxlength="255" />
 </td>
</tr>

<!-- calendar -->
<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('calendar', _("C_alendar")) ?></strong>
 </td>
 <td colspan="4">
  <select id="calendar" name="calendar">
   <option value="|__any"<?php if ($search_calendar[1] == '__any') echo ' selected="selected"' ?>><?php echo _("Any") ?></option>
   <?php foreach ($calendars as $type => $list): ?>
   <?php if ($optgroup): ?>
   <optgroup label="<?php echo htmlspecialchars($type) ?>">
   <?php endif; ?>
   <?php foreach ($list as $id => $name): ?>
   <option value="<?php echo htmlspecialchars($id) ?>"<?php if ($search_calendar[0] . '|' . $search_calendar[1] == $id) echo ' selected="selected"' ?>><?php echo ($optgroup ? '' : htmlspecialchars($type) . ' ') . htmlspecialchars($name) ?></option>
   <?php endforeach; ?>
   <?php if ($optgroup): ?>
   </optgroup>
   <?php endif; ?>
   <?php endforeach; ?>
  </select>
 </td>
</tr>

<!-- location -->
<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('location', _("Lo_cation")) ?></strong>
 </td>
 <td colspan="4">
  <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($event->location) ?>" size="40" maxlength="255" />
 </td>
</tr>

<!-- status -->
<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('status', _("Stat_us")) ?></strong>
 </td>
 <td colspan="4">
  <?php echo Kronolith::buildStatusWidget('status', $event->status, true) ?>
 </td>
</tr>

<!-- description -->
<tr>
 <td class="rightAlign">
  <strong><?php echo Horde::label('description', _("Descri_ption")) ?></strong>
 </td>
 <td colspan="4">
  <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($event->description) ?>" size="40" maxlength="255" />
 </td>
</tr>

<?php require KRONOLITH_TEMPLATES . '/edit/edit_timespan.inc'; ?>

</table>

<br />
<input type="submit" class="button" value="<?php echo _("Search") ?>" />
</form>
