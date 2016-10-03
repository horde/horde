<div data-role="page" id="eventform-view">
<?php echo $this->smartmobileHeader(array('logout' => true, 'backlink' => false,  'title' => _("Event"))) ?>
  <div data-role="content">
    <form id="eventform" name="eventform">
      <input type="hidden" id="event" name="event" />
      <div id="kronolith-event-data" data-role="collapsible-set" data-content-theme="d">
        <div data-role="collapsible">
          <h3><?php echo _("Basic")?></h3>
          <div data-role="field-contain">
            <label for="event_calendar"><?php echo _("Calendar") ?></label>
            <select id="cal" name="targetcalendar">
            <?php foreach (Kronolith::listInternalCalendars(false, Horde_Perms::EDIT) as $id => $cal):?>
              <option value="<?php echo 'internal|' . $id?>"><?php echo $cal->get('name')?></option>
            <?php endforeach;?>
            </select>
          </div>
          <div data-role="field-contain">
            <label for="title"><?php echo _("Title") ?></label>
            <input id="title" type="text" name="title" />
          </div>
          <div data-role="field-contain">
            <label for="whole_day"><?php echo _("All-day event") ?></label>
            <input id="whole_day" type="checkbox" name="whole_day" />
          </div>
          <div data-role="ui-field-contain">
            <fieldset class="ui-grid-a" data-role="controlgroup">
              <div class="ui-block-a">
                <label for="start_date"><?php echo _("From")?></label>
                <input id="start_date" type="date" name="start_date" />
              </div>
              <div class="ui-block-b">
                <label for="start_time"><?php echo _("at")?></label>
                <input id="start_time" type="time" name="start_time" />
              </div>
            </fieldset>
            <fieldset class="ui-grid-a" data-role="controlgroup">
              <div class="ui-block-a">
                <label for="end_date"><?php echo _("To")?></label>
                <input id="end_date" type="date" name="end_date" />
              </div>
              <div class="ui-block-b">
                <label for="end_time"><?php echo _("at")?></label>
                <input id="end_time" type="time" name="end_time" />
              </div>
            </fieldset>
          </div>
        </div>
        <div data-role="collapsible">
          <h3><?php echo _("Description")?></h3>
          <div data-role="field-contain">
          <label for="description"><?php echo _("Description")?></label>
          <textarea name="description" id="description" rows="5" cols="40"></textarea>
          </div>
        </div>
        <div data-role="collapsible">
          <h3><?php echo _("Attendees")?></h3>
          <label for="attendees"><?php echo _("Attendees")?></label>
          <input id="attendees" name="attendeees" type="email" />
        </div>
        <div data-role="collapsible">
          <h3><?php echo _("Alarm")?>
        </div>
        <div data-role="collapsible">
          <h3><?php echo _("Files")?></h3>
        </div>
      </div>

      <div data-role="footer" class="ui-bar" data-position="fixed">
        <a href="#event-submit"><?php echo _("Save Event") ?></a>
        <a data-rel="back" href="#event-cancel"><?php echo _("Cancel") ?></a>
        <a href="#event-delete"><?php echo _("Delete") ?></a>
      </div>

    </form>
  </div>
</div>