<form method="post" name="skoli_exportform" id="skoli_exportform" action="<?php echo Horde::downloadUrl(_("class.csv"), null, Horde::url('data.php')) ?>">
<input type="hidden" name="actionID" value="export" />

<h1 class="header">
 <?php echo _("Export Classes") ?>
</h1>

<div class="item" style="padding:1em">
 <?php echo Horde::label('exportID', _("Select the export format:")) ?><br />
 <select name="exportID" id="exportID">
  <option value="<?php echo EXPORT_CSV ?>"<?php echo Horde_Util::getFormData('exportID') == EXPORT_CSV ? ' selected="selected"' : '' ?>><?php echo _("Comma separated values (CSV)") ?></option>
  <option value="<?php echo EXPORT_TSV ?>"<?php echo Horde_Util::getFormData('exportID') == EXPORT_TSV ? ' selected="selected"' : '' ?>><?php echo _("Tab separated values (TSV)") ?></option>
 </select><br />

<?php if (count($classes) > 1): ?>
 <br />
 <?php echo Horde::label('class', _("Select the class to export from:")) ?><br />
 <select name="class" id="class" onchange="if (this.value) { document.skoli_exportform.actionID.value=''; RedBox.loading(); document.skoli_exportform.submit() }">
  <?php echo implode('', $class_options) ?>
 </select><br />
<?php else: ?>
 <input type="hidden" name="class" value="<?php echo key($classes) ?>" />
<?php endif; ?>
 <br />

 <?php echo Horde::label('student', _("Select a student or the whole class to export:")) ?><br />
 <select name="student" id="student">
  <?php echo implode('', $student_options) ?>
 </select><br />
 <br />

 <input type="submit" value="<?php echo _("Export") ?>" class="button" />
</div>
</form>
