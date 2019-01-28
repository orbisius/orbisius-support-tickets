=== Orbisius Support Tickets ===
Contributors: lordspace,orbisius
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7APYDVPBCSY9A
Tags: orbisius,support,ticket,tickets,help,helpdesk,awesome support
Requires at least: 3.0
Requires PHP: 5.2.4
Tested up to: 5.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Minimalistic support ticket system that enables you to start providing awesome support in 2 minutes.

== Description ==

Orbisius Support Tickets is a minimalistic ticket support system that enables you to start providing awesome support in 2 minutes.
You need to create/update several pages and paste the relevant shortcodes so your customer can submit their support requests.
There is a tool in the plugin's settings page that can help with the page creation.

This is 2 minute demo showing you how to configure and use the plugin.
[youtube https://youtu.be/4TBbLAjCaFY]

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

You can easily view the View tickets or submit ticket pages from plugin's settings page.


= Credits =
1. WordPress
2. Plugin's icon/logo is from Rawpixel https://unsplash.com/photos/3BK_DyRVf90
3. Orbisius for investing and sharing into this plugin.

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

Awesome! Clone the project on github at <a href='https://github.com/orbisius/orbisius-support-tickets/issues' target='_blank'>submit a ticket </a> and make fixes.
Before doing any work it would be nice to check with us so we can coordinate work because some features may have already been planned.

= How to contact the author? =

You can get in touch with us at
<a href="https://orbisius.com/contact/" target="_blank" title="[new window]">https://orbisius.com/contact/</a>

== Changelog ==

= 1.0.3 =
* Removed ORBISIUS_SUPPORT_TICKETS_PAGES_VIEW_TICKET_URL const so it doesn't cause some hard to find glitches.
* Now allowing non-logged in users to view the ticket if they provide the correct ticket password.
* Hooked into 'comments_open' filter to ensure that our tickets will always have comments enabled as some people might have then deactivated globally.
* Fixed: removed an extra closing div which was breaking submit ticket page's layout. Thanks again Ivo Minchev for reporting this. Ref: https://github.com/orbisius/orbisius-support-tickets/issues/1
* The created by default Ticket submit form shortcode doesn't render its title by default because the page already has a title. The title would be useful if the submit ticket form is used somewhere on an existing page and not standalone.
* Updated settings to require users to be logged or register in order to submit a ticket.
* Showing an email field if the user is not logged in.
* Saving user's IP and email if passed
* Using custom ticket password form
* Guests can now really post a reply.
* Removed url from ticket reply
* Tickets are custom comment type now: orb_sup_tx_reply so they don't appear in the recent comments widget.
* Added code that filters 'orb_sup_tx_reply' comment post types because ticket replies could contain sensitive data.
* Tickets appear in chronological order. That means the oldest one is at the top.
* Disabled WP comment flood protection for tickets i.e. people can post replies quickly.
* Disabled WP comment moderation emails for tickets. We use WP's internal post password system and WP "feels" it's necessary to alert the admin about this. In our case it's not necessary and is annoying.

= 1.0.2 =
* Fixed: Do not throw an exception when sanitizeData doesn't know how to sanitize the passed data e.g. NULL or object. Thanks Ivo Minchev for reporting this.
* sanitize_data -> sanitizeData

= 1.0.1 =
* Updated readme to include a shortcode so it YouTube video renders properly.
* Fixes in the list tickets section (td had to be used when in the table body)

= 1.0.0 =
* Initial release

