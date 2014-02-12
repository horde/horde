# Horde xxhash Extension for PHP #

This extension allows for hashing via the xxHash algorithm.

Documentation for xxHash can be found at [» http://code.google.com/p/xxhash/](http://code.google.com/p/xxhash/).

## Configration ##

php.ini:

    extension=horde_xxhash.so

## Function ##

* horde\_xxhash — xxHash computation

### horde\_xxhash — xxHash computation ###

#### Description ####

string **horde\_xxhash** (string _$data_)

xxHash computation.

#### Pameters ####

* _data_

  The string to hash.

#### Return Values ####

Returns the 32-bit hash value (in hexidecimal), or FALSE if an error occurred.


## Examples ##

    $hash = horde_xxhash('test');
