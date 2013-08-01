<table cellspacing="0" width="100%">

<tr>
<td class="header" align="right" valign="top"><?php echo _("Klutz Backend") ?>&nbsp;&nbsp;</td>
<td rowspan="2" valign="middle"><?php echo Horde::img('klutz_butter.png') ?></td>
</tr>

<tr><td class="item" valign="top">

<form action="<?php echo Horde::url('backend.php') ?>" method="get">
<?php echo Horde_Util::formInput(); ?>
<input type="hidden" name="mode" value="fetch" />
<strong><?php echo _("Fetch") ?></strong><br />
<?php echo $comic_select ?><br />
<strong><?php echo _("for") ?></strong><br />
<?php echo $fetch_date_select ?><br />
<input id="overwrite" type="checkbox" name="overwrite" value="true" />
<?php echo Horde::label('overwrite', _("Overwrite comics if they exist?")) ?><br />
<input id="nounique" type="checkbox" name="nounique" value="true" />
<?php echo Horde::label('nounique', _("Don't check for uniqueness")) ?><br />
<input class="horde-default" type="submit" value="<?php echo _("Fetch Comics") ?>" />
</form>
<hr />
<form action="<?php echo Horde::url('backend.php') ?>" method="get">
<?php echo Horde_Util::formInput(); ?>
<input type="hidden" name="mode" value="delete" />
<strong><?php echo _("Delete") ?></strong><br />
<?php echo $comic_select ?><br />
<select name="timeframe">
 <option value="older">on or before</option>
 <option value="date">on</option>
 <option value="newer">on or after</option>
</select>
<?php echo $delete_date_select ?><br />
<input class="horde-delete" type="submit" value="<?php echo _("Delete Comics") ?>" />
</form>
<hr />
<strong><?php echo _("Maintenance Functions") ?></strong>
<ul>
<?php if(method_exists($klutz_driver, 'loadSums')): ?>
<li><a href="<?php echo $sums_url ?>">
<?php echo _("Rebuild Unique Identifiers file.") ?></a></li>
<?php endif; ?>
</ul>

</td></tr>
</table>
