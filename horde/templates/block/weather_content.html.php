    <div class="control">
      <strong><?php echo $this->station->name?> </strong>
      <?php if ($this->current->time->timestamp()):?>
          <?php echo sprintf(_("Local time: %s %s (UTC %s)"), $this->current->time->strftime($GLOBALS['prefs']->getValue('date_format')), $this->current->time->strftime($GLOBALS['prefs']->getValue('time_format')), $this->station->getOffset())?>
      <?php endif;?>
    </div>

    <div class="horde-content">
      <!-- Weather alerts -->
      <?php if (!empty($this->alerts)): ?>
        <?php foreach ($this->alerts as $alert): ?>
          <div class="hordeWeather<?php echo (!empty($this->sig) ? $this->sig : 'Alert') ?>">
            <?php if (!empty($alert['type'])): ?>
            <span style="font-weight:bold;"><?php echo $this->h($alert['type']) ?></span><br />
            <?php endif ?>
            <?php $desc = preg_replace($this->languageFilter, '', $alert['desc']) ?>
            <?php if (!empty($desc)): ?>
            <?php echo $this->h($desc) ?><br />
            <?php endif ?>
            <?php if (!empty($alert['date'])): ?>
            <?php $alert['date']->setTimezone($this->timezone); ?>
            <strong><?php echo _("Starts:") ?></strong> <?php echo $this->h($alert['date']->strftime($this->dateFormat . ' ' . $this->timeFormat)) ?><br />
            <?php elseif (!empty($alert['date_text'])): ?>
            <strong><?php echo _("Starts:") ?></strong> <?php echo $this->h($alert['date_text']) ?><br />
            <?php endif ?>
            <?php if (!empty($alert['expires'])): ?>
            <?php $alert['expires']->setTimezone($this->timezone); ?>
            <strong><?php echo _("Expires:") ?></strong> <?php echo $this->h($alert['expires']->strftime($this->dateFormat . ' ' . $this->timeFormat)) ?><br />
            <?php elseif (!empty($alert['expires_text'])): ?>
            <strong><?php echo _("Expires:") ?></strong> <?php echo $this->h($alert['expires_text']) ?>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      <?php endif ?>

      <!-- Sunrise/Sunset -->
      <?php if ($this->station->sunrise):?>
        <strong><?php echo _("Sunrise")?>: </strong>
        <?php echo Horde_Themes_Image::tag('block/sunrise/sunrise.png', array('alt' => _("Sunrise")))
            . sprintf("%s %s", $this->station->sunrise->strftime($GLOBALS['prefs']->getValue('date_format')), $this->station->sunrise->strftime($GLOBALS['prefs']->getValue('time_format')))?>
        <strong><?php echo _("Sunset")?>: </strong>
        <?php echo Horde_Themes_Image::tag('block/sunrise/sunset.png', array('alt' => _("Sunset")))
            . sprintf("%s %s", $this->station->sunset->strftime($GLOBALS['prefs']->getValue('date_format')), $this->station->sunset->strftime($GLOBALS['prefs']->getValue('time_format')))?>
        <br />
      <?php endif;?>

      <!--Temperture/Dew point -->
       <strong><?php echo _("Temperature")?>: </strong>
       <?php echo $this->current->temp . '&deg;' . Horde_String::upper($this->units['temp'])?>
       <?php if (is_numeric($this->current->dewpoint)):?>
         <strong><?php echo _("Dew point")?>: </strong><?php echo round($this->current->dewpoint) . '&deg;' . Horde_String::upper($this->units['temp'])?>
       <?php endif;?>

       <!-- Pressure/Trend-->
       <?php if ($this->current->pressure):?>
         <br /><strong><?php echo _("Pressure")?>: </strong>
            <?php if (empty($this->current->pressure_trend)):
              echo sprintf('%d %s', round($this->current->pressure), $this->units['pres']);
            else:
              echo sprintf(_("%d %s and %s"), round($this->current->pressure), $this->units['pres'], _($this->current->pressure_trend));
            endif;
       endif;?>

       <!-- Wind -->
       <?php if ($this->current->wind_direction):?>
         <br /><strong><?php echo _("Wind")?>: </strong>
         <?php echo sprintf(_("From the %s (%s&deg;) at %s %s"), $this->current->wind_direction, $this->current->wind_degrees, $this->current->wind_speed, $this->units['wind']);
            if ($this->current->wind_gust > 0):
                echo ', ' . _("gusting") . ' ' . $this->current->wind_gust . ' ' . $this->units['wind'];
            endif;
        endif;?>

        <!-- Humidity-->
        <?php if ($this->current->humidity):?>
          <br /><strong><?php echo _("Humidity")?>: </strong><?php echo $this->current->humidity;?>
        <?php endif; ?>

        <!-- Visibility-->
        <?php if ($this->current->visibility):?>
          <strong><?php echo _("Visibility")?>: </strong><?php echo round($this->current->visibility) . ' ' . $this->units['vis']?>
        <?php endif;?>

        <!-- Current conditions -->
        <br /><strong><?php echo _("Current condition")?>: </strong>
        <?php if ($this->current->icon):?>
          <?php echo Horde_Themes_Image::tag('weather/32x32/' . $this->current->icon) .  ' ' . $this->current->condition?>
        <?php endif;?>
    </div>

    <!-- Forecast -->
    <?php if ($this->params['days'] > 0):?>
      <div class="control"><strong><?php echo sprintf(_("%d-day forecast"), $this->params['days'])?></strong></div>
      <?php $futureDays = 0; ?>
      <table class="hordeBlockWeatherForecast">
         <tr>
           <th><?php echo _("Day")?></th>
           <th><?php echo sprintf(_("Temperature%s(%sHi%s/%sLo%s)"), '<br />', '<span style="color:red">', '</span>', '<span style="color:blue">', '</span>')?></th>
           <th><?php echo _("Condition")?></th>
           <?php if (isset($this->params['detailedForecast'])):?>
              <?php if (in_array(Horde_Service_Weather::FORECAST_FIELD_PRECIPITATION, $this->forecast->fields)):?>
                <th><?php echo sprintf(_("Precipitation%schance"), '<br />')?></th>
              <?php endif;?>
              <?php if (in_array(Horde_Service_Weather::FORECAST_FIELD_HUMIDITY, $this->forecast->fields)):?>
                <th><?php echo _("Humidity")?></th>
              <?php endif;?>
              <?php if (in_array(Horde_Service_Weather::FORECAST_FIELD_WIND, $this->forecast->fields)):?>
               <th><?php echo _("Wind")?></th>
              <?php endif;?>
            <?php endif;?>
         </tr>
         <?php $which = -1;?>
         <?php foreach ($this->forecast as $day):
           $which++;
           if ($which > $this->params['days']):
             break;
           endif;?>
           <tr class="rowEven">
             <td><strong><?php if ($which == 0): echo _("Today"); elseif ($which == 1): echo _("Tomorrow"); else: echo strftime('%A', mktime(0, 0, 0, date('m'), date('d') + $futureDays, date('Y'))); endif;?></strong><br /><?php echo strftime('%b %d', mktime(0, 0, 0, date('m'), date('d') + $futureDays, date('Y')));?></td>
             <td><span style="color:red"><?php echo $day->high . '&deg;' . Horde_String::upper($this->units['temp'])?></span>/<span style="color:blue"><?php echo $day->low . '&deg;' . Horde_String::upper($this->units['temp'])?></span></td>
             <td><?php echo Horde_Themes_Image::tag('weather/32x32/' . $day->icon)?><br /><?php echo $day->conditions?></td>
              <?php if (isset($this->params['detailedForecast'])):?>
               <?php if (in_array(Horde_Service_Weather::FORECAST_FIELD_PRECIPITATION, $this->forecast->fields)):?>
                 <td><?php echo ($day->precipitation_percent >= 0 ? $day->precipitation_percent . '%' : _("N/A"))?></td>
               <?php endif;?>
               <?php if (in_array(Horde_Service_Weather::FORECAST_FIELD_HUMIDITY, $this->forecast->fields)):?>
                 <td><?php echo ($day->humidity ? $day->humidity . '%': _("N/A"))?></td>
                <?php endif;?>
                <?php if (in_array(Horde_Service_Weather::FORECAST_FIELD_WIND, $this->forecast->fields)):?>
                  <?php if ($day->wind_direction):?>
                    <td> <?php echo sprintf(_("From the %s at %s %s"), $day->wind_direction, $day->wind_speed, $this->units['wind']); if ($day->wind_gust && $day->wind_gust > $day->wind_speed): echo ', ' . _("gusting") . ' ' . $day->wind_gust . ' ' . $this->units['wind']; endif?></td>
                  <?php else:?>
                   <td><?php echo _("N/A")?></td>
                  <?php endif;?>
                <?php endif;?>
              <?php endif;?>
           </tr>
           <?php $futureDays++;?>
         <?php endforeach;?>
      </table>
      <!-- Logo -->
      <?php if ($this->logo):?>
        <div class="rightAlign"><?php echo _("Weather data provided by") . ' ' . Horde::link(Horde::externalUrl($this->link), $this->title, '', '_blank', '', $this->title) . Horde_Themes_Image::tag($this->logo)?></a></div>
      <?php else:?>
        <div class="rightAlign"><?php echo _("Weather data provided by") . ' ' . Horde::link(Horde::externalUrl($this->link), $this->title, '', '_blank', '', $this->title) . '<em>' . $this->title?></em></a></div>
      <?php endif;?>
  <?php endif; ?>
