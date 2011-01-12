<h1 class="header"><?php echo htmlspecialchars($title) ?></h1>
<table cellspacing="0" class="headerbar" id="headerbar">
 <tr>
  <td class="browseLocation">
   <?php echo _("Location:") ?>
   <strong>[ <a href="<?php echo Chora::url('browsedir') ?>"><?php echo $chora_conf['sourceRootName'] ?></a> ]
   <?php echo Chora::whereMenu($where, isset($onb) ? $onb : null) ?></strong>
<?php if (!empty($onb)): ?>
    <em><?php echo _("Tracking Branch") ?>: <strong><?php echo $onb ?></strong></em>
<?php endif; ?>
  </td>
<?php if (!empty($extraLink)): ?>
  <td class="rightAlign"><?php echo $extraLink ?></td>
<?php endif; ?>
 </tr>

<?php if (!$where && @is_file($chora_conf['introText'])): ?>
 <tr>
  <td class="text" colspan="2"><?php @readfile($chora_conf['introText']) ?></td>
 </tr>
<?php endif; ?>

</table>
