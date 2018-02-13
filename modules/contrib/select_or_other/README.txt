CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Applications
 * Usage example
   - Field widget
   - Forms API element
 * Maintainers


INTRODUCTION
------------

Provides a new Forms API element which is a select/radios/checkboxes element
that has an 'other' option. When 'other' is selected a textfield appears for
the user to provide a custom value.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/select_or_other

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/select_or_other


INSTALLATION
------------
 * Install as you would normally install a contributed Drupal module. Visit:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


APPLICATIONS
------------
 * As a Field widget for (auto create) Entity reference fields.

 * As a Forms API element for developers. Therefor can be integrated into any
   form or module.


USAGE EXAMPLE
-------------
Field widget
============
This is an example to use the Field widget. First make sure you have a Taxonomy
vocabulary.

1. Go to Structure and add a new Content type.

2. Add a field with field type Taxonomy term.

3. Edit this field and select "Create referenced entities if they don't already
   exist" and save.

4. Go to Manage form display and select "Select or Other" in the Widget column.

You can find the "Other" option by adding new content.


Forms API element
=================
For the developers, this example is about the Forms API element. Start with
the custom module tutorial at:
https://www.drupal.org/docs/8/creating-custom-modules/add-a-form-to-the-block-configuration

Then go to /hello_world/src/Plugin/Block/HelloBlock.php and place the following
before "return $form;":

    $form['hello_block_settings'] = array(
        '#type' => 'select_or_other_select',
        '#title' => t('Options'),
        '#options' => array(
            'value_1' => t('One'),
            'value_2' => t('Two'),
        ),
        '#multiple' => TRUE,
        '#other_unknown_defaults' => 'other',
        '#other_delimiter' => ', ',
    );

The menu should now appear in the block settings. For more information visit:
https://www.drupal.org/node/1158654



MAINTAINERS
-----------
Maintainer: Chris Jansen (https://www.drupal.org/u/legolasbo)
