Replace Quick Mail Sender
====================

Replace user sender name, email address, reply to address in Quick Mail plugin.

Description
-----------

![screenshot](screenshot.png)

Implements filter for current user to replace their sending name, email address, reply to address.

[Quick Mail plugin](https://github.com/mitchelldmiller/quick-mail-wp-plugin) 3.5.0 or later.

__Features__

* Includes `replace_quick_mail_sender` filter, and form to change sending name and addresses with it. 

### Learn More

* See [Send Reliable Email from WordPress with Quick Mail](https://wheredidmybraingo.com/send-reliable-email-wordpress-quick-mail/#replace_sender).

### Installation ###

* Download [the latest release](https://github.com/mitchelldmiller/replace-quick-mail-sender/releases/latest) and unpack in your `/wp-content/plugins/` directory.

* Verify that Quick Mail plugin is activated.

* Activate the plugin through the WordPress 'Plugins' menu.

### Frequently Asked Questions ###

__Who can use this plugin?__

* User must be able to publish posts. Site must have Quick Mail plugin active.

__How to Disable Filter__

* Delete name or email, to temporarily disable filter.

__Saves Credentials__

* Does not delete user info when plugin is uninstalled.

* Delete plugin from Dashboard to delete user options.

__Limitations__

* Check `From` name in Quick Mail, because other mail plugins can override sender credentials.

* Replacement name must be 1-80 characters.

* Replacement email address is verified if `Verify recipient email domains` is enabled.

__Translators and Programmers__

* A .pot file is included for translators.

__License__

This plugin is free for personal or commercial use. 
