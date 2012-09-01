<div class="horde-buttonbar">
  <ul>
    <li>
      <?php echo $this->today ?>
    </li>
    <li><a href="<?php echo $this->previous ?>" class="kronolithPrev" title="<?php echo _("Previous") ?>">&lt;</a></li>
    <li class="horde-active"><span><?php echo $this->current ?></span></li>
    <li><a href="<?php echo $this->next ?>" class="kronolithNext" title="<?php echo _("Next") ?>">&gt;</a></li>
    <li class="horde-icon<?php if ($this->active == 'day') echo ' horde-active' ?>"><?php echo $this->day ?></li>
    <li class="horde-icon<?php if ($this->active == 'workweek') echo ' horde-active' ?>"><?php echo $this->workWeek ?></li>
    <li class="horde-icon<?php if ($this->active == 'week') echo ' horde-active' ?>"><?php echo $this->week ?></li>
    <li class="horde-icon<?php if ($this->active == 'month') echo ' horde-active' ?>"><?php echo $this->month ?></li>
    <li class="horde-icon<?php if ($this->active == 'year') echo ' horde-active' ?>"><?php echo $this->year ?></li>
  </ul>
</div>
