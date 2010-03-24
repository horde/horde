<div class="header">
    Account: <?php echo $_SESSION['shout']['accounts'][$curaccount]; ?>
</div>
<?php
$editurl = Horde::applicationUrl('dialplan.php');
$editurl = Horde_Util::addParameter($editlink, 'action', 'edit');
?>
<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <th width="15%" class="uheader">Menu Name</th>
            <th width="85%" class="uheader">Description</th>
        </tr>
        <?php foreach ($menus as $menu) {
            $url = Horde_Util::addParameter($editurl, 'menu', $menu['name']);
            ?>
        <tr>
            <td><?php echo Horde::link($url) . $menu['name'] . '</a>'; ?></td>
            <td><?php echo $menu['description']; ?></td>
        </tr>
        <?php } ?>
    </table>
</div>
<ul id="controls">
    <?php
    $addurl = Horde::applicationUrl('dialplan.php');
    $addurl = Horde_Util::addParameter($addurl, 'action', 'add');

    ?>
    <li class="button"><a href="<?php echo $addurl; ?>">
        <?php echo Horde::img('add-menu.png'); ?>&nbsp;New Menu
        </a>
    </li>
</ul>