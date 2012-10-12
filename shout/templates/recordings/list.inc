<div class="header">
    Account: <?php echo $GLOBALS['session']->get('shout', 'curaccount_name'); ?>
</div>

<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr><?php // FIXME: Change Size column to recording length ?>
            <td class="uheader">Recording Name</td>
            <td class="uheader">Recording ID</td>
        </tr>
        <?php
            $url = Horde::url("recordings.php")->add('action', 'delete');
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
    <li><a class="horde-create" href="<?php echo $url->add('action', 'add') ?>">
        <?php echo Horde::img('recording-add.png'); ?>&nbsp;New Recording
        </a>
    </li>
</ul>
