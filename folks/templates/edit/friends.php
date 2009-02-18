<?php require dirname(__FILE__) . '/tabs.php'; ?>

<h1 class="header"><?php echo $title ?></h1>

<?php

if (empty($list)) {
    echo '<ul class="notices"><li>' . _("No user listed") . '</li></ul>';
    return true;
}
?>

<table id="friendlist" class="striped sortable" style="width: 100%">
<thead>
<tr>
    <th><?php echo _("Username") ?></th>
    <th><?php echo _("Status") ?></th>
    <th><?php echo _("Action") ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($list as $user) { ?>
<tr>
    <td style="text-align: center">
        <?php echo '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" /><br />' . $user ?>
    </td>
    <td>
    <?php
        if ($folks_driver->isOnline($user)) {
            echo '<span class="online">' . _("Online") . '</span>';
        } else {
            echo '<span class="offline">' . _("Offline") . '</span>';
        }
    ?>
    </td>
    <td>
        <a href="<?php echo Folks::getUrlFor('user', $user) ?>"><?php echo $profile_img  . ' ' . _("View profile") ?></a>
    </td>
    <?php if (!empty($remove_url)): ?>
        <td>
        <a href="<?php echo Util::addParameter($remove_url, 'user', $user) ?>"><?php echo $remove_img  . ' ' . _("Remove") ?></a>
        </td>
    <?php endif; ?>
    <?php if (!empty($letter_url)): ?>
        <td>
            <a href="<?php echo Util::addParameter($letter_url, 'user_to', $user) ?>"><?php echo $letter_img  . ' ' . _("Send message") ?></a>
        </td>
    <?php endif; ?>
</tr>
<?php } ?>
</tbody>
</table>
