<td class="historybg<?php echo $bg ?>">
<?php echo _("Branching to") ?>:<br />
<a href="<?php echo Chora::url('browsefile', $where, array('onb' => $rev)); ?>">
 <?php echo $symname ?></a>
<br />
<em>(<?php printf(_("revision %s"), $rev) ?>)</em>
</td>
