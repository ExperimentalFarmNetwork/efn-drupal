This is the 8.x version of AJAX Comments.

Ajax Comments - http://drupal.org/project/ajax_comments

INSTALLATION
============

See Drupal module installation guidelines:
http://drupal.org/getting-started/install-contrib/modules


TROUBLESHOOTING
===============

1. If you have themed your comment output, make sure that everything is wrapped to the ".comment" class
in your "comment.tpl.php".

2. IMPORTANT: If you have the "Comment Notify" module installed, please also install
http://drupal.org/project/queue_mail to prevent server errors during comment submitting.

3. The module may conflict with Devel. It has been causing lags when a comment is submitting.

4. Try testing with Javascript Optimization disabled and see if it makes a
difference. (/admin/settings/performance)

5. If you are using the FCKEditor with the wysiwyg module, you should upgrade
to FCKEditor 2.x or higher. Anything less than 2.x will not work properly with
the module.

6. If you have having issues, first try the module with a clean Drupal install
with the default theme. As this is Javascript, it relies upon certain
assumptions in the theme files. For example, there have been reports of issues
where custom themes remove <h2> or <h3> tags which cause the module to become
inoperable.

7. If you are using a 3rd party editor such as Wysiwyg or CKeditor, try
disabling them and first troubleshooting with just the textareas. That will
help to narrow down any issues related to the editor.


HELP AND ASSISTANCE
===================

Help is available in the issue queue. If you are asking for help, please
provide the following information with your request:

- The comment settings for your node type
- The ajax comments settings
- The browsers you are testing with and version (Firefox 3.6.10, IE8, etc.)
- Any relevant information from the Firebug console or a similar Javascript debugger
- Screenshots or screencast videos showing your errors are helpful
- If you are using the default textareas or any 3rd party Javascript editors
	like CKeditor, etc.
- Any additional details that the module authors can use to reproduce this
	with a default installation of Drupal with the Garland theme.

---
Created by Alexandr Shvets, aka neochief
http://drupaldance.com/

Drupal 7.x version by acouch

Drupal 8.x version by formatC'vt, andypost and danmuzyka

