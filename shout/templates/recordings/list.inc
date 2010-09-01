<div class="header">
    Account: <?php echo $_SESSION['shout']['curaccount']['name']; ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr><?php // FIXME: Change Size column to recording length ?>
            <td class="uheader">Recording Name</td>
            <td class="uheader">Recording ID</td>
        </tr>
        <?php
            $url = Horde::url("recordings.php");
            $deleteurl = Horde_Util::addParameter($url, 'action', 'delete');
            foreach ($recordings as $info) {
                ?>
                <tr class="item" style="vertical-align: top">
                    <td>
                        <?php echo $info['filename']; ?>
                    </td>
                    <td>
                        <?php echo $info['id']; ?>
                    </td>
                </tr>
                <?php
            }
            ?>
    </table>
</div>
<ul id="controls">
    <?php
    $addurl = Horde_Util::addParameter($url, 'action', 'add');
    ?>
    <li><a class="button" href="<?php echo $addurl; ?>">
        <?php echo Horde::img('recording-add.png'); ?>&nbsp;New Recording
        </a>
    </li>
</ul>
