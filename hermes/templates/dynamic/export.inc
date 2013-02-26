<!-- Begin Export Dialog -->
<div id="hermesExportDialog" class="hermesDialog" style="display:none;">
  <h2><?php echo _("Export Search Results") ?></h2>
  <form id="hermesExportForm" method="POST" action="<?php echo $registry->downloadUrl('time.csv', array('actionID' => 'export'))->setRaw(true) ?>">
    <input type="hidden" name="s" id="hermesExportFormS">
    <div>
      <label for="hermesExportFormat"><?php echo _("Select the export format")?>
        <select name="f" id="hermesExportFormat">
          <option value="<?php echo Horde_Data::EXPORT_CSV ?>"><?php echo _("Comma-Separated Variable (.csv)") ?></option>
          <option value="xls"><?php echo _("Microsoft Excel (.xls)") ?></option>
          <option value="iif"><?php echo _("QuickBooks (.iif)") ?></option>
          <option value="<?php echo Horde_Data::EXPORT_TSV ?>"><?php echo _("Tab-Separated Variable (.tsv, .txt)") ?></option>
        </select>
      </label>
    </div>
    <?php $perms = $GLOBALS['injector']->getInstance('Horde_Perms'); ?>
    <?php if ($perms->hasPermission('hermes:review', $GLOBALS['registry']->getAuth(), Horde_Perms::EDIT)): ?>
      <div>
        <label for="hermesExportMark"><?php echo _("Mark the time as exported?") ?>
          <select name="m" id="hermesExportMark">
            <option value="yes"><?php echo _("Yes") ?></option>
            <option value="no"><?php echo _("No") ?></option>
          </select>
      </div>
    <?php endif; ?>
    <p>
      <input id="hermesDoExport" class="horde-default" type="submit" value="<?php echo _("Export")?>" />
      <input id="hermesExportCancel" class="horde-cancel" type="button" value="<?php echo _("Close")?>" />
    </p>
  </form>
</div>
<!-- End Export Dialog -->