<table class="options" cellspacing="0">
 <tr>
  <td>
   <form method="get" action="diff.php">
    <?php echo Chora::formInputs() ?>
    <input type="hidden" name="f" value="<?php echo htmlspecialchars(Util::getFormData('f')) ?>" />
    <input type="hidden" name="r1" value="<?php echo htmlspecialchars(Util::getFormData('r1')) ?>" />
    <input type="hidden" name="r2" value="<?php echo htmlspecialchars(Util::getFormData('r2')) ?>" />
    <?php echo _("Download diff as: ") ?>
    <select name="t" onchange="this.form.submit()">
<?php if (isset($diff_types['unified'])): ?>
     <option value="unified"><?php echo _("Unified") ?></option>
<?php endif; ?>
<?php if (isset($diff_types['context'])): ?>
     <option value="context"><?php echo _("Context") ?></option>
<?php endif; ?>
<?php if (isset($diff_types['column'])): ?>
     <option value="column"><?php echo _("Column") ?></option>
<?php endif; ?>
<?php if (isset($diff_types['ed'])): ?>
     <option value="ed"><?php echo _("Ed Script") ?></option>
<?php endif; ?>
    </select>
    <input class="button" type="submit" value="<?php echo _("Get Diff") ?>" /><br />
   </form>
  </td>
 </tr>
</table>
<table cellspacing="0" class="hrdiff">
<caption>
 <?php $blank = $registry->getImageDir('horde') . '/blank.gif' ?>
 <img class="unmodified" src="<?php echo $blank ?>" alt="<?php echo _("Unmodified") ?>" /> <?php echo _("Unmodified") ?>
 <img class="added" src="<?php echo $blank ?>" alt="<?php echo _("Added") ?>" /> <?php echo _("Added") ?>
 <img class="modified" src="<?php echo $blank ?>" alt="<?php echo _("Modified") ?>" /> <?php echo _("Modified") ?>
 <img class="removed" src="<?php echo $blank ?>" alt="<?php echo _("Removed") ?>" /> <?php echo _("Removed") ?>
</caption>
<?php if (!empty($log_messages)): ?>
<h3 class="revision_log"><?php echo _("Log Message") ?></h3>
<div class="revision_log">
<?php foreach ($log_messages as $val): ?>
 <div class="difflog">
  <ul class="revision striped">
   <?php if (!empty($val['author'])): ?><li><?php echo _("Author:") ?> <?php echo $val['author'] ?></li><?php endif; ?>
   <?php if (!empty($val['date'])): ?><li><?php echo _("Date:") ?> <?php echo $val['date'] ?></li><?php endif; ?>
   <?php if (!empty($val['branchName'])): ?><li><?php echo _("Branch:") ?> <strong><a href="<?php echo Chora::url('', $where, array('onb' => $val['branchRev'])) ?>"><?php echo $val['branchName'] ?></a></strong></li><?php endif; ?>
   <?php if (!empty($val['tags'])): ?><li class="tags"><?php echo _("Tags:") ?> <?php echo implode(', ', $val['tags']) ?></li><?php endif; ?>
  </ul>
  <a href="<?php echo Chora::url('co', $where, array('r' => $val['rev'])) ?>"><?php echo $val['rev'] ?></a>: <?php echo $val['msg'] ?>
  <div class="diffclear"></div>
 </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<thead>
<tr>
 <th><a href="<?php echo Chora::url('co', $where, array('r' => $r1)) ?>"><?php printf(_("Version %s"), $r1) ?></a></th>
 <th><a href="<?php echo Chora::url('co', $where, array('r' => $r2)) ?>"><?php printf(_("Version %s"), $r2) ?></a></th>
</tr>
</thead>
<tbody>
