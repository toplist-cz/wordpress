=== TOPlist ===
Contributors: toplist, honza.skypala
Donate link: https://www.toplist.cz
Tags: toplist, toplist.cz, web, pages, analytics, statistics, widget
Requires at least: 4.6
Requires PHP: 5.2
Tested up to: 5.2.4
Stable tag: 5.1

TOPlist.cz is a popular web analytics service in Czech Republic. This plugin is for easy integration of your WordPress blog into this service.

== Description ==
  
This plug-in allows for easy integration of web statistics service TOPlist.cz into your blog run by WordPress. Plug-in adds a new widget (called TOPlist) to WordPress and by placing the widget on your page (sidebar) you integrate TopList.cz into your blog. It is common to put the code for such service into the theme template, but my solution utilizing it as a widget allows to run the statistics regardless of the theme used - you can switch the themes and it works all the time. The widget contains complete configuration (displayed icon, detailed analytics etc.).

== Installation ==

1.	If you don't have a toplist.cz server registration yet, you have to <a href="https://www.toplist.cz/register/" target="_blank">register</a> and receive ID number for your web presentation.
2.	Upload the full plugin directory into your wp-content/plugins directory.
3.	Activate the plugin in plugins administration.
4.	Add widget TOPlist into your sidebar in Widgets administration.
5.	In widget configuration, enter your ID number for toplist.cz server; eventually you can change other options. Save changes.

== Frequently Asked Questions ==

Since version 3.2 you can also specify how to display the widget (logo) in the sidebar, you can center or hide it. This should normally be done via the theme css, but some users are not skilled in editing css styles or they might not have permission to change it, so I have added such option to specify the display directly in the widget config.

If you prefer to set the widget display in the theme css (as it should be), then for centering the widget use:

<code>.widget_toplist_cz {text-align:center}</code>

eventually for hiding the widget:

<code>.widget_toplist_cz {display:none}</code>

== Screenshots ==

1. Widget configuration
2. Dashboard widget

== Upgrade Notice ==

= 5.1 =
* Direct requests to API without caching

== Changelog ==

= 5.1 =
* Direct requests to API without caching
= 5.0 =
* Using TOPlist REST API (https://profi.toplist.cz/api/) instead of parsing web pages (which didn't work because of web changes)
* licence changed to MIT
* plugin got official support from TOPlist s.r.o. (company behind TOPlist.cz|sk|eu site)
= 4.1 =
* Added totals to the graph legends in dashboard widget
* Support for TopList security code
* Automatic (ajax) reload of dashboard widget every 15 minutes
= 4.0.1 =
* Bug fixes
= 4.0 =
* Added dashboard widget, which displays the statistics retrieved from toplist.cz/sk
= 3.2 =
* Added option for centering or hiding the widget.
= 3.1 =
* Added option for filtering WordPress admins out of the statistics.
= 3.0 =
* Recoded for WordPress 2.8 API.
= 2.0 =
* Added support for toplist.sk server.
* Link can lead to detailed statistics now.
= 1.0 =
* Initial release.

== Credits ==
* Thanx to [Honza Skypala](http://www.honza.info) for many years of contribution to this plugin

== License ==

MIT License

Copyright (c) 2019 TOPlist s.r.o.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
