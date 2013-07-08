<h1 class="header">
 <?php echo htmlspecialchars($title) ?>
 <?php echo Chora::whereMenu($where, isset($onb) ? $onb : null) ?>
<?php if (!empty($onb)): ?>
 <em><?php echo _("Branch") ?>: <?php echo $onb ?></em>
<?php endif; ?>
</h1>
<?php if (!empty($extraLink)): ?>
  <td class="rightAlign"><?php echo $extraLink ?></td>
<?php endif; ?>
