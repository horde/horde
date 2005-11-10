<?php
if(!isset($dialplan['extensions'])) {
    ?>
    <div id="extensionDetail">
        <div class="extensionBox">No Configured Extensions</div>
    </div>
    <?php
} else {
    ?>
    <script language="JavaScript" type="text/javascript">

    var dialplan = Array();
    var extensions = Array();
    <?php
    foreach($dialplan['extensions'] as $extension => $priorities) {
        print "extensions['$extension'] = '".Shout::exten2name($extension)."'\n";
        foreach($priorities as $priority => $data) {
            print "dialplan['$extension']['$priority']['application'] = '".$data['application']."'\n";
            print "dialplan['$extension']['$priority']['args'] = '".$data['args']."'\n";
        }
    }
    ?>
    </script>
    <table id='extensionDetail' cellspacing="0">
    <?
    $e = 0;
    foreach($dialplan['extensions'] as $extension => $priorities) { ?>
        <tr class="extension"
            id="extension_<?php echo $extension; ?>"
            name="extension_<?php echo $extension; ?>">
            <a name="<?php echo $extension; ?>">
                <td class="extensionBox"
                    name="<?php echo "eBox-$extension"; ?>"
                    id="<?php echo "eBox-$extension"; ?>">
                    <span onclick="javascript:highlightExten('<?php echo $extension; ?>');">
                        <?php echo Shout::exten2name($extension); ?>
                    </span>
                </td>
            </a>
        </tr>

        <tr>
            <td>
                <table class="pList" cellspacing="0"
                    name="<?php echo "pList-$extension"; ?>"
                    id="<?php echo "pList-$extension"; ?>">
                    <?php
                    $p = 0;
                    foreach($priorities as $priority => $data) {
                        ?>
                        <tr class="priority">
                            <td class="pButtons"
                                id="<?php echo "pButtons-$extension-$priority"; ?>"
                                name="<?php echo "pButtons-$extension-$priority"; ?>">
                                <span class="add">+</span>
                                <span class="remove">-</span>
                            </td>
                            <td class="pElement"
                                id="<?php echo "pNumber-$extension-$priority"; ?>"
                                name="<?php echo "pNumber-$extension-$priority"; ?>"
                                onclick="javascript:activatePriority('<?php
                                    echo $extension; ?>', '<?php
                                    echo $priority; ?>')">
                                <span class="priorityBox">
                                    <?php echo $priority; ?>
                                </span>
                            </td>
                            <td class="pElement"
                                id="<?php echo "pApp-$extension-$priority"; ?>"
                                name="<?php echo "pApp-$extension-$priority"; ?>">
                                <span class="applicationBox">
                                    <select
                                        name="app[<?php echo $extension; ?>][<?php echo $priority; ?>]"
                                        id="app[<?php echo $extension; ?>][<?php echo $priority; ?>]">
                                        <option value="<?php echo $data['application']; ?>">
                                            <?php echo $data['application']; ?></option>
                                    </select>
                                </span>
                            </td>
                            <td class="pElement"
                                id="<?php echo "pArgs-$extension-$priority"; ?>"
                                name="<?php echo "pArgs-$extension-$priority"; ?>">
                                <span class="argBox">
                                    <?php echo $data['args']; ?>
                                </span>
                            </td>
                        </tr>
                        <?php
                        $p++;
                    }
                    ?>
                </table>
                <br />
            </td>
        </tr>
        <?php
        $e++;
    } ?>
    </table>
    <?php
}
?>
