<div class="options">
 <div>
  <form method="get" action="diff.php">
   <?php echo Chora::formInputs() ?>
   <input type="hidden" name="f" value="<?php echo htmlspecialchars(Horde_Util::getFormData('f')) ?>" />
   <input type="hidden" name="r1" value="<?php echo htmlspecialchars(Horde_Util::getFormData('r1')) ?>" />
   <input type="hidden" name="r2" value="<?php echo htmlspecialchars(Horde_Util::getFormData('r2')) ?>" />
   <?php echo _("Download diff as: ") ?>
   <select name="t" onchange="this.form.submit()">
    <option value="unified"><?php echo _("Unified") ?></option>
   </select>
   <input class="button" type="submit" value="<?php echo _("Get Diff") ?>" /><br />
  </form>
 </div>
</div>

<?php if (!empty($log_messages)): ?>
<h3 class="revision_log"><?php echo _("Log Message") ?></h3>
<div class="revision_log">
<?php foreach ($log_messages as $val): ?>
 <div class="difflog">
  <ul class="revision striped">
   <?php if (!empty($val['author'])): ?><li><?php echo _("Author:") ?> <?php echo $val['author'] ?></li><?php endif; ?>
   <?php if (!empty($val['date'])): ?><li><?php echo _("Date:") ?> <?php echo $val['date'] ?></li><?php endif; ?>
<?php if (!empty($val['branchinfo'])): ?>
   <li><?php echo _("Branch:") ?>
<?php foreach ($val['branchinfo'] as $branchname): ?>
    <strong><a href="<?php echo Chora::url('browsefile', $where, array('onb' => $branchname)) ?>"><?php echo $branchname ?></a></strong>
<?php endforeach; ?>
   </li>
<?php endif; ?>
   <?php if (!empty($val['tags'])): ?><li class="tags"><?php echo _("Tags:") ?> <?php echo implode(', ', $val['tags']) ?></li><?php endif; ?>
   <?php if (isset($val['added'])): ?><li><?php echo _("Changes:") ?> <span class="diffadd">+<?php echo htmlspecialchars($val['added']) ?></span>, <span class="diffdel">-<?php echo htmlspecialchars($val['deleted']) ?></span> <?php echo _("lines") ?></li><?php endif; ?>
  </ul>
  <a href="<?php echo Chora::url('co', $where, array('r' => $val['rev'])) ?>" title="<?php echo htmlspecialchars($val['rev']) ?>"><?php echo htmlspecialchars($VC->abbrev($val['rev'])) ?></a>: <?php echo $val['msg'] ?>
  <div class="diffclear"></div>
 </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
