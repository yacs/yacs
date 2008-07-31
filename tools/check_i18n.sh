#!/bin/sh

# update_module($1, $2)
#   $1  module name
#   $2  code
function update_module ()
{
  MODULE=$1
  CODE=$2

  echo --- $MODULE module
  echo --- locale/$CODE/$MODULE.po update
  msgmerge i18n/locale/$CODE/$MODULE.po i18n/templates/$MODULE.pot --update --backup=none
  echo --- locale/$CODE/$MODULE.mo generation
  msgfmt i18n/locale/$CODE/$MODULE.po --output=i18n/locale/$CODE/$MODULE.mo --statistics
}

# goes 1 directory up
olddir=`pwd`
BASEDIR=${0%/*}
if test "$BASEDIR" = "$0" ; then
    BASEDIR="$(which $0)"
    BASEDIR=${BASEDIR%/*}
fi
cd "$BASEDIR"/../

# main program
code="en"
echo --- This script will generate .po_s files for language $CODE
while read module files; do
  update_module $module $code
done < $olddir/srcfiles.txt

# CWD back to origin value
cd "$olddir"