=== Orbisius Support Tickets ===
Contributors: lordspace,orbisius
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A
Tags: orbisius,support,ticket,help,helpdesk,tickets
Requires at least: 3.0
Tested up to: 5.0
Requires PHP: 5.4
Stable tag: 1.0.0
License: GPLv2 or later

Minimalistic support ticket system that enables you to start providing awesome support in 2 minutes.

== Description ==

Orbisius Support Tickets is a minimalistic ticket support system that enables you to handle support requests nicely.
You need to create several pages and paste the relevant shortcodes so your customer can submit their support requests.

You can manually create the pages that the plugin needs with the following shortcodes or let the plugin create them for you (Orbisius Support Tickets > Settings).
Ideally, you should create a top level page called Support (/support) or Help (/help).
Then create the following subpages. The parent page link will be prepended automatically by WordPress.

My Tickets
link: /support/my-tickets
This shortcode lists user's tickets
[orbisius_support_tickets_list_tickets]

View Ticket
link: /support/view-ticket

This shortcode is for viewing a given ticket
[orbisius_support_tickets_view_ticket]

Submit Ticket
link: /support/submit-ticket
This shortcode lists ticket submission form
[orbisius_support_tickets_submit_ticket]

Add the newly created pages to the site's nav menu.
You need to go in to Appearance > Menus

= Support =
> If you have found a bug or have a recommendation submit a ticket https://github.com/orbisius/orbisius-support-tickets/issues
We can't fix something we don't know about.

If you've found a security glitch please email ASAP to: help at orbisius.com.

You can use the following shortcode to generate a link to a given page.

Link to view single ticket page
[orbisius_support_tickets_generate_page_link page=view_ticket]

Link to my tickets page
[orbisius_support_tickets_generate_page_link page=list_tickets]

Link to submit tickets page
[orbisius_support_tickets_generate_page_link page=submit_ticket]

if you want the link to be passed through esc_url pass this attribute.
esc=1

You can customize the email notification templates.
The plugin provides merge tags that you can using in the email templates. That way you'll be able to use the same template on multiple sites.

== Demo ==

TODO:
- notifications
- settings page -> set pages
- ticket statuses

= Author =

Svetoslav Marinov (Slavi) | <a href="http://orbisius.com" title="Custom Web Programming, Web Design, e-commerce, e-store, WordPress Plugin Development, Facebook and Mobile App Development in Niagara Falls, St. Catharines, Ontario, Canada" target="_blank">Custom Web and Mobile Programming by Orbisius.com</a>

== Upgrade Notice ==
n/a

== Screenshots ==
1. Plugin's Settings Page - todo

== Installation ==

1. Unzip the package, and upload `orbisius-support-tickets` to the `/wp-content/plugins/` directory OR install the plugin via WordPress admin.
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to use this plugin? =
Just install the plugin and activate it. The feedback text appear in the public area

= How to get support? =

Support is handled on our site: <a href="http://orbisius.com/support/" target="_blank" 
title="[new window]">http://orbisius.com/support/</a>
Please do NOT use other places to seek support because we can't possibly monitor every forum.

== Changelog ==

= 1.0.0 =
* Initial release

