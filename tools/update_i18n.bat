@echo off
SET CODE=fr
echo --- This script will update .po and .mo files for language %CODE%
pause

cd ..

rem ----------------------------------------------------------------------------

SET MODULE=root
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=actions
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=agents
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=articles
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=behaviors
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=categories
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=codes
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=collections
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=comments
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=control
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=dates
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=decisions
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=feeds
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=files
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=forms
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=help
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=i18n
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=images
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=letters
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=links
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=locations
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=overlays
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=scripts
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=sections
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=servers
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=services
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=shared
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=skins
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=smileys
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=tools
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=tables
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=users
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=versions
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/%CODE%/%MODULE%.mo generation
msgfmt i18n/locale/%CODE%/%MODULE%.po --output=i18n/locale/%CODE%/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

echo --- Done.
cd tools