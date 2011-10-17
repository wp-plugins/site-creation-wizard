=== Site Creation Wizard ===
Contributors: yianniy,yitg
Tags: wpmu, admin, administration, buddypress, site creation, more privacy options, site template
Requires at least: 3.0
Tested up to: 3.2.1
Stable tag: 3.0

Allow users to create a site using predefined templates. Compatible with BuddyPress and More Privacy Options.

== Description ==

The Site Creation Wizard builds upon the site creation page available in WordPress multi-site when it is configured to allow users to create new sites.

Users are presented a list of site templates to chose from. Each template gives the user preconfigured content, theme, set of plugins and set of options. This helps users get started without having to know how to configure their sites.

Furthermore, users are given a set of features they can turn on. Each feature is essentially a set of related plugins that are preconfigured.

Users can get the site creation form by click on the '**Create New Site**' link under the Dashboard Menu. (this is added by the plugin.)

= Templates =

Templates are controlled by sites within your Multi-site environment. A template site should be configured exactly as you want new sites to be. When a new site is created, all content, options, turned on plugins and plugin options are copied from the template site to the new site.

= Features =

Features are also controlled by sites within your Multi-site environment. Feature sites should only have the plugins and options set that are related to the feature you wish to activate. When a new site is created, the plugins turned on in the feature site will be activated in the new site. Only those options which are not part of the template will be copied over.

= Use Policy =

It is possible to configure the site with Use Policy. The Use Policy is text at the end of the form that defines any policies users are expected to adhere to. It is possible to define a checkbox that needs to be clicked before the form can be submitted.

= User Registration Form =

The user registration form has been modified so that it no longer allows users to create a new blog as well as register. Once they have confirmed registration, they will be able to create a new site using the 'Create New Site' link in the Dashboard. This may be a bit less convenient but there was no good way to make the wizards features carry through the confirmation process.

= Compatibility =

**More Privacy Options**

The Site Creation Wizard checks to see if More Privacy Options is installed. If so, the form will display those privacy options instead of the standard ones.

**Buddy Press**

The Site Creation Wizard was originally built with BuddyPress in mind. It works just fine with BuddyPress's site creation page as well.

== Installation ==

1. Upload the `site-creation-wizard` folder to the `/wp-content/plugins/` directory
1. Network Activate the plugin through the 'Plugins' menu in WordPress

Use the Site Creation Wizard page in the Super Admin menu to create templates and features.

== Frequently Asked Questions ==

= What is the difference between a Template and a Feature =

Only one template can be chosen per new site. Each new site can choose multiple features. A new site copies nearly everything from the template site. It only copies turned on plugins from the the feature sites. (Technically, options that have not been set by the template site are also copied from the feature site.)

= How do I create a Template? =

You will first need to create a new site that will serve as the source of the template. Configure this site as necessary. Once the site is created, you will need to know its site ID. 

Use the Site Creation Wizard page in the Super Admin menu.

Click on '**Add New Site Type**' to create a new Template.

Fill in the form as requested and click on the appropriate '**Save Blog Type Options**' button.

= How do I create a Feature? =

You will first need to create a new site that will serve as the source of the feature. Configure this site as necessary. Once the site is created, you will need to know its site ID. 

Use the Site Creation Wizard page in the Super Admin menu.

Click on '**Add features option**' to create a new Feature.

Fill in the form as requested and click on the appropriate '**Save Features Options**' button.

= How do I configure a Use Policy? =

Use the Site Creation Wizard page in the Super Admin menu.

The '**Policy Text**' box defines the text that will appear at the end of the site creation form. It is intended to specify what your site's the policy for creating new sites is.

The '**Checkbox Text**' defines the text that appears in front of the checkbox, which needs to be checked in order to submit the form. If this text is blank, there will be no checkbox.

= What is different for Super Admins? =

1. Super Admins do not get the policy checkbox.
1. Super Admins are given the option to define the e-mail address or user id of the site's owner. This will make that user the new site's administrator instead of the Super Admin.

= Why can't Super Admins get this plugin's features in the '**Sites**' section of the Super Admin menu? =

Well, the too creation processes are completely different. If you want to use this plugin only for Super Admins, use the '**Limit blogs per user**' plugin and set the limit to -1. Then configure your WordPress Multi-Site environment to allow blog creation.
 
== Changelog ==

= 3.0 =
* Updates method used to copy information from template sites.
**Type template is completely copied with replacements for admin ids, urls, and paths.
**It also copies tables created by plugins. 
**Feature type options copied if they do not already exist and plugin generated tables are copied if they do not already exist.
*Fixed bug with policy text not displaying links correctly.

= 2.4.1 =
* Minor Buf fix.

=2.4= 
* Minor Fix - removed 'upload_path' from options that are copied from the template site.

= 2.3 =

* Fixed a few issues
** New site now sets admin e-mail, ownership of all posts and blogs to site owner
** sets page, post, etc. creation dates to the current date time.

= 2.2.1 =

* Fixed placement of 'Create New Site' Link within the Admin (and Network Admin) Menus.

= 2.2 =

* Fix for WP 3.1. The Administration page is now in the Network Admin page.

= 2.1.1 = 

* Fixes but with redirection to Signup Page. (wp-signup.php)

= 2.1 =

* Fixed bug that prevented new users from registering.

= 2.0 =
* Fixed problem with link to signup page (I hope).
* Updated User Interface
** Now can search for sites ID within the plugins inteface
** Site and Feature models are sortable.

= 1.0 =
* This is the first version.

= 3.0 =
* Updates method used to copy information from template sites.
**Type template is completely copied with replacements for admin ids, urls, and paths.
**It also copies tables created by plugins. 
**Feature type options copied if they do not already exist and plugin generated tables are copied if they do not already exist.
*Fixed bug with policy text not displaying links correctly.

= 2.4.1 =
* Minor bug Fix

= 2.4 = 
* Minor Fix - removed 'upload_path' from options that are copied from the template site.

= 2.3 =

* Fixed a few issues
** New site now sets admin e-mail, ownership of all posts and blogs to site owner
** sets page, post, etc. creation dates to the current date time.

= 2.2.1 =

* Fixed placement of 'Create New Site' Link within the Admin (and Network Admin) Menus.

= 2.2 =

* Makes plugin compatible with version 3.1. The plugin's administration page is now on the Network Admin page under Settings.

= 2.1.1 =
Fixes problem with redirection to Signup Page (wp-signup.php). This problem seems to only be found in sites installed in sub-directories.

= 2.1 = 
Fixes a problem that prevented the user registration form from working. Some minor UI tweaks.

= 2.0 =
Better User interface and fixed bug with link to signup form (I hope) for sub-directory installs.

= 1.0 =
This is the first version.

== Suggested Configuration ==

1. the **More Privacy Options** will allow you to make your Template and Feature site's invisible to the world.
1. the **Limit blogs per user** can limit how may blogs a user can create. Setting the limit to -1 will make it so that only Site Admin's can create new sites this way.
