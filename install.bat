@echo off
rem Setup a clean site in docroot/
cd docroot/
drush site-install -y

rem Save configuration to database for later usage
drush php-script ../scripts/drupal_pre_install.php

drush init
drush build

drush php-script ../scripts/drupal_post_install.php

rem Enable required blocks
echo "Enable required blocks ..."
drush block-configure language --module=locale --region=header

echo "Registering migrations ..."
drush migrate-auto-register

IF NOT %1=="--migrate" GOTO DONE

:MIGRATE
    echo "Importing Activity taxonomy"
    drush migrate-import TaxonomyActivity

    echo "Importing NACE codes taxonomy"
    drush migrate-import TaxonomyNaceCodes

    echo "Importing ESENER taxonomy"
    drush migrate-import TaxonomyEsener

    echo "Importing Publication types taxonomy"
    drush migrate-import TaxonomyPublicationTypes

    echo "Importing multilingual Thesaurus taxonomy"
    drush migrate-import TaxonomyThesaurus

    echo "Importing Tags taxonomy"
    drush migrate-import TaxonomyTags

    echo "Importing News content"
    drush migrate-import News

    echo "Importing Publications content"
    drush migrate-import Publication

    echo "Importing Articles content"
    drush migrate-import Article

    echo "Importing Blog content"
    drush migrate-import Blog

    echo "Importing Case Study content"
    drush migrate-import CaseStudy

:DONE

drush cc all