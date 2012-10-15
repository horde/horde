<h2 class="header"><?php echo $this->h($this->desc) ?></h2>

<table class="itipSummary">
<?php if (strlen($this->start)): ?>
 <tr>
  <td><strong><?php echo _("Start") ?>:</strong></td>
  <td><?php echo $this->start ?></td>
 </tr>
<?php endif; ?>

<?php if (strlen($this->end)): ?>
 <tr>
  <td><strong><?php echo _("End") ?>:</strong></td>
  <td><?php echo $this->end ?></td>
 </tr>
<?php endif; ?>

<?php if (strlen($this->priority)): ?>
 <tr>
  <td><strong><?php echo _("Priority") ?>:</strong></td>
  <td><?php echo $this->priority ?></td>
 </tr>
<?php endif; ?>

<?php if (strlen($this->summary) || strlen($this->summary_error)): ?>
 <tr>
  <td><strong><?php echo _("Summary") ?>:</strong></td>
  <td>
<?php if (isset($this->summary)): ?>
   <?php echo $this->h($this->summary) ?>
<?php else: ?>
   <em><?php echo $this->h($this->summary_error) ?></em>
<?php endif; ?>
  </td>
 </tr>
<?php endif; ?>

<?php if (strlen($this->desc2)): ?>
 <tr>
  <td><strong><?php echo _("Description") ?>:</strong></td>
  <td><?php echo nl2br($this->h($this->desc2)) ?></td>
 </tr>
<?php endif; ?>

<?php if (strlen($this->loc)): ?>
 <tr>
  <td><strong><?php echo _("Location") ?>:</strong></td>
  <td><?php echo $this->h($this->loc) ?></td>
 </tr>
<?php endif; ?>
</table>

<?php if (isset($this->attendees)): ?>
<h2 class="smallheader"><?php echo _("Attendees") ?>:</h2>

<table class="itipAttendees">
 <thead class="leftAlign">
  <tr>
   <th><?php echo _("Name") ?></th>
   <th><?php echo _("Role") ?></th>
   <th><?php echo _("Status") ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($this->attendees as $v): ?>
  <tr>
   <td><?php echo $this->h($v['attendee']) ?></td>
   <td><?php echo $v['role'] ?></td>
   <td><?php echo $v['status'] ?></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
<?php endif; ?>

<?php if (isset($this->conflicts)): ?>
<h2 class="smallheader"><?php echo _("Possible Conflicts") ?>:</h2>

<table class="itipconflicts">
<?php foreach ($this->conflicts as $v): ?>
 <tr class="<?php echo (empty($v['collision']) ? 'itipcollision' : 'itipnearcollision') ?>">
  <td><?php echo $v['title'] ?></td>
  <td><?php echo $v['range'] ?></td>
 </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<?php if (isset($this->options)): ?>
<?php if (count($this->options) == 1): ?>
<input type="hidden" name="itip_action[<?php echo $this->options_id ?>]" value="<?php echo key($this->options) ?>" />
<input type="submit" class="button" value="<?php echo current($this->options) ?>" />
<?php else: ?>
<h2 class="smallheader"><?php echo _("Actions") ?>:</h2>

<label for="action_<?php echo $this->options_id ?>" class="hidden"><?php echo _("Actions") ?></label>
<select id="action_<?php echo $this->options_id ?>" name="itip_action[<?php echo $this->options_id ?>]">
 <option disabled="disabled" value="">-- <?php echo _("Select") ?> --</option>
<?php foreach ($this->options as $k => $v): ?>
 <option value="<?php echo $k ?>"><?php echo $v ?></option>
<?php endforeach; ?>
</select>

<input type="submit" class="button" value="<?php echo _("Go") ?>" />
<?php endif; ?>
<?php endif; ?>
