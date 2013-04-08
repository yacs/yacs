@echo off
SET CODE=en
echo --- This script will generate .po_s files for language %CODE%
pause

cd ..

rem ----------------------------------------------------------------------------

SET MODULE=root
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=agents
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=articles
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=behaviors
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=categories
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=codes
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=comments
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=control
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=dates
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=feeds
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=files
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=help
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=i18n
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=images
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=letters
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=links
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=locations
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=overlays
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=scripts
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=sections
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=servers
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=services
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=shared
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=skins
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=smileys
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=tools
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=tables
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=users
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

SET MODULE=versions
echo --- %MODULE% module
echo --- locale/%CODE%/%MODULE%.po update
msgmerge i18n/locale/%CODE%/%MODULE%.po i18n/templates/%MODULE%.pot --output-file=i18n/locale/%CODE%/%MODULE%.po_s --sort-output

rem ----------------------------------------------------------------------------

echo --- Done.
cd tools