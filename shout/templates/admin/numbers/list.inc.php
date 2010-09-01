<div id="adminNumberList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Telephone Number</td>
            <td class="uheader">Account Name</td>
            <td class="uheader">Menu Name</td>
        </tr>
        <?php
            $url = Horde::url("admin/numbers.php");
            $editurl = Horde_Util::addParameter($url, 'action', 'edit');
            $deleteurl = Horde_Util::addParameter($url, 'action', 'delete');
            foreach ($numbers as $numberinfo) {
                $accountcode = $numberinfo['accountcode'];
                ?>
                <tr class="item">
                    <td>
                        <?php echo Horde::link(Horde_Util::addParameter($editurl,
                                    array('number' => $numberinfo['number'])));
                              echo $numberinfo['number']; echo '</a>'; ?>
                    </td>
                    <td>
                        <?php echo $accounts[$accountcode]['name']; ?>
                    </td>
                    <td>
                        <?php echo $numberinfo['menuName']; ?>
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
        <?php echo Horde::img('extension-add.png'); ?>&nbsp;New Number
        </a>
    </li>
</ul>