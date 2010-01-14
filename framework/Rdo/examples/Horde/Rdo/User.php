<?php
/**
 * @package Horde_Rdo
 */

require_once 'Horde/Autoloader.php';

@include './conf.php';
if (empty($conf['sql'])) {
    die("No sql configuration found\n");
}

/**
 */
class User extends Horde_Rdo_Base {
}

/**
 */
class UserMapper extends Horde_Rdo_Mapper {

    public function getAdapter()
    {
        return $GLOBALS['injector']->getInstance('db-writer');
    }

}

$um = new UserMapper();

// Count all users.
$userCount = $um->count();
echo "# users: $userCount\n";

// Get the number of new users in May 2005
//$userCount = $um->count('created > \'2005-05-01\' AND created <= \'2005-05-31\'');
//echo "# new: $userCount\n";

// Check if id 1 exists.
$exists = $um->exists(1);
echo "exists: " . ($exists ? 'yes' : 'no') . "\n";

// Look for Alice
$userTwo = $um->findOne(array('name' => 'Alice'));
if ($userTwo) {
    echo "Found Alice: id $userTwo->id\n";
} else {
    echo "No Alice found, creating:\n";
    // $userOne = $um->create(array('name' => 'Alice', 'phone' => '212-555-6565'));
    $userOne = new User(array('name' => 'Alice', 'phone' => '212-555-6565'));
    $userOne->setMapper($um);
    $userOne->save();
    $userOneId = $userOne->id;
    echo "Created new user with id: $userOneId\n";
}

// Change the name of the user and save.
if ($userTwo) {
    $userTwo->name = 'Bob';
    $result = $userTwo->save();
    var_dump($result);
}

// List all users.
echo "Looking for all:\n";
foreach ($um->find() as $userOb) {
    echo "  (" . $userOb->id . ") " . $userOb->name . "\n";
}

// Fetch id 2.
//$user = $um->findOne(2);
// Try to delete it.
//$result = $user->delete();
//var_dump($result);

/*
// $user->billingAddresses is an Iterator.
foreach ($user->billingAddresses as $billingAddress) {
    echo $billingAddress->zipCode . "\n";
}

if ($user->favorite) {
    echo $user->favorite->name . "\n";
} else {
    $user->favorite = new User(array('name' => 'Charles'));
}
*/
