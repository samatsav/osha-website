@echo off

rem Setup a clean site in docroot/
cd docroot/

drush build

echo "Registering migrations ..."
drush migrate-auto-register

IF NOT %1=="--migrate" GOTO DONE

:MIGRATE

	echo "Importing Activity taxonomy"
	drush migrate-import TaxonomyActivity

	echo "Importing NACE codes taxonomy"
	drush migrate-import --update TaxonomyNaceCodes

	echo "Importing ESENER taxonomy"
	drush migrate-import --update TaxonomyEsener

	echo "Importing Publication types taxonomy"
	drush migrate-import --update TaxonomyPublicationTypes

	echo "Importing multilingual Thesaurus taxonomy"
	drush migrate-import --update TaxonomyThesaurus

	echo "Importing Tags taxonomy"
	drush migrate-import --update TaxonomyTags

	echo "Importing News content"
	drush migrate-import --update News

	echo "Importing Publications content"
	drush migrate-import --update Publication

	echo "Importing Articles content"
	drush migrate-import --update Article

	echo "Importing Blog content"
	drush migrate-import --update Blog

	echo "Importing Case Study content"
	drush migrate-import --update CaseStudy

fi

:DONE
drush cc all