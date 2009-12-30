    // -->
    </script>
    <?php
}
?>
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