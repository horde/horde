# Horde_Imap_Client Documentation

## General Information

### Autoloading

Horde/Imap_Client does not [include()](http://php.net/include) its own files, so an autoloader must be registered that will load the Horde/Imap_Client files from its directory.

If installing via Composer, user the generated include file to load the library.

If using PEAR, or installing from source, [Horde's Autoloader](http://www.horde.org/libraries/Horde_Autoloader) package can be used to do this for you (or any other [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant autoloader).

### Constructor

Interaction with the mail server is handled through a ```Horde_Imap_Client_Base``` object. The object class will be either:

- [```Horde_Imap_Client_Socket```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Socket.html) - IMAP servers
- [```Horde_Imap_Client_Socket_Pop3```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Socket_Pop3.html) - POP3 servers

The minimum necessary configuration needed for the Client constructor is the ```username``` and ```password```. Although default values can be used for the ```hostspec```, ```port```, and ```secure``` options, it is generally a good idea to explicitly set these values to aid in debugging connection issues.

#### Debugging

Debug output of the server protocol communication can be obtained by providing the ```debug``` option. Acceptable values are any PHP supported
wrapper that can be opened via the [```fopen()```](http://php.net/fopen) command. A plain string is inerpreted as a filename, which is probably what most people will want to use.

#### Caching

Horde/Imap_Client provides transparent caching support by using the Horde_Cache package.

Cached information includes the following:

  - Envelope information
  - Message structure
  - IMAP flags (if the IMAP [CONDSTORE](http://tools.ietf.org/html/rfc7162) capability exists on the server)
  - Search results for a mailbox
  - Threading results for a mailbox

To use, a Horde_Cache backend should be configured and provided to the Horde_Imap_Client constructor in the ```cache``` option.  The format of that option can be found in the [constructor API Documentation](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Base.html#method___construct). Information about how to create a Horde_Cache object can be found in the [Horde_Cache API Documentation](http://dev.horde.org/api/master/lib/Cache/).

A simple cache driver configuration is illustrated in the example below.

#### Example

A sample constructor instantation (all further examples assume that this command has been performed and a client object is present in the
```$client``` variable):

```php
try {
    /* Connect to an IMAP server.
     *   - Use Horde_Imap_Client_Socket_Pop3 (and most likely port 110) to
     *     connect to a POP3 server instead. */
    $client = new Horde_Imap_Client_Socket(array(
        'username' => 'foo',
        'password' => 'secret',
        'hostspec' => 'localhost',
        'port' => '143',
        'secure' => 'tls',

        // OPTIONAL Debugging. Will output IMAP log to the /tmp/foo file
        'debug' => '/tmp/foo',

        // OPTIONAL Caching. Will use cache files in /tmp/hordecache.
        // Requires the Horde/Cache package, an optional dependency to
        // Horde/Imap_Client.
        'cache' => array(
            'backend' => new Horde_Imap_Client_Cache_Backend_Cache(array(
                'cacheob' => new Horde_Cache(new Horde_Cache_Storage_File(array(
                    'dir' => '/tmp/hordecache'
                )))
            ))
        )
    ));
} catch (Horde_Imap_Client_Exception $e) {
    // Any errors will cause an Exception.
}
```

The full list of options can be found in the [API Documentation](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Base.html#method___construct).

### Error Handling/Exceptions

As noted in the constructor example, all errors encountered by the library will cause exceptions of the ```Horde_Imap_Client_Exception``` class (or a subclass of that base class) to be thrown.

The exception message will contain a *translated* version of the error message. If the "raw" English version of the message is needed - i.e. for logging purposes - it can be found in the ```$raw_msg``` property.

Further server debug information *might* be found in the ```$details``` property, but this is not guaranteed.

- [API Documentation of the base exception class](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html)
- [Error Message Code Constants](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#constant_ALREADYEXISTS)

#### IMAP Alerts

IMAP alerts are *REQUIRED* by the IMAP specification to be displayed to the user.

These alerts should be caught/handled in the client code by observing the [```Horde_Imap_Client_Base_Alerts```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Base_Alerts.html) object, which is available as the ```$alerts_ob``` property in the Horde_Imap_Client_Base object.

Example:

```php
// First, define an observer in your code:
class Foo implements SplObserver
{
    public function update(SplSubject $subject)
    {
        if ($subject instanceof Horde_Imap_Client_Base_Alerts) {
            // This is where your code processes the alert.
            $alert = $subject->getLast();

            // Alert text: $alert->alert
            // Alert type (optional): $alert->type
        }
    }
}

$foo = new Foo();

// Then, register the observer with the client object.
$client->alerts_ob->attach($foo);
```

## General Function Information

#### Manual Mailbox Loading Not Necessary

<span style="color:red">NOTE:</span> All mailbox arguments to methods in Horde/Imap_Client take the UTF-8 version of the mailbox name. There is no need to worry about converting from/to the internal UTF7-IMAP charset used on IMAP servers.

Alternatively, you can use [Horde_Imap_Client_Mailbox](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Mailbox.html) objects as the mailbox argument. This object can automatically convert the mailbox name if in UTF7-IMAP for you.  Example:

```php
// This creates a mailbox object from UTF-8 data.
$mbox1 = Horde_Imap_Client_Mailbox::create('Envoyé');

// This creates a mailbox object from UTF7-IMAP data.
$mbox2 = Horde_Imap_Client_Mailbox::create('Envoy&AOk-', true);

// $result === true
$result = ($mbox1 == $mbox2);
```

#### UID Object Return

Many method either require UIDs as an argument or return a list of UIDs.  This is done by using the [Horde_Imap_Client_Ids](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Ids.html) object.

This object implements both the ```Countable``` and ```Traversable``` classes, so it can be used directly in ```count()``` and ```foreach()``` commands. If a raw array list of ids is needed, it can be obtained from the object via the ```$ids``` property.

## Mailbox Actions

The following are actions dealing with mailbox-level actions.  Actions dealing with the contents of the mailboxes are discussed below in the "Message Actions" section.

### Listing Mailboxes

Listing mailboxes is accomplished via the [```listMailboxes()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Base.html#method_listMailboxes) function. The search query (or multiple queries) is passed in via the first argument.  The second argument defines which mailboxes that match the search pattern(s) are returned.  Valid modes are:

| Constant Value                            | Description |
|-------------------------------------------|-------------|
| Horde_Imap_Client::MBOX_SUBSCRIBED        | Return subscribed mailboxes |
| Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS | Return subscribed mailboxes that exist on the server |
| Horde_Imap_Client::MBOX_UNSUBSCRIBED      | Return unsubscribed mailboxes |
| Horde_Imap_Client::MBOX_ALL               | Return all mailboxes regardless of subscription status |
| Horde_Imap_Client::MBOX_ALL_SUBSCRIBED    | Return all mailboxes regardless of subscription status, and ensure the '\subscribed' attribute is set if mailbox is subscribed |

The third argument contains additional options to modify the return values.

```listMailboxes()``` returns an array with keys as UTF-8 mailbox names and values as arrays with these keys:

| Key        | Value |
|------------|-------|
| attributes | ```(array)``` List of lower-cased attribute values (if 'attributes' option is true) |
| delimiter  | ```(string)``` The mailbox delimiter character |
| mailbox    | ```(Horde_Imap_Client_Mailbox)``` The mailbox object |
| status     | ```(array)``` Status information |

See the [API Documentation](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Base.html#method_listMailboxes) for more detailed explanations of the options and return values.

Example:

```php
// This example will return status information for all subscribed mailboxes
// (confirmed to exist on the server) in the base directory that begin with
// "sent-mail".
$list = $client->listMailboxes(
    'sent-mail%',
    Horde_Imap_Client::MBOX_SUBSCRIBED_EXISTS
);
```

### Mailbox Creation

[```createMailbox()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#method_createMailbox) will create a new mailbox or throw an Exception on error or if the mailbox already exists.

Example:

```php
$new_mailbox = new Horde_Imap_Client_Mailbox('NewMailboxName');

try {
    $client->createMailbox($new_mailbox);
    // Success
} catch (Horde_Imap_Client_Exception $e) {
    // Failure
}
```

### Mailbox Deletion

[```deleteMailbox()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#method_deleteMailbox) will delete an existing mailbox or throw an Exception on error or if the mailbox doesn't exist.

Example:

```php
$existing_mailbox = new Horde_Imap_Client_Mailbox('ExistingMailboxName');

try {
    $client->deleteMailbox($existing_mailbox);
} catch (Horde_Imap_Client_Exception $e) {
    // Failure
}
```

### Mailbox Rename

[```renameMailbox()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#method_renameMailbox) will rename an existing mailbox or throw an Exception on error or if the mailbox doesn't exist.

Example:

```php
$old_mailbox = new Horde_Imap_Client_Mailbox('OldMailboxName');
$new_mailbox = new Horde_Imap_Client_Mailbox('NewMailboxName');

try {
    $client->renameMailbox($old_mailbox, $new_mailbox);
} catch (Horde_Imap_Client_Exception $e) {
    // Failure
}
```

### Mailbox Subscriptions

[```subscribeMailbox()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#method_subscribeMailbox) sets the subscription status of a mailbox or throws an Exception on error.

Example:

```php
$mailbox = new Horde_Imap_Client_Mailbox('MailboxName');

try {
    // Subscribe
    $client->subscribeMailbox($mailbox, true);

    // Unsubscribe
    $client->subscribeMailbox($mailbox, false);
} catch (Horde_Imap_Client_Exception $e) {
    // Failure
}
```

### Mailbox Status

[```status()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#method_status) - TODO

## Message Storage Actions

To store a message on the mail server, the [```append()```](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Exception.html#method_append) command is used.

Example of simple usage - adding a single message with two flags set (```\Seen``` and ```OtherFlag```):

```php
$mailbox = new Horde_Imap_Client_Mailbox('MailboxName');
$message_data = <<<EOT
From: Foo <foo@example.com>
Subject: Test Message

This is a test message.
EOT;

try {
    $uids = $client->append(
        $mailbox,
        array(
            array(
                'data' => $message_data,
                'flags' => array('\Seen', 'OtherFlag')
            )
        )
    );

    // $uids is a Horde_Imap_Client_Ids object that contains the UID(s) of
    // the messages that were successfully appended to the mailbox.
} catch (Horde_Imap_Client_Exception $e) {
    // Failure
}
```

## Message Actions

### General Information For Message Actions

#### Working with a Single Mailbox

The following examples assume that the user wants to work with the messages contained in their INBOX.

#### Loading Mailbox

There is *no need* to provide commands to login and/or switch to a mailbox. These actions are handled on-demand by the library.

### Listing Messages

The [```search()```](<http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Base.html#method_search) command is used to list the messages in a mailbox. It only requires a mailbox name for basic usage, and will return a list of unsorted UIDs in the mailbox.

```php
// Get a list of all UIDs in the INBOX.
$results = $client->search('INBOX');

// $results['match'] contains a Horde_Imap_Client_Ids object, containing the
// list of UIDs in the INBOX.
$uids = $results['match'];
```

To filter the list of messages returned by ```search()```, a [Horde_Imap_Client_Search_Query](http://dev.horde.org/api/master/lib/Imap_Client/classes/Horde_Imap_Client_Search_Query.html) object can be passed as the second parameter to ```search()```. If not present, all messages in the mailbox are returned.

The third argument to ```search()``` allows additional search parameters to be specified, such as the prefered sort order.

```php
// Advanced example to return the list of all unseen UIDs in the INBOX
// younger than a week, sorted by the From address.
$query = new Horde_Imap_Client_Search_Query();
$query->flag(Horde_Imap_Client::FLAG_SEEN, false);

// 604800 = 60 seconds * 60 minutes * 24 hours * 7 days (1 week)
$query->intervalSearch(
    604800,
    Horde_Imap_Client_Search_Query::INTERVAL_YOUNGER
);

$results = $client->search('INBOX', $query, array(
    'sort' => array(
        Horde_Imap_Client::SORT_FROM
    )
));

// $results['match'] contains a Horde_Imap_Client_Ids object, containing the
// list of UIDs in the INBOX.
$uids = $results['match'];
```

