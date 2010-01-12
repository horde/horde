<h1 class="header"><?php echo htmlspecialchars($blocks->getName($app, $name)) ?></h1>
<form action="<?php echo Horde::selfUrl() ?>#block" method="post" id="blockform" name="blockform">
<?php Horde_Util::pformInput() ?>
<input type="hidden" name="action" value="save" />
<input type="hidden" name="block" value="<?php echo Horde_Util::getFormData('block') ?>" />
<input type="hidden" name="widget" value="<?php echo Horde_Util::getFormData('widget') ?>" />
<table style="width: 100%; border-collapse: collapse;">
<?php if ($block->updateable): ?>
<tr>
    <td class="text rightAlign" valign="top"><?php echo Horde::label('params_refresh_time', _("Refresh rate:")) ?>&nbsp;</td>
    <td class="text" valign="top">
    <select id="params_refresh_time" name="params[_refresh_time]">
        <option<?php if ($defaults['_refresh_time'] == 0) echo ' selected="selected"' ?> value="0"><?php echo _("Never") ?></option>
        <option<?php if ($defaults['_refresh_time'] == 30) echo ' selected="selected"' ?> value="30"><?php echo _("Every 30 seconds") ?></option>
        <option<?php if ($defaults['_refresh_time'] == 60) echo ' selected="selected"' ?> value="60"><?php echo _("Every minute") ?></option>
        <option<?php if ($defaults['_refresh_time'] == 300) echo ' selected="selected"' ?> value="300"><?php echo _("Every 5 minutes") ?></option>
        <option<?php if ($defaults['_refresh_time'] == 900) echo ' selected="selected"' ?> value="900"><?php echo _("Every 15 minutes") ?></option>
        <option<?php if ($defaults['_refresh_time'] == 1800) echo ' selected="selected"' ?> value="1800"><?php echo _("Every half hour") ?></option>
        <option<?php if ($defaults['_refresh_time'] == 3600) echo ' selected="selected"' ?> value="3600"><?php echo _("Every hour") ?></option>
    </select>
    </td>
</tr>
<?php endif; ?>
<?php $i = 0; foreach ($params as $id): $i++; ?>
    <tr>
        <td class="<?php echo ($i % 2) ? 'text' : 'item0' ?> rightAlign" valign="top"><?php echo $blocks->getParamName($app, $name, $id) ?>:&nbsp;</td>
        <td class="<?php echo ($i % 2) ? 'text' : 'item0' ?>" valign="top"><?php echo $blocks->getOptionsWidget($app, $name, $id, $defaults) ?></td>
    </tr>
<?php endforeach; ?>
<tr>
<td class="control" colspan="2" style="text-align: center;">
<input type="button" class="button" value="<?php echo _("Save") ?>" onclick="return setParams()" />
<input type="button" class="button" value="<?php echo _("Cancel") ?>" onclick="return cancelRedBox()" />
</td>
</tr>
</table>
</form>
