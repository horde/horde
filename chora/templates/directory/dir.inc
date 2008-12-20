 <tr>
  <td>
<?php if (!Chora::isRestricted("$where/$currentDir")): ?>
   <?php echo Horde::img('folder.png', _("Directory"), 'width="16" height="16"') ?> <a href="<?php echo $url ?>"><?php echo $currDir ?>/</a>
<?php else: ?>
   <?php echo Horde::img('folder.png', _("Directory"), 'width="16" height="16"') ?> <?php echo $currDir ?>/
<?php endif; ?>
  </td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
  <td>&nbsp;</td>
 </tr>
