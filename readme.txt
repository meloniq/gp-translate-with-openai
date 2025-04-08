=== GP Translate with OpenAI ===
Contributors: meloniq
Tags: glotpress, translate, machine translate, openai, chatgpt
Tested up to: 6.8
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A OpenAI translate plugin for GlotPress as a WordPress plugin.

== Description ==

A OpenAI translate plugin for [GlotPress as a WordPress plugin](https://wordpress.org/plugins/glotpress/).


Note: This plugin assumes the source language is English as support for automated translation from other source languages is limited.

= Configuration =

Once you have installed GP Translate with OpenAI, go to your WordPress admin screen and select "Settings > GP Translate with OpenAI".

You will have few fields to configure:

	1. Global API Key
	2. OpenAI Model
	3. Temperature (where lower values indicating greater determinism and higher values indicating more randomness)
	4. Custom Prompt (if you would like to adjust what the AI is returning, eg. glossary tips)

Each user can adjust, change these configuration on his profile page.

= OpenAI =

* Login/signup [OpenAI API](https://platform.openai.com/)
* Go to your account and scroll down to [API keys](https://platform.openai.com/settings/organization/api-keys)
* Create new `secret key` and put it into `Global API Key` of GP Translate with OpenAI.


= Setting the API key =

To set the API key for all users, go to the WordPress Dashboard, then Settings, then "GP Translate with OpenAI" and set the API key.

To set if for a specific user, go to the users profile and scroll down to the "GP Translate with OpenAI" section and set the API key.

Note, if both a global and user API key are set, the user API key will override the global API key.


== Changelog ==

= 1.0 =
* Initial release.
