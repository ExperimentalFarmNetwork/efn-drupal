YAML Form
---------

> Please be aware that the YAML Form module is moving to the Webform module and 
that a migration path will be provided. 
[Learn more](https://www.drupal.org/node/2805097)

### About this Module

The YAML Form module is a form builder and submission manager for Drupal 8.

The primary use case for this module is to:

- **Build** a new form or duplicate an existing template
- **Publish** the form as a page, node, or block
- **Collect** submissions
- **Send** confirmations and notifications
- **Review** submissions online
- **Download** submissions as a CSV


### Demo

<div style="position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden;">
  <iframe width="560" height="315" src="https://www.youtube.com/embed/9jSOOEpzAy8" frameborder="0" allowfullscreen style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;"></iframe>
</div> 
<br/>

> Evaluate this project online using [simplytest.me](https://simplytest.me/project/drupal/8.2.x?add[]=yamlform).

> [Watch a demo](http://youtu.be/9jSOOEpzAy8) of the YAML Form module.


### Goals

- A comprehensive form and survey building solution for Drupal 8. 
- A stable, maintainable, and tested API for building forms and handling submission.
- A pluggable/extensible API for custom form elements and submission handling. 
 

### Documentation

The YAML Form module's documentation is still under development. The YAML Form 
module has a dedicated website that provides 
a [features summary](http://yamlform.io/features/), 
[installation instructions](http://yamlform.io/support/installation/),
[FAQs](http://yamlform.io/support/faq/),
and [more...](http://yamlform.io/yamlform/).

### Blog Posts & Articles

- [Getting NYU onto YAML Form](https://www.fourkitchens.com/blog/article/getting-nyu-yaml-form)
- [YAML Forms for Drupal 8](https://www.gaiaresources.com.au/yaml-forms-drupal-8/)
- [Creating YAML Form Handlers in Drupal 8](http://fivemilemedia.co.uk/blog/creating-yaml-form-handlers-drupal-8)

### Releases

Even though the YAML Form module is still under active development with
regular [beta releases](https://www.drupal.org/documentation/version-info/alpha-beta-rc),
all existing configuration and submission data will be maintained and updated 
between releases.  **APIs can and will be changing** while this module moves 
from beta releases to a final release candidate. 

Simply put, if you install and use the YAML Form module out of the box AS-IS, 
you _should_ be okay.  Once you start extending forms with plugins, altering 
hooks, and overriding templates, you will need to read each release's 
notes and assume that _things will be changing_.


### Similar Modules

- **[Comparison of Form Building Modules](https://www.drupal.org/node/2083353)**  
  Drupal has a lot of modules aimed at helping site builders and users add forms 
  to their sites. The [Comparison of Form Building Modules](https://www.drupal.org/node/2083353) 
  page includes rough comparisons of three of them for Drupal 8 and five of them
  for Drupal 7. 

---

- **[Contact](https://www.drupal.org/documentation/modules/contact) + 
  [Contact Storage](https://www.drupal.org/project/contact_storage)**    
  The Contact module allows site visitors to send emails to other authenticated 
  users and to the site administrator. The Contact Storage module provides 
  storage for Contact messages which are fully-fledged entities in Drupal 8.
  Many of its features are likely to be moved into Drupal Core.

- **[Eform](https://www.drupal.org/project/eform)**  
  The EForm module enables you to create front-end forms (fieldable entities), 
  which contain fields that you define! These forms use the standard Drupal 
  fields.  
  [Is this module still needed?](https://www.drupal.org/node/2809179)

- **[Webform](https://www.drupal.org/project/webform)**  
  The Webform module provides forms and surveys for Drupal 7.  
  [The Webform module has not been ported to Drupal 8](https://www.drupal.org/node/2574683), 
  please checkout the [YAML Form Migrate](https://www.drupal.org/sandbox/dippers/2819169)
  module if you need to migrate from the Webform module (D6/D7) to the 
  YAML Form module (D8). 
