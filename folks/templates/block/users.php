<table class="striped" style="width: 100%">
<?php foreach ($list as $user): ?>
<tr valign="top">
<td>
<a href="<?php echo Folks::getUrlFor('user', $user) ?>" alt="<?php echo $user ?>" title="<?php echo $user ?>">
<img src="<?php echo Folks::getImageUrl($user) ?>" class="userMiniIcon" style="float: left" />
<strong><?php echo $user ?></strong></a><br />
<span class="small">
<?php foreach ($actions as $action): ?>
<a href="<?php echo $action['url']->add($action['id'], $user) ?>"><?php echo $action['name'] ?> </a>
<?php endforeach; ?>
<br />
<?php
    if ($GLOBALS['folks_driver']->isOnline($user)) {
        echo '<span class="online">' . _("Online") . '</span>';
    } else {
        echo '<span class="offline">' . _("Offline") . '</span>';
    }
?>

</span>
</td>
</tr>
<?php endforeach; ?>
</table>
