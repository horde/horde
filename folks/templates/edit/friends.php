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
        <?php echo '<a href="' . Folks::getUrlFor('user', $user) . '">'
                . '<img src="' . Folks::getImageUrl($user) . '" class="userMiniIcon" /><br />' . $user ?></a>
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
    <?php
        foreach ($actions as $action) {
            echo '<td>';
            echo '<a href="' . $action['url']->add($action['id'], $user) . '">'
                            . $action['img']  . ' ' . $action['name'] . '</a>';
            echo '</td>';
        }
    ?>
</tr>
<?php } ?>
</tbody>
</table>
