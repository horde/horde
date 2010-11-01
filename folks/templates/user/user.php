
<h1><?php echo $title ?></h1>

<?php
if ($user == $GLOBALS['registry']->getAuth()) {
    echo $form->renderActive(null, null, '', 'post') . '<br />';
}
?>

<table style="width: 100%">
<tbody>
<tr valign="top">

<td style="width: 50%; text-align: center">

<?php

if ($profile['user_video']) {

    echo $registry->call('video/getEmbedCode', array($profile['user_video']));

} elseif ($profile['user_picture']) {

    echo '<img src="' . Folks::getImageUrl($user, 'big') . '" alt="' . $user . '" />';

} else {

    echo '<img src="' . Horde_Themes::img('guest.png') . '" alt="'
                . $user . '" title="' . _("Has no picture"). '" style="border: 2px solid #eeeeee; padding: 100px"/>';

}

echo '<br />';
include FOLKS_TEMPLATES . '/user/actions.php';
?>

</td>

<td>

<table class="striped" style="width: 100%">
<tbody>
<tr valign="top">
    <td><strong><?php echo _("Status") ?></strong></td>
    <td>
    <?php
        if ($folks_driver->isOnline($user)) {
            echo '<span class="online">' . _("Online") . '</span>';
        } else {
            echo '<span class="offline">' . _("Offline") . '</span>';
            if ($profile['last_online_on'] &&
                ($profile['last_online'] == 'all' ||
                $GLOBALS['registry']->isAuthenticated() && (
                    $profile['last_online'] == 'authenticated' ||
                    $profile['last_online'] == 'friends' && $friends_driver->isFriend($GLOBALS['registry']->getAuth())))
                ) {
                echo ' ' . _("Last time online") . ': ' . Folks::format_datetime($profile['last_online_on']);
            }
        }
    ?>
    </td>
</tr>

<?php
if (!empty($profile['activity_log'])) {
$activity = current($profile['activity_log']);
foreach ($profile['activity_log'] as $item) {
    if ($item['activity_scope'] == 'folks:custom') {
        $activity = $item;
        break;
    }
}
?>
<tr>
    <td><strong><?php echo _("Last activity") ?></strong></td>
    <td><?php echo $activity['activity_message'] ?></td>
</tr>
<?php } ?>

<tr>
    <td><strong><?php echo _("Age") ?></strong></td>
    <td>
        <?php
            $age = Folks::calcAge($profile['user_birthday']);
            echo $age['age'];
            if ($age['sign']) {
                echo ' (' . $age['sign'] . ')';
            }
        ?>
    </td>
</tr>
<tr>
    <td><strong><?php echo _("Gender") ?></strong></td>
    <td><?php if ($profile['user_gender']) { echo $profile['user_gender'] == 1 ? _("Male") : _("Female"); } ?></td>
</tr>
<tr>
    <td><strong><?php echo _("City") ?></strong></td>
    <td><?php echo $profile['user_city'] ?></td>
</tr>
<?php if ($conf['services']['countcron']): ?>
<tr>
    <td><strong><?php echo _("Activity") ?></strong></td>
    <td><?php echo $profile['activity'] ?>%</td>
</tr>
<tr>
    <td><strong><?php echo _("Popularity") ?></strong></td>
    <td><?php echo $profile['popularity'] ?>%</td>
</tr>
<?php endif; ?>
<tr>
    <td><strong><?php echo _("Homepage") ?></strong></td>
    <td><?php echo $profile['user_url'] ? '<a href="' . $profile['user_url'] . '" target="_blank">' . _("Visit my homepage") . '</a>' : _("I don't have it") ?></td>
</tr>

