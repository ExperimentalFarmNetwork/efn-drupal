Below is the current roadmap for the YAML Form module.

★ Indicates areas that I need help with. 

Phase I (before Release Candidate)
----------------------------------

### Required Features & Tasks

**General**

- Finalize default configuration  
- Finalize supported elements

**Multilingual** 

- [#2805113](https://www.drupal.org/node/2805113) 
  Rework translation handling

**Submission Download**

- [#2822560](https://www.drupal.org/node/2822560) 
  Excel exporter

**Conditional Logic**

- Server side conditional logic 

**Views**

- [#2769977](https://www.drupal.org/node/2769977) 
  Views integration ★

**Templating ★**

- [#2787117](https://www.drupal.org/node/2787117)
  Add more starter templates ★
  
**UI/UX**

- [#2822613](https://www.drupal.org/node/2822613) 
  Use CKEditor (instead of CodeMirror) for all settings that can contain HTML markup
- Review element edit form UI/UX

**Third party libraries**

- Improve CKEditor integration
- Add external libraries to composer.json ★

### Optional Features

**Elements**

- [#2783937](https://www.drupal.org/node/2783937)
  Add OptGroup support to options element

- Upload files from third-party services such as Dropbox, Box, OneDrive, Google 
  Drive and Instagram
  See: [File Chooser Field](https://www.drupal.org/project/file_chooser_field)

**Email handler**

- [#2817901](https://www.drupal.org/node/2817901) 
  Form select component email handler mapping

**Handlers**

- Provide Google Sheets integration

### Documentation & Help 

**General**

- Move all documentation to Drupal.org

**Screencasts**

- Decide if inline screencasts are useful and update screencasts

**Module**

- Review hardcoded messages

**Editorial ★**

- Unified tone
- General typos, grammar, and wording ★


Phase II (after Stable Release)
-------------------------------

### Translations

- [#2788741](https://www.drupal.org/node/2788741)
  French
  
  
### Optional Features


**UI/UX**
 
- [#2771235](https://www.drupal.org/node/2771235)
  Implement drag-n-drop UI/UX ★ 

**Field API**

- [#2792583](https://www.drupal.org/node/2792583) 
  Use Field API ★ 

**Forms**

- [#2757491](https://www.drupal.org/node/2757491) 
  AJAX support for forms ★ 

- [#2824714](https://www.drupal.org/node/2824714)
  Composite and/or base forms

**Rules/Actions**

- [#2779461](https://www.drupal.org/node/2779461) 
  Rules/Action integration ★

**Results**

- Create trash bin for deleted results
  _Copy D8 core's solution_ 

**APIs** 

- REST API endpoint for CRUD operations
- Headless Drupal Forms


Ongoing Tasks
-------------

**Accessibility**

- Establish accessibility policy and review process.
 
**Communication**

- Set up weekly, biweekly, or monthly online hangout.

**Workflow**

- Define development workflow
- Define release policy and schedule
- Document issue guidelines
 
**Code Review**

- Cleanup bad smelling code.
- Testability
- Refactorability
- Plugin definitions ★
- Entity API implementation ★
- Form API implementation ★

**Testing**

- Refactor PHPUnit tests
- Improve SimpleTest setUp performance
