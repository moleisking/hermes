=== Hermes ===
Contributors: moleking64
Donate link: http://espeaky.com/
Tags: chat,converstion,email,message,skype,wechat,whatsapp,shortcode,user,id
Requires at least: 4.6
Tested up to: 5.0
Stable tag: 4.3
Requires PHP: 5.2.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==
A wordpress plugin that adds the ability to send messages to users by user id. This enables the page to hide the user contact details on the client side. This plugin requires a configured SMTP server to send messages of type email, Skype, WeChat and WhatsApp privided the client has these applications setup.

Example of shortcode
    paramaters: to = {userid of user being reviewed}, subject = {email subject}
    shortcodes:[hermes_chat to='1'],[hermes_enemy to='1'],[hermes_friend to='1'],[hermes_inbox path='\users\'],[hermes_email to='1' subject='News'],[hermes_help],[hermes_skype to='1'],[hermes_wechat to='1'],[hermes_whatsapp to='1']

== Installation ==
Normal plugin installation in wp-content/plugin directory

== Frequently Asked Questions ==
na

== Screenshots ==
1. Chat buttons 
2. Chat message

== Changelog ==

= 1.0 =
First stable version