<?php
$friends = $friends_driver->getFriends();
if (!empty($friends)):
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo Horde::url('edit/friends/index.php') ?>" title="<?php echo _("Edit my firends") ?>"><img src="<?php Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo Horde_Util::addParameter(Horde::url('edit/friends/index.php'), 'user', $user) ?>" title="<?php echo sprintf(_("Add %s as a friend?"), $user) ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<?php echo _("Friends") ?> (<?php echo count($friends) ?>)
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($friends as $item) {
                $img = Folks::getImageUrl($item);
                echo '<a href="' .  Folks::getUrlFor('user', $item) . '" title="' . $item . '">'
                    . '<img src="' . $img . '" class="userMiniIcon" /></a>';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_classifieds']):
$path = $registry->get('webroot', 'classifieds');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/ads/index.php" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/list.php?user_uid=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'classified') ?> (<?php echo $profile['count_classifieds'] ?>)</a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_classifieds_list'] as $item_id => $item) {
                echo '&nbsp;&#8226; <a href="' . $path . '/classified/ad.php?ad_id=' . $item_id . '">'
                        . $item['ad_title'] . '</a><br />';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_news']):
$path = $registry->get('webroot', 'news');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/add.php" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/search.php?user=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'news') ?> (<?php echo $profile['count_news'] ?>)</a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_news_list'] as $item) {
                echo '&nbsp;&#8226; <a href="' . $path . '/news.php?id=' . $item['id'] . '" title="'
                        . htmlspecialchars($item['abbreviation']) . '...">' . $item['title'] . '</a><br />';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_videos']):
$path = $registry->get('webroot', 'oscar');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/videos/index.php" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/search.php?author=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'oscar') ?> (<?php echo $profile['count_videos'] ?>)</a>
</a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_videos_list'] as $item_id => $item) {
                echo '<a href="' . $path . '/video.php?id=' . $item_id . '" title="'
                        . htmlspecialchars($item['video_description']) . '...">'
                        . '<img src="' . $registry->get('webroot', 'horde') . '/vfs/.horde/oscar/' .  substr($item_id, -2) . '/' . $item_id . '/00000001.jpg" style="width: 50px; height: 38px" /></a> ';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_wishes']):
$path = $registry->get('webroot', 'genie');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/wishlist.php?wishlist=<?php echo $GLOBALS['registry']->getAuth() ?>" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/wishlist.php?wishlist=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'genie') ?> (<?php echo $profile['count_wishes'] ?>)</a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_wishes_list'] as $item_id => $item) {
                echo '&nbsp;&#8226; <a href="' . $path . '/view.php?wishlist=' . $user .'&item=' . $item_id . '" title="'
                        . htmlspecialchars($item['desc']) . '...">'
                        . htmlspecialchars($item['name']) . '</a><br />';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_galleries']):
$path = $registry->get('webroot', 'ansel');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/view.php?groupby=owner&view=List&owner=<?php echo $GLOBALS['registry']->getAuth() ?>" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/view.php?groupby=owner&view=List&owner=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'ansel') ?> (<?php echo $profile['count_galleries'] ?>)</a> |
<a href="<?php echo $path ?>/faces/search/owner.php?owner=<?php echo $user ?>" title="<?php echo _("Faces in user galleries") ?>"><?php echo _("Faces") ?></a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_galleries_list'] as $item_id => $item) {
                echo '<a href="' . $path . '/view.php?gallery=' . $item['share_id'] . '" title="'
                        . htmlspecialchars($item['attribute_name']) . '">'
                        . '<img src="' . $path . '/img/mini.php?style=ansel_default&image='
                        . $item['attribute_default'] . '" /></a> ';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_blogs']):
$path = $registry->get('webroot', 'thomas');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/edit.php" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/user.php?user=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'thomas') ?> (<?php echo $profile['count_blogs'] ?>)</a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_blogs_list'] as $item_id => $item) {
                echo '&nbsp;&#8226; <a href="' . $item['link'] . '" title="'
                        . htmlspecialchars($item['description']) . '">'
                        . $item['title'] . '</a><br />';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<?php
if ($profile['count_attendances']):
$path = $registry->get('webroot', 'schedul');
?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="<?php echo $path ?>/add.php" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="<?php echo $path ?>" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<a href="<?php echo $path ?>/user.php?user=<?php echo $user ?>" title="<?php echo _("Others user content") ?>" ><?php echo $registry->get('name', 'schedul') ?> (<?php echo $profile['count_attendances'] ?>)</a>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php
            foreach ($profile['count_attendances_list'] as $item_id => $item) {
                echo Folks::format_datetime($item['ondate']) . ' ' . $item['city'] . ', ' . $item['place'] . ': <a href="' . $path . '/event.php?id=' . $item['id'] . '">'
                        . ' ' . $item['short'] . '</a><br />';
            }
        ?>
    </td>
</tr>
<?php endif; ?>

<tr>
<td class="header" colspan="2">
<?php echo _("Description") ?>
</td>
</tr>
<tr>
    <td colspan="2">
        <?php echo $profile['user_description'] ?>
    </td>
</tr>

<?php if (!empty($profile['activity_log'])): ?>
<tr>
<td class="header" colspan="2">
<span style="float: right">
<a href="/uporabniki/edit/activity.php" title="<?php echo _("Add your content") ?>"><img src="<?php echo Horde_Themes::img('plus.png') ?>" /></a>
<a href="/uporabniki/friends/index.php" title="<?php echo _("Preview") ?>"><img src="<?php echo Horde_Themes::img('nav/right.png') ?>" /></a>
</span>
<?php echo _("Activity") ?>
</td>
</tr>
<?php
foreach ($profile['activity_log'] as $item_id => $item) {
    echo '<tr><td colspan="2">' . Folks::format_datetime($item['activity_date']) . ' - ' . $item['activity_message'] . '</td></tr>';
}
?>
<?php endif; ?>

</tbody>
</table>

</td>

</tr>

</tbody>
</table>

<?php

include FOLKS_TEMPLATES . '/user/actions.php';

/**
 * Shoud we allow comments?
 */
switch ($profile['user_comments']) {

case 'never':
    $allow_comments = false;
    $comments_reason = sprintf(_("User %s does not wish to be commented."), $user);
    break;

case 'authenticated':
    $allow_comments = $GLOBALS['registry']->isAuthenticated();
    if ($allow_comments) {
        if ($friends_driver->isBlacklisted($GLOBALS['registry']->getAuth())) {
            $allow_comments = false;
            $comments_reason = sprintf(_("You are on %s blacklist."), $user);
        }
    } else {
        $comments_reason = _("Only authenticated users can post comments.");
        if ($conf['hooks']['permsdenied']) {
            $comments_reason = Horde::callHook('perms_denied', array('folks'));
        }
    }
    break;

case 'friends':
    $allow_comments = $friends_driver->isFriend($GLOBALS['registry']->getAuth());
    $comments_reason = _("Only authenticated users can post comments.");
    break;

default:
    $allow_comments = true;
    if ($GLOBALS['registry']->isAuthenticated() && $friends_driver->isBlacklisted($GLOBALS['registry']->getAuth())) {
        $allow_comments = false;
        $comments_reason = sprintf(_("You are on %s blacklist."), $user);
    }

    break;
}

$params = array('folks', $user, 'commentCallback', true, null, null,
                array('message_subject' => $user), $conf['comments']['comment_template']);

$comments = $GLOBALS['registry']->call('forums/doComments', $params);

if (!empty($comments['threads'])) {
    echo $comments['threads'];
}

if ($allow_comments) {
    if (!empty($comments['comments'])) {
        echo $comments['comments'];
    }
} else {
    echo $comments_reason;
}
