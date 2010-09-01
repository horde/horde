<form method="post" name="export" action="<?php echo Horde::downloadUrl(_("postings.csv"), null, Horde::url('data.php')) ?>">
<?php echo Horde_Util::formInput() ?>
<input type="hidden" name="actionID" value="export" />

<h1 class="header">
 <?php echo _("Export Postings") ?>
</h1>

<div class="item">
 <?php echo Horde::label('exportID', _("Select the export format:")) ?><br />
 <select name="exportID" id="exportID">
  <option value="<?php echo EXPORT_CSV ?>"><?php echo _("Comma separated values") ?></option>
  <option value="<?php echo EXPORT_TSV ?>"><?php echo _("Tab separated values") ?></option>
 </select><br />

 <input type="submit" value="<?php echo _("Export") ?>" class="button" />
</div>
</form>
