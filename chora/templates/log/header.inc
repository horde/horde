<div class="browsefileoptions">
 <span>
  <?php echo _("Search Revisions:") ?>
  <input type="text" for="revlog_body" size="20" />
 </span>

 <span>
<?php if (!$browser->hasFeature('javascript')): ?>
  <form method="get" action="diff.php">
   <?php echo Chora::formInputs() ?>
   <input type="hidden" name="f" value="<?php echo htmlspecialchars(Horde_Util::getFormData('f')) ?>" />
   <?php printf(_("Show diffs between %s and %s"),
                '<input type="text" size="6" name="r1" value="' . $diffValueLeft . '" />',
                '<input type="text" size="6" name="r2" value="' . $diffValueRight . '" />') ?>
   <input class="button" type="submit" value="<?php echo _("Diff") ?>" /><br />
  </form>
<?php endif; ?>
   <em><?php printf(_("%1\$s shows diffs to the previous revision. If you select a revision by clicking its row, %1\$s will show the differences to the selected row."), Horde::img('diff.png', _("Show Differences"))) ?></em>
 </span>

<?php if (!empty($selAllBranches)): ?>
 <span>
  <form method="get" action="<?php echo Chora::url('browsefile', Horde_Util::getFormData('f')) ?>">
   <?php echo _("Show Branch:") ?>
   <select name="onb" onchange="this.form.submit()">
    <option value="0"<?php if (!isset($onb) || !$onb) echo ' selected="selected"' ?>><?php echo _("All Branches") ?></option>
    <?php echo $selAllBranches ?>
   </select>
   <noscript><input class="button" type="submit" value="<?php echo _("View") ?>" /></noscript>
  </form>
 </span>
<?php endif; ?>
</div>

<table class="revlog striped sortable" id="revlog" cellspacing="0">
<thead>
 <tr class="item leftAlign">
  <th class="nosort">&nbsp;</th>
  <th><?php echo _("Revision") ?></th>
  <th class="sortup"><?php echo _("Date") ?></th>
  <th><?php echo _("Author") ?></th>
  <th class="nosort"><?php echo _("Log Message") ?></th>
 </tr>
</thead>
<tbody id="revlog_body">
