<div class="header">
    Account: <?php echo $_SESSION['shout']['accounts'][$curaccount]; ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Account Code</td>
            <td id ="destinationsCol" class="uheader">Account Name</td>
        </tr>
        <?php
            foreach ($accounts as $code => $name) {

                $url = Horde::applicationUrl("admin.php");
                $url = Horde_Util::addParameter($url,
                    array(
                        'account' => $code,
                    )
                );
                $editurl = Horde_Util::addParameter($url, 'action', 'edit');
                $deleteurl = Horde_Util::addParameter($url, 'action', 'delete');
        ?>
        <tr class="item" style="vertical-align: top">
            <td>
                <?php echo Horde::link($editurl); echo $code; ?></a>
            </td>
            <td>
                <?php echo $name; ?>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>
<ul id="controls">
    <?php
    $addurl = Horde::applicationUrl('admin.php');
    $addurl = Horde_Util::addParameter($addurl, 'action', 'add');
    ?>
    <li class="button"><a href="<?php echo $addurl; ?>">
        <?php echo Horde::img('extension-add.png'); ?>&nbsp;New Account
        </a>
    </li>
</ul>