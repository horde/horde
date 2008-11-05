<?php
if (empty($friend_list)) {
    echo '<ul class="notices"><li>';
    echo _("There are no users listed as your friend.");
    echo '</li>';
} else {
?>
<h1 class="header"><?php echo $title ?></h1>
<table id="friendlist" class="striped sortable">
<thead>
<tr>
    <th><?php echo _("Username") ?></th>
    <th><?php echo _("Action") ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($friend_list as $user) { ?>
<tr>
    <td><?php echo '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" /> ' . $user ?></td>
    <td>
        <a href="<?php echo Util::addParameter($remove_url, 'user', $user) ?>"><?php echo $remove_img  . ' ' . _("Remove") ?></a>
        <a href="<?php echo Folks::getUrlFor('user', $user) ?>"><?php echo $profile_img  . ' ' . _("View profile") ?></a>
        <?php if ($letter_url): ?>
        <a href="<?php echo Util::addParameter($letter_url, 'user_to', $user) ?>"><?php echo $letter_img  . ' ' . _("Send message") ?></a>
        <?php endif; ?>
    </td>
</tr>
<?php } ?>
</tbody>
</table>

<?php
}

echo '<br />';
echo $form->renderActive();

if (!empty($waitingFrom)) {
    echo '<br /><h1 class="header">' . _("We are waiting this users to approve our friendship") .'</h1>';
    foreach ($waitingFrom as $user) {
        echo ' <a href="' . Folks::getUrlFor('user', $user) . '">'
                . '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" />'
                . ' ' . $user . '</a> ';
    }
}

if (!empty($waitingFor)) {
    echo '<br /><h1 class="header">' . _("Users winting us to approve their friendship") .'</h1>';
    foreach ($waitingFor as $user) {
        echo ' <a href="' . Folks::getUrlFor('user', $user) . '">'
                . '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" />'
                . ' ' . $user . '</a> '
                . ' <a href="' . Util::addParameter(Horde::applicationUrl('edit/approve.php'), 'user', $user) . '" title="' . _("Approve") . '">'
                . '<img src="' . $registry->getImageDir('horde') . '/tick.png" /></a> '
                . ' <a href="' . Util::addParameter(Horde::applicationUrl('edit/reject.php'), 'user', $user) . '" title="' . _("Reject") . '">'
                . '<img src="' . $registry->getImageDir('horde') . '/cross.png" /></a>';
    }
}

if (!empty($possibilities)) {
    echo '<br /><h1 class="header">' . _("Users that has you listed as a friend") .'</h1>';
    foreach ($possibilities as $user) {
        echo ' <a href="' . Folks::getUrlFor('user', $user) . '">'
                . '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" />'
                . ' ' . $user . '</a> ';
    }
}
