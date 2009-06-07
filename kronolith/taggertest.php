<?php
@define('KRONOLITH_BASE', dirname(__FILE__));
include_once KRONOLITH_BASE . '/lib/base.php';
include KRONOLITH_TEMPLATES . '/common-header.inc';
include KRONOLITH_TEMPLATES . '/menu.inc';

Kronolith_Imple::factory('TagAutoCompleter',
     array('triggerId' => 'tags'));
?>

<input id="tags" name="tags" />