<td style="background:<?php echo $bg ?>; text-align:center">
<?php echo _("Branching to") ?>:<br />
<a href="<?php echo Chora::url('', $where, array('onb' => $rev)); ?>">
 <?php echo $symname ?></a>
<br />
<em>(<?php printf(_("revision %s"), $rev) ?>)</em>
</td>
