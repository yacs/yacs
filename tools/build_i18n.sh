#!/bin/sh

# build_module($1, $2)
#   $1  module name
#   $2  list of php files to scan
function build_module ()
{
  MODULE=$1
  # Make sure $@ contains only file list
  shift 1
  
  echo --- $MODULE module
  echo --- templates/$MODULE.pot generation
  if [ ! -d i18n/templates/ ]; then
    mkdir -p "i18n/templates/"
  fi
  xgettext $@ --output=i18n/templates/$MODULE.pot --default-domain=$MODULE --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

  if [ ! -f i18n/locale/en/$MODULE.po ]; then
  	echo --- locale/en/$MODULE.po generation
  	msginit --input=i18n/templates/$MODULE.pot --output=i18n/locale/en/$MODULE.po --locale=en --no-translator
  else
  	echo --- locale/en/$MODULE.po update
  	msgmerge i18n/locale/en/$MODULE.po i18n/templates/$MODULE.pot --update --backup=none
  	echo --- locale/en/$MODULE.mo generation
  fi
  msgfmt i18n/locale/en/$MODULE.po --output=i18n/locale/en/$MODULE.mo --statistics

  if [ ! -f i18n/locale/fr/$MODULE.po ]; then
  	echo --- locale/fr/$MODULE.po generation
  	msginit --input=i18n/templates/$MODULE.pot --output=i18n/locale/fr/$MODULE.po --locale=fr --no-translator
  else
  	echo --- locale/fr/$MODULE.po update
  	msgmerge i18n/locale/fr/$MODULE.po i18n/templates/$MODULE.pot --update --backup=none
  	echo --- locale/fr/$MODULE.mo generation
  fi
  msgfmt i18n/locale/fr/$MODULE.po --output=i18n/locale/fr/$MODULE.mo --statistics
}

# goes 1 directory up

cd ..

# Main program

echo --- Building all language files
while read module files; do 
  build_module $module $files
done < tools/srcfiles.txt 

# CWD back to origin value
cd "tools"