===================
 Horde_Cli_Modular
===================

Command line interfaces can often be composed of different modules responsible for distinct actions. ``Horde_Cli_Modular`` allows each such module to influence the overall command line interface.

--------------
 Introduction
--------------

The command line of the tool **``pear``** is a good example of the kind of interface ``Horde_Cli_Modular`` supports: There are a number of global options available but each command supported by **``pear``** may have its own subset of options. **``-c``** identifies the **``pear``** configuration file as a global options. In contrast the **``--register-only``** option is an option specific to the **``install``** command **``pear``** provides.

Obviously not all command line interfaces match this scheme and hence ``Horde_Cli_Modular`` shouldn't be considered to be a generic utility for all CLI tools. But for all CLI helpers that have such a structure the library should provide a decent skeleton that allows to quickly start adding modules.

-----------
 Structure
-----------

``Horde_Cli_Modular`` provides four classes as main structural elements:

:``Horde_Cli_Modular``:               The entry point for generating the command
                                      line interface.
:``Horde_Cli_Modular_Modules``:       The handler for the list of modules.
:``Horde_Cli_Modular_ModuleProvide``: A module factory.
:``Horde_Cli_Modular_Module``:        The interface definition for a module.

-------------------
 Horde_Cli_Modular
-------------------

This class is entry point for constructing a modular command line interface. The class provides methods to combine and access the different modules.

Setup
=====

This class generates the basic setup and you will only have to provide a few basic parameters to setup the system. This introduction will only focus on the central settings and you will need to lookup the API documentation for further details.

The following demonstrates how you could construct the ``Horde_Cli_Modular`` instance:

::

 $modular = new Horde_Cli_Modular(
   array(
     'parser' => array('usage' => '[options] MODULE'),
     'modules' => array('directory' => __DIR__ . '/Module'),
     'provider' => array('prefix' => 'Horde_Something_Module_')
   )
 );

The **``parser``** parameter preparse the command line argument parser (usually ``Horde_Argv``. The snippet above only provides the **``usage``** setting that will be displayed as the condensed usage instruction when the user requests the help for the command.

The **``modules``** part defines the **``directory``** that contains the various modules that form the command line interface.

And finally the **``provider``** setting indicates the common **``prefix``** that the module classes will have.

The file structure for the hypothetical ``Horde_Something`` package would look like this in order to match the setup presented above:

::

 lib/
   Horde/
     Something.php
     Something/
       Module/
         One.php
         Two.php        

There are two modules: ``Horde_Something_Module_One`` in ``One.php`` and ``Horde_Something_Module_Two`` in ``Two.php``.

Usage
=====

After creating the ``Horde_Cli_Modular`` instance you should create the command line parser and read the arguments the user provided.

::

 $parser = $modular->createParser();
 list($options, $arguments) = $parser->parseArgs();

This is the main action you will need.

Beyond that ``Horde_Cli_Modular`` will allow you to retrieve the list of modules with:

::

 $modules = $modular->getModules();

And you can retrieve a module instance using:

::

 $module = $modular->getProvider()->getModule('one');

-----------------
 Writing modules
-----------------

First and foremost each module can add its own option group with a specific title, description and a set of module specific options (see Horde_Argv_OptionGroup for exact details). In addition ``Horde_Cli_Modular`` allows a module to modify the global usage string and add basic options valid for all modules.

For that to work all modules are required to implement the ``Horde_Cli_Modular_Module`` interface:

::

 interface Horde_Cli_Modular_Module
 {
     public function getUsage();
     public function getBaseOptions();
     public function hasOptionGroup();
     public function getOptionGroupTitle();
     public function getOptionGroupDescription();
     public function getOptionGroupOptions();
 }

There are two function that allow to influence the global setup of the command line parser: ``getUsage()`` and ``getBaseOptions()``. The first one returns a string that will be added to the generic usage description displayed when the user requests the help screen. The second one returns an array of ``Horde_Argv_Option`` instances that each define an option valid for all modules.

If the module defines its own option group (that will be displayed as a separate section in the help text for the command) ``hasOptionGroup()`` needs to return **``true``**.

An option group needs a title (returned as a string by ``getOptionGroupTitle()``) and a descriptions (returned as a string by ``getOptionGroupDescription()``). And finally an option group needs a list of options valid for the specific module defining the option group. The corresponding list is returned as an array of ``Horde_Argv_Option`` instances by the method ``getOptionGroupOptions()``.

----------
 Examples
----------

Currently the package ``Horde_Kolab_Cli`` and the ``components`` application both use ``Horde_Cli_Modular``. You are referred to these package in order to look at real world examples using the ``Horde_Cli_Modular`` library.

