<?php
if(!isset($dialplan['extensions'])) {
    ?>
    <div id="extensionDetail">
        <div class="extensionBox">No Configured Extensions</div>
    </div>
    <?php
} else {
    ?>
    <script language="JavaScript" type="text/javascript"
             src="/services/javascript.php?file=dialplan.js&amp;app=shout"></script>
    <script language="JavaScript" type="text/javascript">
    <!--
    var shout_dialplan_x = new Array();
    <?php
    foreach($dialplan['extensions'] as $extension => $priorities) {
        print "shout_dialplan_x['$extension'] = new Array();\n";
        print "shout_dialplan_x['$extension']['name'] = '".Shout::exten2name($extension)."'\n";
        print "shout_dialplan_x['$extension']['priorities'] = new Array();\n";
        foreach($priorities as $priority => $data) {
            print "shout_dialplan_x['$extension']['priorities']['$priority'] = new Array();\n";
            print "shout_dialplan_x['$extension']['priorities']['$priority']['application'] = '".$data['application']."'\n";
            print "shout_dialplan_x['$extension']['priorities']['$priority']['args'] = '".$data['args']."'\n";
        }
    }
    ?>

    var dp = new Dialplan('x');

    // -->
    </script>
    <div id='extensionDetail' cellspacing="0">
    <?
    $e = 0;
    foreach($dialplan['extensions'] as $extension => $priorities) { ?>
        <div class="extension"
            id="extension_<?php echo $extension; ?>"
            name="extension_<?php echo $extension; ?>">
            <a name="<?php echo $extension; ?>">
                <div class="extensionBox"
                    name="<?php echo "eBox-$extension"; ?>"
                    id="<?php echo "eBox-$extension"; ?>"
                    onclick="dp.highlightExten('<?php echo $extension; ?>');">
                    <?php echo Shout::exten2name($extension); ?>
                </div>
            </a>
        </div>

        <div>
            <div>
                <div id="<?php echo "pList-$extension"; ?>"
                    name="<?php echo "pList-$extension"; ?>">
                    <table class="pList" cellspacing="0">
                        <?php
                        $p = 0;
                        foreach($priorities as $priority => $data) {
                            ?>
                            <tr class="priority">
                                <td class="pButtons"
                                    id="<?php echo "pButtons-$extension-$priority"; ?>"
                                    name="<?php echo "pButtons-$extension-$priority"; ?>">
                                    <span class="add"
                                        onclick="javascript:dp.addPrio('<?php
                                            echo $extension; ?>', '<?php echo $priority; ?>');">+</span>
                                    <span class="remove"
                                        onclick="javascript:dp.delPrio('<?php
                                            echo $extension; ?>', '<?php echo $priority; ?>');">-</span>
                                </td>
                                <td class="pElement"
                                    id="<?php echo "pNumber-$extension-$priority"; ?>"
                                    name="<?php echo "pNumber-$extension-$priority"; ?>"
                                    onclick="javascript:dp.activatePriority('<?php
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
                </div>
            </div>
        </div>
        <?php
        $e++;
    } ?>
    </div>
    <?php
}
?>
