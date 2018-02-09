
Steps for creating a new release
--------------------------------

  1. Cleanup code
  2. Export configuration
  3. Review code
  4. Run tests
  5. Generate release notes
  6. Tag and create a new release


1. Cleanup code
---------------

[Convert to short array syntax](https://www.drupal.org/project/short_array_syntax)

    drush short-array-syntax yamlform

Tidy YAML files

    drush yamlform-tidy yamlform; 
    drush yamlform-tidy yamlform_ui; 
    drush yamlform-tidy yamlform_test;
    drush yamlform-tidy yamlform_test_translation;


2. Export configuration
-----------------------

    # Install all sub-modules.
    drush en -y yamlform yamlform_test yamlform_test_translation yamlform_examples yamlform_templates yamlform_node
    
    # Export form configuration from your site.
    drush features-export -y yamlform
    drush features-export -y yamlform_test
    drush features-export -y yamlform_test_translation
    drush features-export -y yamlform_examples
    drush features-export -y yamlform_templates
    
    # Tidy form configuration from your site.
    drush yamlform-tidy -y --dependencies yamlform
    drush yamlform-tidy -y --dependencies yamlform_test
    drush features-tidy -y --dependencies yamlform_test_translation
    drush yamlform-tidy -y --dependencies yamlform_examples
    drush yamlform-tidy -y --dependencies yamlform_templates
    
    # Reset certain files.
    cd modules/sandbox/yamlform
    git reset HEAD yamlform.info.yml
    git reset HEAD tests/modules/yamlform_test/yamlform_test.info.yml
    git reset HEAD tests/modules/yamlform_test/config/optional


3. Review code
--------------

[Online](http://pareview.sh)

    http://git.drupal.org/project/yamlform.git 8.x-1.x

[Commandline](https://www.drupal.org/node/1587138)

    # Make sure to remove the node_modules directory.
    rm -Rf node_modules

    # Check Drupal coding standards
    phpcs --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,css,info modules/sandbox/yamlform
    
    # Check Drupal best practices
    phpcs --standard=DrupalPractice --extensions=php,module,inc,install,test,profile,theme,js,css,info modules/sandbox/yamlform

[File Permissions](https://www.drupal.org/comment/reply/2690335#comment-form)

    # Files should be 644 or -rw-r--r--
    find * -type d -print0 | xargs -0 chmod 0755

    # Directories should be 755 or drwxr-xr-x
    find . -type f -print0 | xargs -0 chmod 0644


4. Run tests
------------

[SimpleTest](https://www.drupal.org/node/645286)

    # Run all tests
    php core/scripts/run-tests.sh --url http://localhost/d8_dev --module yamlform

[PHPUnit](https://www.drupal.org/node/2116263)

    # Execute all PHPUnit tests.
    cd core
    php ../vendor/phpunit/phpunit/phpunit --group YamlFormUnit

    # Execute individual PHPUnit tests.
    cd core
    export SIMPLETEST_DB=mysql://drupal_d8_dev:drupal.@dm1n@localhost/drupal_d8_dev;
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/yamlform/tests/src/Unit/YamlFormTidyTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/yamlform/tests/src/Unit/YamlFormHelperTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/yamlform/tests/src/Unit/YamlFormElementHelperTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/yamlform/tests/src/Unit/YamlFormOptionsHelperTest.php
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/yamlform/tests/src/Unit/YamlFormArrayHelperTest.php     
    php ../vendor/phpunit/phpunit/phpunit ../modules/sandbox/yamlform/src/Tests/YamlFormEntityElementsValidationUnitTest.php    


5. Generate release notes
-------------------------

[Git Release Notes for Drush](https://www.drupal.org/project/grn)

    drush release-notes --nouser 8.x-1.0-VERSION 8.x-1.x


6. Tag and create a new release
-------------------------------

[Tag a release](https://www.drupal.org/node/1066342)

    git tag 8.x-1.0-VERSION
    git push --tags
    git push origin tag 8.x-1.0-VERSION

[Create new release](https://www.drupal.org/node/add/project-release/2640714)
