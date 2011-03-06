<?php
/**
 * http://www.scribd.com/platform/documentation/api?method_name=Authentication
 * http://www.scribd.com/platform/account
 *
 */

error_reporting(E_ALL);

require 'Horde/Autoloader.php';

$scribd = new Horde_Service_Scribd(array(
    'api_key' => '',
    'api_secret' => '',
));

foreach ($scribd->getList() as $doc) {
    echo $doc->doc_id . ': ' . $doc->title . "\n";
    $doc_id = $doc->doc_id();
}

var_dump($scribd->getConversionStatus($doc_id));
var_dump($scribd->getSettings($doc_id));


if (false) {
/**
 * Upload a document from a file
*/

$file = '../testfile.txt'; //a reference to the file in reference to the current working directory.
$doc_type = null;
$access = null;
$rev_id = null;
// $data = $scribd->upload($file, $doc_type, $access, $rev_id); // returns Array ( [doc_id] => 1026598 [access_key] => key-23nvikunhtextwmdjm2i )


/**
 * Upload a document from a URL
*/

$scribd->my_user_id = '143'; # The user ID of one of your users
$url = 'http://lib.store.yahoo.net/lib/paulgraham/onlisp.ps';
$doc_type = null;
$access = "public";
$rev_id = null; // By using a id stored in a database, you can update an existing document without creating a new one

// $data = $scribd->uploadFromUrl($url, $doc_type, $access, $rev_id); // returns Array ( [doc_id] => 1021237 [access_key] => key-dogbmich9x5iu09kiki )

$doc_id = "1021237";

//$data = $scribd->getConversionStatus($doc_id); // returns PROCESSING



/**
 * Get settings (various meta-information) of a document
*/

$doc_id = "1026450";

//$data = $scribd->getSettings($doc_id); // returns Array ( [doc_id] => 1021237 [title] => onlisp [description] => [access] => public [license] => by-nc [tags] => [show_ads] => default [access_key] => key-dogbmich9x5iu09kiki )





$doc_ids = array("1026450"); // This dosen't HAVE to be an array, im simply demonstrating that it can be done with one.
$title = "New, Updated Title 2";
$description = "Updated Description";
$access = "private";
$license = "pd"; //public domain.. c for normal copyright.. ect.
$parental_advisory = "adult";
$show_ads = "false"; // setting this to "default" will use the configured option in your account
$tags = "tag, another tag, another tag"; //You can also use an array here

//$data = $scribd->changeSettings($doc_ids, $title, $description, $access, $license, $parental_advisory, $show_ads, $tags); //returns 1



/**
 * Delete a document
*/


$doc_id = "1024559";

//$data = $scribd->delete($doc_id); //returns 1



/**
 * Login as a user
*/

$username = "aeinstein3";
$password = "whitehair";

//$data = $scribd->login($username, $password); // Array ( [session_key] => sess-1d9t8wze460fbhp7jw0p [user_id] => 195134 [username] => aeinstein3 [name] => )




/**
 * Create a new Scribd account
*/

$username = "aeinstein3";
$password = "whitehair";
$email = "ae04@gmail.com";
$name = "Alby Dinosour"; // optional

//$data = $scribd->signup($username, $password, $email, $name); //returns Array ( [session_key] => sess-1d9t8wze460fbhp7jw0p [user_id] => 195134 [username] => aeinstein3 [name] => )
//NOTICE :: you need to login as a user before adding files, signup does not automaticly 'sign you in'





/**
 * Search for docs on Scribd
*/

$query = "fun";
$num_results = 20;
$num_start = 10; // this will bring results 10-30 back
$scope = "all"; // user (default) or all -- using test will throw an error.

try{
//  $data = $scribd->search($query, $num_results, $num_start, $scope); // returns
}catch( exception $e){
    $trace = $e->getTrace();
    echo "<br /><b>Scribd Error</b>: ".$e->getMessage()." in <b>".$trace[1]['file']."</b> on line <b>".$trace[1]['line']."</b><br />";
}

print_r($data);

/*

Assuming $scope is set to default or all :)

Array
(
    [result] => Array
        (
            [doc_id] => 501796
            [title] => Andrew Loomis - Fun WIth a Pencil
            [description] =>
        )

[.. cut for brevity ..]

    [result 20] => Array
        (
            [doc_id] => 515437
            [title] => Andrew Loomis - Fun With A Pencil
            [description] =>
        )

*/
}
