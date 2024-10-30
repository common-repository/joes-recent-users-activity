=== Joe's Recent Users Activity ===
Contributors: JoeWa1980
Donate link: https://www.paypal.me/joewakeford/
Tags: login log,rename admin,brute force indicator,security, history
Requires at least: 5.0.0
Requires PHP: 5.6.40
Tested up to: 6.7
Stable tag: 2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A mobile-responsive plugin showing the last 100 logged-in users & their last page in admin via a 'Recent Activity' menu.

== Description ==

A mobile responsive lightweight plugin displaying the most recent 100 logged-in users and their last viewed page in the admin area accessible via a 'Recent Activity' admin menu link.

1. **User ID** - The user ID associated with the logged in party
1. **Username** - The currently logged-in user's username, linking to the user's profile page
1. **Role** - The user's current role within the Wordpress installation
1. **Last Page Viewed** - The front-end page the user last viewed. Admin pages and callbacks are not logged, linking to the page itself
1. **IP Address** - The user's IP address
1. **Last Login** - The date and time of the user's most recent login using the date format set within the Wordpress installation
1. **Time active** - The time from the last login time to the most recenty viewed page

'Top 10 Exited Pages' shows the five most common pages for logged-in visitors to see before they leave the site.

= Features =

A mobile responsive lightweight plugin displaying the most recent 100 logged-in users and their last viewed page in the admin area accessible via a 'Recent Activity' admin menu link. A section called, 'Top 10 Exited Pages' shows the ten most common pages for logged-in visitors to see before they leave the site.

= Translations =

None currently, but it is such a simple plugin there really is not much language to consider.

= How to use the plugin? =

1. To see all the tracked records in admin, click on the plugin menu shown in the left sidebar.

= Bug Fixes =

If you find any bug, please create a topic with a step by step description to reproduce the bug.
Please search the forum before creating a new topic.

= Keywords =
user log, log, logger, detector, tracker, membership, register, sign up, admin, subscriber, editor, contributor, geo location, profile, front end registration, manager, report, statistics, activity, user role editor

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/joes_recent_users_activity` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress.

== Frequently Asked Questions ==

= Can this plugin track the info of guest users? =
No.

= Is this plugin mobile responsive? =
Yes.

= Where to see login list in admin? =
After activating the plugin, just re-login and then click on "Recent Activity" menu on the left sidebar to see the login list.

There are no settings to change.

== Screenshots ==

1. User login list table for backend
2. Top 10 Exited Pages for backend

== Third Party Code Integration ==
A PayPal Donate button is integrated into the plugin page if users would like to support the developer. The code used is shown at https://developer.paypal.com/sdk/donate/ and PayPal's privacy terms can be read at https://www.paypal.com/myaccount/privacy/privacyhub/


== Changelog ==
= 2.4 (28th October 2024) =
- Compatibility with WordPress 6.7

= 2.3 (5th September 2024) =
- Compatibility with WordPress 6.6.1

= 2.2 (8th May 2024) =
- Compatibility with WordPress 6.5.3

= 2.1 (25th March 2024) =
- Compatibility with WordPress 6.5

= 2.0.3 (25th March 2024) =
- plugin blueprint.json file added for testing the plugin easily

= 2.0.2 (25th March 2024) =
- plugin description updated for brevity

= 2.0.1 (25th March 2024) =
- corrected spelling errors

= 2.0 (25th March 2024) =
- compatibility with the latest version of Wordpress (6.4.3)

= 1.9 (9th November 2023) =
- changed the default icon in the admin menu
- added title and subheader for mobile explaining sorting

= 1.8 (9th November 2023) =
- added mobile responsiveness for the main table

= 1.7 (7th November 2023) =
- table increased to 100 most recent users' activity (from 10)
- paginaton introduced
- most visited page display increased to 10 (from 5)

= 1.6 (1st November 2023) =
- sanitize function renamed to match the rest of the plugin functions

= 1.5 (28th October 2023) =
- additional data sanitized and escaped, nonce added to clear all results button

= 1.4 (25th October 2023) =
- additional data sanitized and escaped

= 1.3 (16th October 2023) =
- Nonce added to the POST call to prevent unauthorised access.
- prepare() added to database clear query to protect the code from SQL injection vulnerabilities.
- detail about PayPal Donate button usage added to readme,txt
- all necessary data sanitized, escaped, and validated
- variables escaped when echo'd

= 1.2 (9th October 2023) =
- All data being read from any of these PHP global variables $_POST / $_GET / $_REQUEST / $_COOKIE / $_SERVER / $_SESSION / $_FILES has been sanitized before storing it in another variable or doing something else with it.

In this code, the following sanitization functions have been used:

sanitize_text_field() for text fields.
absint() for integers.
esc_url_raw() for raw URLs.
These sanitization functions ensure that the input data is safe to use and minimize the risk of security vulnerabilities.

- Appropriate escaping functions (esc_html, esc_url_raw, sanitize_text_field) added to the variables that are being echoed or printed to ensure that the output is safe from XSS vulnerabilities.

= 1.1 (27th July 2023) =
- Added top 5 most exited pages (with clickable links), Top 5 exited pages only include front-end pages and exclude admin-ajax.php, 'nonce', 'wp-json', and others not matching front-end permalinks.
- Added line space and contact details under the main title. 
- Added a 'Clear all results' button in case the table becomes bloated

= 1.0 (24th July 2023) =
Plugin released