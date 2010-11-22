#!/bin/sh
#
# Script to compare revisions and ignore files that should be different, and
# differences in revision numbers.
#
# Syntax: ./horde-rev-cmp.sh FIRST_FOLDER SECOND_FOLDER ['ADDITIONAL PARAMETERS']

FIRST=$1
SECOND=$2
shift 2
diff -r -I "\$Id" --exclude CHANGES --exclude CREDITS --exclude '*.po' --exclude '*.pot' --exclude locale --exclude '.#*' --exclude '*~' --exclude '*.bak' --exclude '*.orig' --exclude '*.rej' "$@" $FIRST $SECOND | grep -v "config/.*\.php "
