<div class="horde-buttonbar">
 <ul class="rightFloat">
  <li class="horde-nobutton">
<?php if ($this->multiple_page): ?>
   <form method="get" class="imp-navbar" action="<?php echo $this->mailbox_url ?>">
    <input type="hidden" name="mailbox" value="<?php echo $this->mailbox ?>" />
    <?php echo $this->forminput ?>
<?php if ($this->url_first): ?>
    <a href="<?php echo $this->url_first ?>" title="<?php echo _("First Page") ?>"><span class="iconImg <?php echo $this->pages_first ?>"></span></a>
<?php else: ?>
    <span class="iconImg <?php echo $this->pages_first ?>"></span>
<?php endif; ?>
<?php if ($this->url_prev): ?>
    <a href="<?php echo $this->url_prev ?>" title="<?php echo _("Previous Page") ?>"><span class="iconImg <?php echo $this->pages_prev ?>"></span></a>
<?php else: ?>
    <span class="iconImg <?php echo $this->pages_prev ?>"></span>
<?php endif; ?>
    <label for="page<?php echo $this->id ?>" class="hidden"><?php echo _("Page") ?>:</label>
    <input type="text" id="page<?php echo $this->id ?>" name="page" value="<?php $this->h($this->page_val) ?>" size="<?php echo $this->page_size ?>" />
<?php if ($this->url_next): ?>
    <a href="<?php echo $this->url_next ?>" title="<?php echo _("Next Page") ?>"><span class="iconImg <?php echo $this->pages_next ?>"></span></a>
<?php else: ?>
    <span class="iconImg <?php echo $this->pages_next ?>"></span>
<?php endif; ?>
<?php if ($this->url_last): ?>
    <a href="<?php echo $this->url_last ?>" title="<?php echo _("Last Page") ?>"><span class="iconImg <?php echo $this->pages_last ?>"></span></a>
<?php else: ?>
    <span class="iconImg <?php echo $this->pages_last ?>"></span>
<?php endif; ?>
    </form>
<?php endif; ?>
  </li>
 </ul>
<?php if (!$this->readonly): ?>
 <ul>
<?php if ($this->flaglist_set): ?>
  <li>
   <form class="navbarselect">
    <label for="flag<?php echo $this->id ?>" class="hidden"><?php echo _("Mark Messages") ?></label>
    <select id="flag<?php echo $this->id ?>" name="flag">
     <option value="" selected="selected"><?php echo _("Mark Messages") ?></option>
     <option value="" disabled="disabled">- - - - - - - -</option>
     <option class="actionsSelectSection" value=""><?php echo _("Mark as:") ?></option>
<?php foreach ($this->flaglist_set as $v): ?>
     <option value="<?php echo $v['f'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
     <option value="" disabled="disabled">- - - - - - - -</option>
     <option class="actionsSelectSection" value=""><?php echo _("Unmark as:") ?></option>
<?php foreach ($this->flaglist_unset as $v): ?>
     <option value="<?php echo $v['f'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
    </select>
   </form>
  </li>
<?php endif; ?>
<?php if ($this->filtermsg): ?>
  <li>
   <form class="navbarselect">
    <label for="filter<?php echo $this->id ?>" class="hidden"><?php echo _("Filter Messages") ?></label>
    <select id="filter<?php echo $this->id ?>" name="filter">
     <option value="" selected="selected"><?php echo _("Filter Messages") ?></option>
<?php if ($this->flag_filter): ?>
     <option value="" disabled="disabled">- - - - - - - -</option>
     <option class="actionsSelectSection" value=""><?php echo _("Show Only:") ?></option>
<?php foreach ($this->flaglist_set as $v): ?>
     <option value="<?php echo $v['f'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
      <option value="" disabled="disabled">- - - - - - - -</option>
      <option class="actionsSelectSection" value=""><?php echo _("Don't Show:") ?></option>
<?php foreach ($this->flaglist_unset as $v): ?>
     <option value="<?php echo $v['f'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
<?php endif; ?>
<?php if ($this->filters): ?>
     <option value="" disabled="disabled">- - - - - - - -</option>
<?php foreach ($this->filters as $v): ?>
     <option value="<?php echo $v['v'] ?>">&nbsp;&nbsp;<?php echo $v['l'] ?></option>
<?php endforeach; ?>
<?php endif; ?>
    </select>
   </form>
  </li>
<?php endif; ?>
 </ul>
<?php if ($this->move): ?>
 <form method="post" action="<?php echo $this->mailbox_url ?>">
  <input type="hidden" name="mailbox" value="<?php $this->mailbox ?>" />
  <ul>
   <li>
    <?php echo $this->move ?>
   </li>
   <li>
    <?php echo $this->copy ?>
   </li>
   <li>
    <label for="targetMailbox<?php echo $this->id ?>" class="hidden"><?php echo _("Target Mailbox:") ?></label>
    <select id="targetMailbox<?php echo $this->id ?>" name="targetMailbox">
     <?php echo $this->folder_options ?>
    </select>
   </li>
  </ul>
 </form>
<?php endif; ?>
<?php endif; ?>
</div>
