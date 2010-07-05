<ul>
<?php foreach ($this->results as $tag): ?>
 <li value="<?php echo $this->escape($tag['tag_id']) ?>" created="<?php echo $this->escape($tag['created']) ?>"><?php echo $this->escape($tag['tag_name']) ?></li>
<?php endforeach ?>
</ul>
