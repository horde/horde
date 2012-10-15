<div class="horde-buttonbar">
 <ul class="msgactions">
<?php if ($this->delete): ?>
  <li><?php echo $this->delete ?></li>
<?php endif; ?>
<?php if ($this->reply): ?>
  <li>
   <?php echo $this->reply ?>
   <div>
    <ul>
     <li><?php echo $this->reply_sender ?></li>
<?php if ($this->reply_list): ?>
     <li><?php echo $this->reply_list ?></li>
<?php endif; ?>
<?php if ($this->show_reply_all): ?>
     <li><?php echo $this->show_reply_all ?></li>
<?php endif; ?>
    </ul>
   </div>
  </li>
  <li>
   <?php echo $this->forward ?>
<?php if ($this->forward_attach): ?>
   <div>
    <ul>
     <li><?php echo $this->forward_attach ?></li>
     <li><?php echo $this->forward_body ?></li>
     <li><?php echo $this->forward_both ?></li>
    </ul>
   </div>
<?php endif; ?>
  </li>
  <li><?php echo $this->redirect ?></li>
  <li><?php echo $this->editasnew ?></li>
<?php endif; ?>
<?php if ($this->show_thread): ?>
  <li><?php echo $this->show_thread ?></li>
<?php endif; ?>
<?php if ($this->blacklist): ?>
  <li><?php echo $this->blacklist ?></li>
<?php endif; ?>
<?php if ($this->whitelist): ?>
  <li><?php echo $this->whitelist ?></li>
<?php endif; ?>
<?php if ($this->view_source): ?>
  <li><?php echo $this->view_source ?></li>
<?php endif; ?>
<?php if ($this->resume): ?>
  <li><?php echo $this->resume ?></li>
<?php endif; ?>
  <li><?php echo $this->save_as ?></li>
<?php if ($this->spam): ?>
  <li><?php echo $this->spam ?></li>
<?php endif; ?>
<?php if ($this->notspam): ?>
  <li><?php echo $this->notspam ?></li>
<?php endif; ?>
  <li>
   <?php echo $this->headers ?>
   <div>
    <ul>
<?php if ($this->common_headers): ?>
     <li><?php echo $this->common_headers ?></li>
<?php endif; ?>
<?php if ($this->all_headers): ?>
     <li><?php echo $this->all_headers ?></li>
<?php endif; ?>
<?php if ($this->list_headers): ?>
     <li><?php echo $this->list_headers ?></li>
<?php endif; ?>
    </ul>
   </div>
  </li>
<?php if ($this->atc): ?>
  <li>
   <?php echo $this->atc ?>
   <div>
    <ul>
<?php if ($this->show_parts_all): ?>
     <li><?php echo $this->show_parts_all ?></li>
<?php endif; ?>
<?php if ($this->download_all): ?>
     <li><?php echo $this->download_all ?></li>
<?php endif; ?>
<?php if ($this->strip_all): ?>
     <li><?php echo $this->strip_all ?></li>
<?php endif; ?>
    </ul>
   </div>
  </li>
<?php endif; ?>
 </ul>
</div>
