<?php

$_root = ltrim(dirname($_SERVER['PHP_SELF']), '/');

/**
 * References:
 * http://code.google.com/apis/gdata/docs/2.0/reference.html#Queries
 */

// List tags. With no parameters, lists all tags. With no file extension, uses
// the default format. Available query parameters:
//   q:         do a starts-with search on tag text
//   typeId:    restrict matches to tags that have been applied to objects with type $typeId
//   userId:    restrict matches to tags that have been applied by $userId
//   objectId:  restrict matches to tags that have been applied to $objectId
$mapper->connect($_root . '/tags', array('controller' => 'tag', 'action' => 'searchTags'));
$mapper->connect($_root . '/tags.:(format)', array('controller' => 'tag', 'action' => 'searchTags'));

// List objects. At least a content type, or more specific parameters, are
// required; listing all objects is not allowed.
$mapper->connect($_root . '/objects', array('controller' => 'tag', 'action' => 'searchObjects'));
$mapper->connect($_root . '/objects.:(format)', array('controller' => 'tag', 'action' => 'searchObjects'));

// List users. Specific parameters are required as listing all users is not
// allowed.
$mapper->connect($_root . '/users', array('controller' => 'tag', 'action' => 'searchUsers'));
$mapper->connect($_root . '/users.:(format)', array('controller' => 'tag', 'action' => 'searchUsers'));


// Tag an object. Required POST parameters are: tags (array or string list) and
// objectId. userId is inferred from the authenticated user:
$mapper->connect($_root . '/tag', array('controller' => 'tag', 'action' => 'tag'));

// Untag an object. Required POST parameters are: tags (array or string list)
// and objectId. userId is inferred from the authenticated user:
$mapper->connect($_root . '/untag', array('controller' => 'tag', 'action' => 'untag'));


// Local route overrides
if (file_exists($CONTENT_DIR . '/config/routes.local.php')) {
    include $CONTENT_DIR . '/config/routes.local.php';
}
