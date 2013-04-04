============
 Horde_Argv
============

.. contents:: Contents
.. section-numbering::

*Horde_Argv* is a library for parsing command line arguments with various actions, providing help, grouping options, and more. It is ported from Python's Optik (http://optik.sourceforge.net/).

-------------
 Basic Usage
-------------

While *Horde_Argv* is quite flexible and powerful, you don't have to jump through hoops or read reams of documentation to get started. This document aims to demonstrate some simple usage patterns that will get you started using *Horde_Argv* in your scripts.

To parse a command line with *Horde_Argv*, you must create an ``Horde_Argv_Parser`` instance and define some options. You'll have to import the ``Horde_Argv_Parser`` classes in any script that uses *Horde_Argv*, but it is suggested that you use an autoloader instead:

::

 require_once 'Horde/Autoloader/Default.php';

Early on in the main program, create a parser:

::

 $parser = new Horde_Argv_Parser();

Then you can start defining options. The basic syntax is:

::

 $parser->addOption('opt_str', ..., array('attr' => 'value', ...));

That is, each option has one or more option strings, such as "-f" or "--file", and several option attributes that tell *Horde_Argv* what to expect and what to do when it encounters that option on the command line.

Typically, each option will have one short option string and one long option string, e.g.:

::

 $parser->addOption('-f', '--file', ...);

You're free to define as many short option strings and as many long option strings as you like, as long as there is at least one option string overall.

Once all of your options are defined, instruct *Horde_Argv* to parse your program's command line:

::

 list($values, $args) = $parser->parseArgs();

