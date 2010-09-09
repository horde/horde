<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<h1 class="header"><?php echo _('Moderators'); ?></h1>
<?php if (!empty($this->forums)): ?>
<table class="striped">
<tr>
    <td class="control"><?php echo _('Forum'); ?></td>
    <td class="control"><?php echo _('Forum name'); ?></td>
    <td class="control"><?php echo _('Moderators'); ?></td>
</tr>
<?php foreach ($this->forums as $k1 => $v1): ?>
<tr>
    <td><?php if (isset($v1)) { echo is_array($v1) ? $k1 : $v1; } elseif (isset($this->forums)) { echo $this->forums; } ?></td>
    <td><?php echo $v1['forum_name']; ?></td>
    <td>
        <?php if (!empty($v1['moderators'])): ?>
            <?php foreach ($v1['moderators'] as $k2 => $v2): ?>
                <?php if (isset($v2)) { echo is_array($v2) ? $k2 : $v2; } elseif (isset($v1['moderators'])) { echo $v1['moderators']; } ?><br />
            <?php endforeach; ?>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
 <p><em><?php echo _('No moderators have been created.'); ?></em></p>

<?php endif; ?>

<br class="spacer">

<?php echo $this->formbox; ?>
