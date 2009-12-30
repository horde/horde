<tr valign="top">
    <td width="15%" align="right" class="item<?php echo $i % 2; ?>">
        <input type="text"
            name="priority[<?php echo $i; ?>]" value="<?php echo $priority; ?>"
            size="3" id="priority[<?php echo $i; ?>]" /></td>
    <td class="item<?php echo $i % 2; ?>">
        <input type="text" name="application[<?php echo $i; ?>]"
            size="40" value="<?php echo $application; ?>"
            id="application[<?php echo $i; ?>]" />
    </td>
</tr>