(You can pass an argument list to ``parseArgs()`` if you like, but that's rarely necessary: by default it uses $_SERVER['argv'].)

``parseArgs()`` returns two values:

* $values is a ``Horde_Argv_Values`` object containing values for all of your options -- e.g. if "--file" takes a single string argument, then $values->file (or $values['file']) will be the filename supplied by the user, or NULL if the user did not supply that option.
* $args is the list of arguments left over after parsing options.

This tutorial document only covers the four most important option attributes: "action", "type", "dest" (destination), and "help". Of these, "action" is the most fundamental.

Option actions
==============

Actions tell *Horde_Argv* what to do when it encounters an option on the command line. There is a fixed set of actions hard-coded into *Horde_Argv*; adding new actions is an advanced topic covered in `Extending Horde_Argv`_. Most actions tell *Horde_Argv* to store a value in some variable -- for example, take a string from the command line and store it in an attribute of options.

.. _`Extending Horde_Argv`: Extending Horde_Argv

If you don't specify an option action, *Horde_Argv* defaults to "store".

The store action
****************

The most common option action is store, which tells *Horde_Argv* to take the next argument (or the remainder of the current argument), ensure that it is of the correct type, and store it to your chosen destination.

For example:

::

 $parser->addOption(
     '-f', '--file',
     array('action' => 'store', 'type' => 'string', 'dest' => 'filename')
 );

Now let's make up a fake command line and ask *Horde_Argv* to parse it:

::

 $args = array('-f', 'foo.txt');
 list($values, $args) = $parser->parseArgs(args);

When *Horde_Argv* sees the "-f", it consumes the next argument, "foo.txt", and stores it in $values->filename, where values is the first return value from ``parseArgs()``. So, after this call to ``parseArgs()``, ``$values->filename`` is "foo.txt".

Some other option types supported by *Horde_Argv* are "int" and "float". Here's an option that expects an integer argument:

::

 $parser->addOption('-n', array('type' => 'int', 'dest' => 'num'));

Note that I didn't supply a long option, which is perfectly acceptable. I also didn't specify the action, since the default is "store".

Let's parse another fake command-line. This time, we'll jam the option argument right up against the option -- "-n42" (one argument) is equivalent to "-n 42" (two arguments).

::

 list($values, $args) = $parser->parseArgs(array('-n42'));
 echo $values->num;

will print "42".

Trying out the "float" type is left as an exercise for the reader.

If you don't specify a type, *Horde_Argv* assumes "string". Combined with the fact that the default action is "store", that means our first example can be a lot shorter:

::

 $parser->addOption('-f', '--file', array('dest' => 'filename'))

If you don't supply a destination, *Horde_Argv* figures out a sensible default from the option strings: if the first long option string is "--foo-bar", then the default destination is "foo_bar". If there are no long option strings, *Horde_Argv* looks at the first short option: the default destination for "-f" is "f".

Adding types is covered in "Extending *Horde_Argv*".

Handling flag (boolean) options
*******************************

Flag options -- set a variable to TRUE or FALSE when a particular option is seen -- are quite common. *Horde_Argv* supports them with two separate actions, "store_true" and "store_false". For example, you might have a verbose flag that is turned on with "-v" and off with "-q":

::

 $parser->addOption('-v', array('action' => 'store_true', 'dest' => 'verbose'));
 $parser->addOption('-q', array('action' => 'store_false', 'dest' => 'verbose'));

Here we have two different options with the same destination, which is perfectly OK. (It just means you have to be a bit careful when setting default values -- see Default values, below.)

When *Horde_Argv* sees "-v" on the command line, it sets the verbose attribute of the special "option values" object to a TRUE value; when it sees "-q", it sets verbose to a FALSE value.

Other actions
*************

Some other actions supported by *Horde_Argv* are:

:"store_const": store a constant value
:"append":      append this option's argument to a list
:"count":       increment a counter by one
:"callback":    call a specified function

These are covered in the `Advanced Usage`_ and `Option Callbacks`_ documents.

.. _`Advanced Usage`: Advanced Usage
.. _`Option Callbacks`: Option Callbacks

Default values
==============

All of the above examples involve setting some variable (the "destination") when certain command-line options are seen. What happens if those options are never seen? Since we didn't supply any defaults, they are all set to NULL. Usually, this is just fine, but sometimes you want more control. To address that need, *Horde_Argv* lets you supply a default value for each destination, which is assigned before the command-line is parsed.

First, consider the verbose/quiet example. If we want *Horde_Argv* to set verbose to TRUE unless "-q" is seen, then we can do this:

::

 $parser->addOption('-v', array('action' => 'store_true', 'dest' => 'verbose', $default => true));
 $parser->addOption('-q', array('action' => 'store_false', 'dest' => 'verbose'));

Oddly enough, this is exactly equivalent:

::

 $parser->addOption('-v', array('action' => 'store_true', 'dest' => 'verbose'));
 $parser->addOption('-q', array('action' => 'store_false', 'dest' => 'verbose', $default => true));

Those are equivalent because you're supplying a default value for the option's destination, and these two options happen to have the same destination (the verbose variable).

Consider this:

::

 $parser->addOption('-v', array('action' => 'store_true', 'dest' => 'verbose', $default => false));
 $parser->addOption('-q', array('action' => 'store_false', 'dest' => 'verbose', $default => true));

Again, the default value for verbose will be TRUE: the last default value supplied for any particular destination attribute is the one that counts.

A clearer way to specify default values is the ``setDefaults()`` method of ``Horde_Argv_Parser``, which you can call at any time before calling ``parseArgs()``:

::

 $parser->setDefaults(array('verbose' => true));
 $parser->addOption(...);
 list($values, $args) = $parser->parseArgs();

As before, the last value specified for a given option destination is the one that counts. For clarity, try to use one method or the other of setting default values, not both.

Generating help
===============

There is one more feature that you will use in every script: *Horde_Argv*'s ability to generate help messages. All you have to do is supply a help value for each option. Let's create a new parser and populate it with user-friendly (documented) options:

::

 $usage = 'usage: %prog [options] arg1 arg2';
 $parser = new Horde_Argv_Parser(array('usage' => $usage));
 $parser->addOption(
     '-v', '--verbose',
     array('action' => 'store_true', 'dest' => 'verbose', $default => 1,
           'help' => 'make lots of noise [default]')
 );
 $parser->addOption(
     '-q', '--quiet',
     array('action' => 'store_false', 'dest' => 'verbose', 
           'help' => 'be vewwy quiet (I'm hunting wabbits)')
 );
 $parser->addOption(
     '-f', '--filename',
     array('metavar' => 'FILE', 'help' => 'write output to FILE')
 );
 $parser->addOption(
     '-m', '--mode',
     array('default' => 'intermediate',
           'help' => 'interaction mode: one of "novice", "intermediate" [default], "expert"')
 );

If *Horde_Argv* encounters either '-h' or '--help' on the command-line, or if you just call ``$parser->printHelp()``, it prints the following to stdout:

::

 usage: <yourscript> [options] arg1 arg2
 
 options:
   -h, --help           show this help message and exit
   -v, --verbose        make lots of noise [default]
   -q, --quiet          be vewwy quiet (I'm hunting wabbits)
   -fFILE, --filename=FILE
                        write output to FILE
   -mMODE, --mode=MODE  interaction mode: one of 'novice', 'intermediate'
                        [default], 'expert'

There's a lot going on here to help *Horde_Argv* generate the best possible help message:

* the script defines its own usage message:

  ``$usage = 'usage: %prog [options] arg1 arg2';``
  *Horde_Argv* expands "%prog" in the usage string to the name of the current script, i.e. basename($_SERVER['argv'][0]). The expanded string is then printed before the detailed option help.
  If you don't supply a usage string, *Horde_Argv* uses a bland but sensible default: "usage: %prog [options]", which is fine if your script doesn't take any positional arguments.

* every option defines a help string, and doesn't worry about line-wrapping -- *Horde_Argv* takes care of wrapping lines and making the help output look good.
* options that take a value indicate this fact in their automatically-generated help message, e.g. for the "mode" option:

  ``-mMODE, --mode=MODE``
  Here, "MODE" is called the meta-variable: it stands for the argument that the user is expected to supply to -m/--mode. By default, *Horde_Argv* converts the destination variable name to uppercase and uses that for the meta-variable. Sometimes, that's not what you want -- for example, the --filename option explicitly sets $metavar = "FILE", resulting in this automatically-generated option description:
  ``-fFILE, --filename=FILE``
  This is important for more than just saving space, though: the manually written help text uses the meta-variable "FILE", to clue the user in that there's a connection between the formal syntax "-fFILE" and the informal semantic description "write output to FILE". This is a simple but effective way to make your help text a lot clearer and more useful for end users.

Print a version number
======================

Similar to the brief usage string, *Horde_Argv* can also print a version string for your program. You have to supply the string, as the version argument to ``Horde_Argv_Parser``:

::

 $parser = new Horde_Argv_Parser(array('usage' => '%prog [-f] [-q]', 'version' => '%prog 1.0'));

Note that "%prog" is expanded just like it is in usage. Apart from that, version can contain anything you like. When you supply it, *Horde_Argv* automatically adds a "--version" option to your parser. If it encounters this option on the command line, it expands your version string (by replacing "%prog"), prints it to stdout, and exits.

For example, if your script is called "/usr/bin/foo", a user might do:

::

 $ /usr/bin/foo --version
 foo 1.0

Error-handling
==============

The one thing you need to know for basic usage is how *Horde_Argv* behaves when it encounters an error on the command-line -- e.g. "-n4x" where the "-n" option takes an integer. *Horde_Argv* prints your usage message to stderr, followed by a useful and human-readable error message. Then it terminates with a non-zero exit status by calling ``exit()``.

If you don't like this, subclass ``Horde_Argv_Parser`` and override the ``parserError()`` method. See Extending *Horde_Argv*.

Putting it all together
=======================

Here's what a *Horde_Argv*-based scripts usually look like:

::

 require_once 'Horde/Autoloader/Default.php';
 
 [...]
 
 $usage = 'usage: %prog [options] arg';
 $parser = new Horde_Argv_Parser(array('usage' => $usage));
 $parser->addOption(
     '-f', '--file',
     array('type' => 'string', 'dest' => 'filename',
           'help' => 'read data from FILENAME')
 );
 $parser->addOption(
     '-v', '--verbose',
     array('action' => 'store_true', 'dest' => 'verbose')
 );
 $parser->addOption(
     '-q', '--quiet',
     array('action' => 'store_false', 'dest' => 'verbose')
 );
 [... more options ...]
 
 list($values, $args) = $parser->parseArgs();
 if (count($args) != 1) {
     $parser->parserError('incorrect number of arguments');
 }
 
 if ($values->verbose) {
     printf('reading %s...%n', $values->filename);
 }
 
 [... go to work ...]

