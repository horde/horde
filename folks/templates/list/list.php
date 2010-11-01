<div class="tabset">
<ul>
<li <?php if (basename($_SERVER['PHP_SELF']) == 'online.php') echo 'class="activeTab"' ?> title="<?php echo _("Users currently online") ?>"><a href="<?php echo Folks::getUrlFor('list', 'online') ?>"><?php echo _("Online") ?></a></li>
<li <?php if (basename($_SERVER['PHP_SELF']) == 'new.php') echo 'class="activeTab"' ?> title="<?php echo _("New registered user") ?>"><a href="<?php echo Folks::getUrlFor('list', 'new') ?>"><?php echo _("New") ?></a></li>
<?php if ($conf['services']['countcron']): ?>
<li <?php if (basename($_SERVER['PHP_SELF']) == 'popularity.php') echo 'class="activeTab"' ?> title="<?php echo _("Most popular users") ?>"><a href="<?php echo Folks::getUrlFor('list', 'popularity') ?>"><?php echo _("Popularity") ?></a></li>
<li <?php if (basename($_SERVER['PHP_SELF']) == 'activity.php') echo 'class="activeTab"' ?> title="<?php echo _("Most active users") ?>"><a href="<?php echo Folks::getUrlFor('list', 'activity') ?>"><?php echo _("Activity") ?></a></li>
<?php endif; ?>
<li <?php if (basename($_SERVER['PHP_SELF']) == 'birthday.php') echo 'class="activeTab"' ?> title="<?php echo _("Users celebrating birthday today") ?>"><a href="<?php echo Folks::getUrlFor('list', 'birthday') ?>"><?php echo _("Birthday") ?></a></li>
<li <?php if (basename($_SERVER['PHP_SELF']) == 'list.php') echo 'class="activeTab"' ?> title="<?php echo _("All users") ?>"><a href="<?php echo Folks::getUrlFor('list', 'list') ?>"><?php echo _("List") ?></a></li>
</ul>
</div>
<br class="clear" />
<h1 class="header"><?php echo $title ?></h1>
<?php if (empty($users)): ?>
<?php echo _("No users found under selected criteria"); ?>
<?php else:

$sortImg = ($criteria['sort_dir'] == 'DESC') ? 'za.png' : 'az.png';
$sortText = _("Sort Direction");

$headers = array();
$headers['user_uid'] = array('stext' => _("Sort by Username"),
                                'text' => _("Username"));
$headers['user_gender'] = array('stext' => _("Sort by Gender"),
                                'text' => _("Gender"));
$headers['user_birthday'] = array('stext' => _("Sort by Age"),
                                    'text' => _("Age"));
$headers['user_city'] = array('stext' => _("Sort by City"),
                                'text' => _("City"));
$headers['user_homepage'] = array('stext' => _("Sort by Homepage"),
                                    'text' => _("Homepage"));
$headers['user_description'] = array('stext' => _("Sort by Description"),
                                    'text' => _("Description"));
$headers['count_galleries'] = array('stext' => _("Sort by Albums"),
                                    'text' => _("Albums"));
$headers['count_video'] = array('stext' => _("Sort by Video"),
                                    'text' => _("Video"));

$sortImg = ($criteria['sort_dir'] == 'DESC') ? 'za.png' : 'az.png';
$sortText = _("Sort Direction");

?>
<table class="striped" style="width: 100%">
<thead>
<tr>
<?php
foreach ($headers as $key => $val) {
    echo '<th class="widget leftAlign nowrap">' . "\n";
    if ($criteria['sort_by'] == $key) {
        echo Horde::link(Horde_Util::addParameter($list_url, 'sort_dir', ($criteria['sort_dir'] == 'DESC') ? 'ASC' : 'DESC'), $val['text'], null, null, null, $val['text']);
        echo Horde::img($sortImg, $sortText) . '</a>&nbsp;';
    }
    echo Horde::widget(Horde_Util::addParameter(($criteria['sort_by'] == $key) ? $list_url : $list_url, 'sort_by', $key), $val['text'], 'widget');
    echo '</th>';
}
?>
</tr>
</thead>
<?php
foreach ($users as $user):
?>
<tr>
    <td style="text-align: center">
        <a href="<?php echo Folks::getUrlFor('user', $user['user_uid']) ?>">
        <img src="<?php echo Folks::getImageUrl($user['user_uid']) ?>" class="userMiniIcon" />
        <?php echo $user['user_uid'] ?></a>
    </td>
    <td><?php if ($user['user_gender']) { echo $user['user_gender'] == 1 ? _("Male") : _("Female"); } ?></td>
    <td><?php
        $age = Folks::calcAge($user['user_birthday']);
        if (!empty($age['age'])) {
            echo $age['age'] . ' (' . $age['sign'] . ')' ;
        }
        ?></td>
    <td><?php echo $user['user_city'] ?></td>
    <td><?php echo $user['user_url'] ? _("Yes") : _("No") ?></td>
    <td><?php echo $user['user_description'] ? _("Yes") : _("No") ?></td>
    <td><?php echo $user['count_galleries'] ? _("Yes") : _("No") ?></td>
    <td><?php echo $user['count_videos'] ? _("Yes") : _("No") ?></td>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php echo $pager->render() ?>
<?php endif; ?>
