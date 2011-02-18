<?php
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
$mapper->connect('tags', array('controller' => 'tag', 'action' => 'searchTags'));
$mapper->connect('tags.:(format)', array('controller' => 'tag', 'action' => 'searchTags'));

// Most recent tags. Available query parameters:
//   typeId:    restrict matches to tags that have been applied to objects with type $typeId
//   userId:    restrict matches to tags that have been applied by $userId
$mapper->connect('tags/recent', array('controller' => 'tag', 'action' => 'recentTags'));
$mapper->connect('tags/recent.:(format)', array('controller' => 'tag', 'action' => 'recentTags'));

// List objects. At least a content type, or more specific parameters, are
// required; listing all objects is not allowed.
$mapper->connect('objects', array('controller' => 'tag', 'action' => 'searchObjects'));
$mapper->connect('objects.:(format)', array('controller' => 'tag', 'action' => 'searchObjects'));

// List users. Specific parameters are required as listing all users is not
// allowed.
$mapper->connect('users', array('controller' => 'tag', 'action' => 'searchUsers'));
$mapper->connect('users.:(format)', array('controller' => 'tag', 'action' => 'searchUsers'));


// Tag an object. Required POST parameters are: tags (array or string list) and
// objectId. userId is inferred from the authenticated user:
$mapper->connect('tag', array('controller' => 'tag', 'action' => 'tag',
                              'conditions' => array('method' => array('POST', 'PUT'))));

// Untag an object. Required POST parameters are: tags (array or string list)
// and objectId. userId is inferred from the authenticated user:
$mapper->connect('untag', array('controller' => 'tag', 'action' => 'untag',
                                'conditions' => array('method' => array('POST', 'DELETE'))));
