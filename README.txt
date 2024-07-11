=== LogDash Activity Log ===
Contributors: deryck
Donate link: https://www.paypal.com/donate/?hosted_button_id=XHK37YBVVMP58
Tags: Activity Log, User Activity, User Log, Audit Log
Requires at least: 5.9.5
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The ultimate solution for tracking activities and security issues on your WordPress site.

== Description ==

LogDash Activity Log is the ultimate solution for tracking activities on your WordPress site. With its comprehensive features and intuitive interface, managing your website's activity log has never been easier.

Designed with simplicity in mind, LogDash Activity Log allows you to effortlessly monitor and track all actions on your WordPress site, including user logins, content changes, plugin updates, and more. Its user-friendly dashboard gives you instant access to critical information, making it easy to identify and resolve issues quickly.

Whether you're managing a personal blog or a large corporate website, LogDash Activity Log Plugin is the perfect tool for enhancing your site's security.

Log everything that happens on you WordPress website to:

* __Gain full visibility:__ Stay informed about everything happening on your WordPress site, from user logins and content updates to plugin installations and more.
* __Strengthen site security:__ Easily spot suspicious behavior before it becomes a security threat with real-time alerts and customizable notifications, providing an added layer of protection for your site.
* __Boost user productivity:__ Monitor user activity and identify ways to optimize your site and workflow, leading to increased productivity.
* __Enhance user accountability:__ Keep your users accountable with detailed logs of their actions on your site. This helps to promote responsible behavior and reduce errors.
* __Simplify troubleshooting:__ Makes it easy to pinpoint the source of errors or issues on your site, enabling you to troubleshoot more efficiently and effectively.
* __Streamline site management:__ Make use of shortcuts to gain quick access to modified content, simplifying site administration and reducing complexity.

LogDash Activity Log is FREE. You can keep your log events for as long as you need - there's no restriction on the duration of your logs.

**Here's an overview of the modifications that the plugin is capable of tracking and storing:**

* __Core Updates:__ such as upgrades, downgrades and re-downloads.
* __Themes:__ such as downloads, installations, upgrades, activations, theme switch and deletions.
* __Plugins:__ such as downloads, installations, upgrades, activations, theme switch and deletions.
* __Files:__ such as uploads or every files, including plugins and themes and updates in WordPress theme or plugin editors.
* __Attachments:__ Uploads and updates for every attachment.
* __Posts, Pages and Custom Posts:__ such as title, content (with quick link to rollback revisions), status, taxonomies and many more.
* __Settings:__ such as the Blog Title, Date format and every setting in WordPress.
* __Categories, Tags and Custom Taxonomies:__ such as creating, removing, updating and adding values to posts, pages and custom posts.
* __Users profile changes:__ such as name, email, role changes (including support for multiple roles) and every profile related data.
* __User activity:__ such as failed logins, login, logout and terminating other user sessions.

__Note:__ LogDash also support WooCommerce, ACF, LogDash as well a every Custom Post Type. Support will be improved and extended in a Premium version in the future.

== Installation ==

1. Upload `logdash` to the `/wp-content/plugins/` directory or install directly through the plugin installer.
2. Activate the plugin through the 'Plugins' menu in WordPress or by using the link provided by the plugin installer.

== Frequently Asked Questions ==

= Will LogDash create, update or delete any data but the events? =

Apart from the record and settings created in the options table, no other data will be created, updated, deleted or manipulated.

= Will LogDash have access to sensitive data? =

No se almacenarán datos a los que su sitio web WordPress no tiene acceso de antemano.

= Will LogDash store user password when are modified? =

Definitely not. Only the modification event will be stored.

= Will LogDash have an impact in the website performance? =

LogDash has been developed with performance in mind. However, it should be considered that the plugin adds some extra queries when storing data. This will not affect the performance of the website in most cases.

= There is any limitation for the events stored in the database? =

The only limitation is the database size restrictions that your hosting plan may have. However, to avoid reaching that possible limit, you can specify the days you want to keep the log entries.

== Changelog ==

= 1.1.5 =
* Enhanced log rotation process for better performance.
* Fixed issue that was overriding stored dates.
* Removed IP info through API; now accessible via link.
* Decluttered log records for improved readability.

= 1.1.4 =
* Addressed a security issue in the login failure process, preventing a potential exploit that could harm the website. This fix significantly enhances the overall safety and reliability of the plugin.
* Fixed some minor bugs that generated warnings in the logs.

= 1.1.3 =
* Performance improved while deleting old events

= 1.1.2 =
* Fixed a critical bug with Autoload

= 1.1 =
* Multisite support added.
* Post ID added in details
* Fixed and issue that displayed LogDash menu to users that should not see it.

= 1.0 =
* Initial Release

== Upgrade Notice ==

= 1.1.5 =
* Enhanced log rotation process for better performance.
* Fixed issue that was overriding stored dates.
* Removed IP info through API; now accessible via link.
* Decluttered log records for improved readability.

= 1.1.4 =
* Addressed a security issue in the login failure process, preventing a potential exploit that could harm the website. This fix significantly enhances the overall safety and reliability of the plugin.
* Fixed some minor bugs that generated warnings in the logs.

= 1.1.3 =
* Performance improved while deleting old events

= 1.1.2 =
* Fixed a critical bug with Autoload

= 1.1 =
* Multisite support added.
* Post ID added in details
* Fixed and issue that displayed LogDash menu to users that should not see it.

= 1.0 =
To have the initial release.