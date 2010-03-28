<div class="header">
    Account: <?php echo $_SESSION['shout']['accounts'][$curaccount]; ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr><?php // FIXME: Change Size column to recording length ?>
            <td class="uheader">Name</td>
            <td class="uheader">Size</td>
        </tr>
        <?php
            foreach ($recordings as $name => $info) {

                $url = Horde::applicationUrl("recordings.php");
                $url = Horde_Util::addParameter($url,
                    array(
                        'name' => $name,
                    )
                );
                $editurl = Horde_Util::addParameter($url, 'action', 'edit');
                $deleteurl = Horde_Util::addParameter($url, 'action', 'delete');
        ?>
        <tr class="item" style="vertical-align: top">
            <td>
                <?php echo Horde::link($editurl); echo $info['name']; ?></a>
            </td>
            <td>
                <?php echo $info['size']; ?>
            </td>
        </tr>
        <?php
            }
        ?>
    </table>
</div>
<ul id="controls">
    <?php
    $addurl = Horde::applicationUrl('recordings.php');
    $addurl = Horde_Util::addParameter($addurl, 'action', 'add');
    ?>
    <li><a class="button" href="<?php echo $addurl; ?>">
        <?php echo Horde::img('recording-add.png'); ?>&nbsp;New Recording
        </a>
    </li>
</ul>
