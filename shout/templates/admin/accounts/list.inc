<div id="extensionList">
    <table width="100%" cellspacing="0" class="striped">
        <tr>
            <td class="uheader">Account Code</td>
            <td class="uheader">Account Name</td>
            <td class="uheader">Admin PIN</td>
        </tr>
        <?php
            $url = Horde::url("admin/accounts.php");
            $editurl = Horde_Util::addParameter($url, 'action', 'edit');
            $deleteurl = Horde_Util::addParameter($url, 'action', 'delete');
            foreach ($accounts as $acctinfo) {
                $code = $acctinfo['code'];
                ?>
                <tr class="item" style="vertical-align: top">
                    <td>
                        <?php echo Horde::link(Horde_Util::addParameter($editurl,
                            array('account' => $code))); echo $code; echo '</a>'; ?>
                    </td>
                    <td>
                        <?php echo $acctinfo['name']; ?>
                    </td>
                    <td>
                        <?php echo $acctinfo['adminpin']; ?>
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
        <?php echo Horde::img('extension-add.png'); ?>&nbsp;New Account
        </a>
    </li>
</ul>