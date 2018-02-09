YAML Form to Webform
--------------------

### About this Module

The YAML Form 8.x-1.x to Webform 8.x-5.x module provides Drush commands and 
tools to rename the YAML Form module and migrate all YAML Form configuration and 
submission data to the Webform module. 
[Read more](https://www.drupal.org/node/2827845)

### About the Migration

Both the `drush yamlform-to-webform-convert` and 
`drush yamlform-to-webform-migrate` commands are basically doing global 
search-n-replaces on Drupal's database and file system. 

> This is the fastest and most absolute way to completely migrate your site 
from YAML Form 8.x-1.x to Webform 8.x-5.x, but it is also a little risky.

The biggest risk and challenge with using a global search-n-replace is to not  
accidentally rename and break something unexpectedly, which is why any custom or 
contrib modules (except the YAML Form module) that references the words 
'YAML Form'  or 'yamlform' must be completely uninstalled.  For example, any 
custom link or shortcut containing the words 'YAML Form' will be changed to 
'Webform'.

### Prerequisites

 
- Only MySQL is support. 
    - Patches adding support other databases (PostgreSQL, SQLite, etc...) are 
      welcomed.  
- All custom and/or third-party related YAML Form modules must be disabled.
- YAML Form 8.x-1.x module must be enabled.
    - _YAML Form module will be disabled during the migration._
- Webform 8.x-5.x module must be disabled.
    - _Webform module will be enabled during the migration._

### Important!!!

> Please make sure to test and [backup your site](https://www.drupal.org/docs/7/backing-up-and-migrating-a-site).

### Testing

> It is strongly recommended that you use [Drush](http://www.drush.org/en/master/) 
to perform the YAML Form to Webform migration. Running updates via Drush 
(aka the command-line ) is less likely to run into script timeouts and 
memory limit issues.

#### Via the Command Line using Drush.

> Replace `/var/www/sites/d8_yamlform` with your site's docroot.

drush```bash
# Change directory to your site's root.
cd /var/www/sites/d8_yamlform

# Enable YAML Form to Webform module.
drush pm-enable yamlform_to_webform

# Download Webform 8.x-5.x module (Once it is available)
drush pm-download webform-8.x-5.x

# Make a _backup directory.
mkdir ../_backup

# Save archive to _backup directory.
drush archive-dump default --overwrite --destination=../_backup/d8_yamlform.tar

# Migrate YAML Form 8.x-1.x to Webform 8.x-5.x.
drush yamlform-to-webform-migrate -y

# Review migration.
open http://localhost/admin/structure/webform

# OPTIONAL: Restore archive from _backup directory.
drush archive-restore --overwrite --destination /var/www/sites/d8_yamlform  ../_backup/d8_yamlform.tar
```

#### Via the User Interface

- Download the [Backup and Migrate](https://www.drupal.org/project/backup_migrate) module.
- Go to Extend (/admin/modules)
    - Install the 'Backup and Migrate' module. 
    - Install the 'YAML Form to Webform' module. 
- Backup database and file. (/admin/config/development/backup_migrate)
- Migrate YAML Form 8.x-1.x to Webform 8.x-5.x. (/admin/structure/yamlform/migrate)
- Review migration
    - /admin/structure/webform
    - /admin/structure/webform/manage/contact/results/submissions
    - etc...
- OPTIONAL: Restore database and file. (/admin/config/development/backup_migrate/restore)
