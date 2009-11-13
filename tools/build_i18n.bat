@echo off
echo --- Building all language files
cd ..

rem ----------------------------------------------------------------------------

SET MODULE=root
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  *.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_root_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_root_en
:update_root_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_root_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_root_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_root_fr
:update_root_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_root_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=actions
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_actions_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_actions_en
:update_actions_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_actions_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_actions_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_actions_fr
:update_actions_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_actions_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=agents
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_agents_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_agents_en
:update_agents_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_agents_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_agents_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_agents_fr
:update_agents_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_agents_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=articles
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_articles_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_articles_en
:update_articles_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_articles_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_articles_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_articles_fr
:update_articles_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_articles_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=behaviors
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php %MODULE%/agreements/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_behaviors_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_behaviors_en
:update_behaviors_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_behaviors_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_behaviors_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_behaviors_fr
:update_behaviors_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_behaviors_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=categories
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_categories_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_categories_en
:update_categories_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_categories_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_categories_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_categories_fr
:update_categories_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_categories_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=codes
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_codes_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_codes_en
:update_codes_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_codes_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_codes_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_codes_fr
:update_codes_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_codes_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=collections
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_collections_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_collections_en
:update_collections_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_collections_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_collections_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_collections_fr
:update_collections_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_collections_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=comments
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_comments_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_comments_en
:update_comments_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_comments_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_comments_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_comments_fr
:update_comments_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_comments_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=control
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php %MODULE%/htaccess/*.php %MODULE%/htaccess/basic/*.php %MODULE%/htaccess/options/*.php %MODULE%/htaccess/indexes/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_control_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_control_en
:update_control_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_control_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_control_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_control_fr
:update_control_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_control_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=dates
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_dates_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_dates_en
:update_dates_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_dates_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_dates_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_dates_fr
:update_dates_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_dates_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=decisions
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_decisions_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_decisions_en
:update_decisions_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_decisions_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_decisions_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_decisions_fr
:update_decisions_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_decisions_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=feeds
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php %MODULE%/flash/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_feeds_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_feeds_en
:update_feeds_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_feeds_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_feeds_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_feeds_fr
:update_feeds_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_feeds_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=files
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_files_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_files_en
:update_files_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_files_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_files_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_files_fr
:update_files_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_files_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=forms
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_forms_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_forms_en
:update_forms_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_forms_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_forms_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_forms_fr
:update_forms_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_forms_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=help
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_help_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_help_en
:update_help_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_help_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_help_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_help_fr
:update_help_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_help_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=i18n
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_i18n_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_i18n_en
:update_i18n_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_i18n_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_i18n_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_i18n_fr
:update_i18n_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_i18n_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=images
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_images_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_images_en
:update_images_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_images_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_images_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_images_fr
:update_images_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_images_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=letters
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_letters_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_letters_en
:update_letters_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_letters_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_letters_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_letters_fr
:update_letters_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_letters_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=links
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_links_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_links_en
:update_links_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_links_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_links_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_links_fr
:update_links_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_links_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=locations
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_locations_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_locations_en
:update_locations_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_locations_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_locations_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_locations_fr
:update_locations_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_locations_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=overlays
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php %MODULE%/forms/*.php %MODULE%/mutables/*.php %MODULE%/polls/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_overlays_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_overlays_en
:update_overlays_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_overlays_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_overlays_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_overlays_fr
:update_overlays_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_overlays_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=scripts
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_scripts_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_scripts_en
:update_scripts_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_scripts_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_scripts_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_scripts_fr
:update_scripts_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_scripts_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=sections
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_sections_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_sections_en
:update_sections_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_sections_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_sections_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_sections_fr
:update_sections_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_sections_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=servers
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_servers_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_servers_en
:update_servers_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_servers_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_servers_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_servers_fr
:update_servers_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_servers_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=services
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_services_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_services_en
:update_services_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_services_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_services_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_services_fr
:update_services_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_services_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=shared
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_shared_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_shared_en
:update_shared_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_shared_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_shared_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_shared_fr
:update_shared_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_shared_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=skins
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php %MODULE%/_reference/avatars/*.php %MODULE%/boxesandarrows/*.php %MODULE%/digital/*.php %MODULE%/flexible/*.php %MODULE%/joi/*.php %MODULE%/skeleton/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_skins_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_skins_en
:update_skins_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_skins_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_skins_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_skins_fr
:update_skins_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_skins_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=smileys
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_smileys_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_smileys_en
:update_smileys_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_smileys_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_smileys_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_smileys_fr
:update_smileys_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_smileys_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=tools
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_tools_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_tools_en
:update_tools_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_tools_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_tools_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_tools_fr
:update_tools_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_tools_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=tables
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_tables_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_tables_en
:update_tables_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_tables_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_tables_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_tables_fr
:update_tables_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_tables_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=users
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php %MODULE%/authenticators/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_users_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_users_en
:update_users_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_users_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_users_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_users_fr
:update_users_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_users_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

SET MODULE=versions
echo --- %MODULE% module
echo --- templates/%MODULE%.pot generation
xgettext  %MODULE%/*.php --output=i18n/templates/%MODULE%.pot --default-domain=%MODULE% --keyword=c --keyword=nc:1,2 --keyword=s --keyword=ns:1,2 --language=php

if exist i18n/locale/en/%MODULE%.po goto update_versions_en
echo --- locale/en/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/en/%MODULE%.po --locale=en --no-translator
goto compile_versions_en
:update_versions_en
echo --- locale/en/%MODULE%.po update
msgmerge i18n/locale/en/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/en/%MODULE%.mo generation
:compile_versions_en
msgfmt i18n/locale/en/%MODULE%.po --output=i18n/locale/en/%MODULE%.mo --statistics

if exist i18n/locale/fr/%MODULE%.po goto update_versions_fr
echo --- locale/fr/%MODULE%.po generation
msginit --input=i18n/templates/%MODULE%.pot --output=i18n/locale/fr/%MODULE%.po --locale=fr --no-translator
goto compile_versions_fr
:update_versions_fr
echo --- locale/fr/%MODULE%.po update
msgmerge i18n/locale/fr/%MODULE%.po i18n/templates/%MODULE%.pot --update --backup=none
echo --- locale/fr/%MODULE%.mo generation
:compile_versions_fr
msgfmt i18n/locale/fr/%MODULE%.po --output=i18n/locale/fr/%MODULE%.mo --statistics

rem ----------------------------------------------------------------------------

echo --- Done.
cd tools