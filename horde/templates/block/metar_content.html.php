<div class="control">
  <?php if ($this->error):?>
    <strong><?php echo sprintf(_("No weather information provided for %s."), $this->requested_location)?></strong>
  <?php else: ?>
    <strong><?php echo $this->location_title?></strong><br />
    <?php echo sprintf(_("Last Updated: %s"), $this->weather['update'])?>
  <?php endif;?>
</div>

<?php if (empty($this->error)):?>
<div class="horde-content">
  <?php if (isset($this->wind)): ?>
    <strong><?php echo _("Wind")?>:</strong> <?php echo $this->wind?>
  <?php endif; ?>
  <?php if (isset($this->weather['visibility'])): ?>
    <strong><?php echo _("Visibility")?>:</strong> <?php echo sprintf('%s %s', $this->weather['visibility'], $this->units['distance'])?>
  <?php endif;?>
  <?php if (isset($this->weather['temperature'])):?>
    <br /><strong><?php echo _("Temperature")?>:</strong> <?php echo sprintf('%s %s', $this->weather['temperature'], $this->units['temp'])?>
  <?php endif;?>
  <?php if (isset($this->weather['dewPoint'])):?>
    <strong><?php echo _("Dew Point")?>:</strong> <?php echo sprintf('%s %s', $this->weather['dewPoint'], $this->units['temp'])?>
  <?php endif;?>  <?php if (isset($this->weather['feltTemperature'])):?>
    <strong><?php echo _("Feels like")?>:</strong> <?php echo sprintf('%s %s', $this->weather['feltTemperature'], $this->units['temp'])?>
  <?php endif;?>
  <?php if (isset($this->weather['pressure'])):?>
    <br /><strong><?php echo _("Pressure")?>:</strong> <?php echo sprintf('%s %s', $this->weather['pressure'], $this->units['pres'])?>
  <?php endif;?>
  <?php if (isset($this->weather['humidity'])):?>
    <strong><?php echo _("Humidity")?>:</strong> <?php echo $this->weather['humidity']?>
  <?php endif;?>
  <?php if (isset($this->clouds)):?>
    <br /><strong><?php echo _("Clouds")?>:</strong>
     <?php foreach ($this->clouds as $clouds): ?>
        <div style="margin-left: 8px;">
          <?php if (isset($clouds['height'])):?>
            <?php echo sprintf(_("%s at %s %s"), $clouds['amount'], $clouds['height'], $this->units['height']);?></div>
          <?php elseif (!empty($clouds)): ?>
            <?php echo $clouds['amount']?>
         <?php endif;?>
     <?php endforeach;?>
  <?php endif;?>
  <?php if (isset($this->weather['conditions'])):?>
    <br /><strong><?php echo _("Conditions")?>:</strong> <?php echo $this->weather['conditions'] ?>
  <?php endif;?>
  <?php if (isset($this->clouds)):?>
    <br /><strong><?php echo _("Remarks")?>:</strong> <?php echo $this->remarks . $this->other ?>
  <?php endif;?>
  <?php if (!empty($this->periods)): ?>
     <div class="control">
       <strong><?php echo _("Forecast (TAF)")?></strong><br />
       <?php echo sprintf(
        _("Valid from %s %s to %s %s"),
        $this->taf['validFrom']->setTimezone($this->timezone)->strftime($this->date_format),
        $this->taf['validFrom']->setTimezone($this->timezone)->strftime($this->time_format),
        $this->taf['validTo']->setTimezone($this->timezone)->strftime($this->date_format),
        $this->taf['validTo']->setTimezone($this->timezone)->strftime($this->time_format)
        )?>
      </div>
     <table width="100%" cellspacing="0">
     <?php foreach ($this->periods as $entry):?>
        <?php $this->item++?>
        <tr class="row<?php echo ($this->item % 2) ? 'Odd' : 'Even' ?>">
          <td align="center" width="50"><?php echo $entry['time']->setTimezone($this->timezone)->strftime($this->time_format)?></td>
          <td>
            <?php if (isset($entry['wind'])):?>
                <strong><?php echo _("Wind")?>:</strong>
                <?php echo $entry['wind']; ?>
                <br />
            <?php endif;?>
            <?php if (isset($entry['temperatureLow']) || isset($entry['temperatureHigh'])):?>
                <strong><?php echo _("Temperature")?>:</strong>
                <?php if (isset($entry['temperatureLow'])): ?>
                    <strong><?php echo _("Low")?>: <?php echo $entry['temperatureLow']?></strong>
                <?php endif;?>
                <?php if (isset($entry['temperatureHigh'])): ?>
                    <strong><?php echo _("High")?>: <?php echo $entry['temperatureHigh']?></strong>
                <?php endif;?>
                <br />
            <?php endif;?>
            <?php if (isset($entry['shear'])):?>
                <strong><?php echo _("Wind Shear")?>:</strong> <?php echo $entry['shear']?>
                <br />
            <?php endif;?>
            <?php if (isset($entry['visibility'])):?>
                <strong><?php echo _("Visibility")?>:</strong> <?php echo $entry['visibility']?>
                <br />
            <?php endif;?>
            <?php if (isset($entry['condition'])):?>
                <strong><?php echo _("Condition")?>:</strong> <?php echo $entry['condition']?>
                <br />
            <?php endif;?>
            <?php foreach ($entry['clouds'] as $clouds):?>
                <?php if (isset($clouds['type'])): echo ' ' . $clouds['type']; endif;?>
                <?php echo ' ' . $clouds['amount'];?>
                <?php if (isset($clouds['height'])): echo ' ' . sprintf(_("at %s %s"), $clouds['height'], $this->units['height']); endif;?>
                <br />
            <?php endforeach;?>
          </td>
        </tr>
        <?php foreach ($entry['fmc'] as $fmcEntry): ?>
            <?php if (empty($fmcEntry)): continue; endif; ?>
            <?php $this->item++;?>
            <tr class="row<?php echo ($this->item % 2) ? 'Odd' : 'Even' ?>">
              <td align="center" width="50">
                * <?php echo $fmcEntry['from']->setTimezone($this->timezone)->strftime($this->time_format)?>
                <br /> -
                <?php echo $fmcEntry['to']->setTimezone($this->timezone)->strftime($this->time_format)?>
              </td>
              <td>
                <strong><?php echo _("Type")?>: </strong> <?php echo $fmcEntry['type'];?>
                <?php if (isset($fmcEntry['probability'])):?>
                    <strong><?php echo _("Prob")?>: </strong><?php echo $fmcEntry['probability']?>%
                <?php endif;?>
                <?php if (isset($fmcEntry['condition'])):?>
                    <strong><?php echo _("Conditions")?>: </strong><?php echo $fmcEntry['condition']?>
                <?php endif;?>
                <?php foreach ($fmcEntry['clouds'] as $clouds):?>
                    <?php if (isset($clouds['type'])): echo ' ' . $clouds['type']; endif;?>
                    <?php echo ' ' . $clouds['amount'];?>
                    <?php if (isset($clouds['height'])): echo ' ' . sprintf(_("at %s %s"), $clouds['height'], $this->units['height']); endif;?>
                <?php endforeach;?>
                <?php if (isset($fmcEntry['visQualifier'])):?>
                    <strong><?php echo _("Visibility")?>: </strong>
                    <?php echo sprintf('%s %s %s', strtolower($fmcEntry['visQualifier']), $fmcEntry['visibility'], $this->units['vis']);?>
                <?php endif;?>
              </td>
            </tr>
        <?php endforeach;?>
     <?php endforeach;?>
     </table>
  <?php endif;?>
</div>
<?php endif;?>