=== Orbisius Support Tickets ===
Contributors: lordspace,orbisius
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A
Tags: orbisius,support,ticket,tickets,help,helpdesk,awesome support
Requires at least: 3.0
Requires PHP: 5.2.4
Tested up to: 5.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimalistic support ticket system that enables you to start providing awesome support in 2 minutes.

== Description ==

Orbisius Support Tickets is a minimalistic ticket support system that enables you to start providing awesome support in 2 minutes.
You need to create/update several pages and paste the relevant shortcodes so your customer can submit their support requests.
There is a tool in the plugin's settings page that can help with the page creation.

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
> If you have found a bug or have a recommendation
<a href='https://github.com/orbisius/orbisius-support-tickets/issues' target='_blank'>submit a ticket </a>
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

The plugin should also work even if your hosting is running php v5.2.4 (min php version required by WordPress itself).

== Demo ==

TODO


= Author =

Svetoslav Marinov (Slavi) | <a href="https://orbisius.com" title="Custom Web Programming, Web Design, e-commerce, e-store, WordPress Plugin Development, Facebook and Mobile App Development in Niagara Falls, St. Catharines, Ontario, Canada" target="_blank">Custom Web and Mobile Programming by Orbisius.com</a>

== Upgrade Notice ==
n/a

== Screenshots ==
1. Plugin's Settings Page - todo

== Installation ==

1. Unzip the package, and upload `orbisius-support-tickets` to the `/wp-content/plugins/` directory OR install the plugin via WordPress admin.
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How to use this plugin? =
1. Install the plugin and activate it.
2. Configure the plugin (Orbisius Support Tickets > Settings) and its pages with the proper shortcodes (there's a tool within settings that can help with that).
3. Add pages to the site menu
4. Submit a test ticket. You can get to the submit ticket page from the plugin's settings page.

= I want to contribute? =

Awesome! Clone the project on github at https://github.com/orbisius/orbisius-support-tickets/issues

= How to contact the author? =

You can get in touch with at at
<a href="https://orbisius.com/contact/" target="_blank" title="[new window]">https://orbisius.com/contact/</a>

== Changelog ==

= 1.0.0 =
* Initial release

