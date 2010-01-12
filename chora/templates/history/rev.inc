<td id="rev<?php echo $rev ?>" class="historybg<?php echo $bg ?>">
 <a href="<?php echo Chora::url('browsefile', $where, array('r' => $rev), 'rev' . $rev) ?>">
 <?php echo $rev ?></a> <?php printf(_("by %s"), $author) ?>
 <br />
 <em><?php echo $date ?></em>
<?php if (!empty($lines)): ?>
 <br /><?php printf(_("Changed: %s"), $lines) ?>
<?php endif ?>
</td>
