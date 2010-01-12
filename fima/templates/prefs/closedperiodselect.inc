<?php if (!$prefs->isLocked('closed_period')): ?>
<br />
<?php echo _("Closed by period:") ?><br />
<?php echo Fima::buildDateWidget('closedperiod', (int)$prefs->getValue('closed_period'), '', _("None"), true); ?>
</select><br /><br />
<?php endif; ?>