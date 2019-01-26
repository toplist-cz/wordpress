=== TopList.cz ===
Contributors: honza.skypala
Donate link: http://www.honza.info
Tags: toplist, toplist.cz, web, pages, analytics, statistics, widget
Requires at least: 4.3
Tested up to: 4.7
Stable tag: 4.2

TopList.cz is a popular web analytics service in Czech Republic. This plugin is for easy integration of your WordPress blog into this service.

== Description ==

For English, please see below.
  
<strong>Czech:</strong> Tento plug-in Vám zajistí snadné použití statistické služby TopList.cz ve vašem blogu provozovaném na systému WordPress. Plug-in přidá nový widget (nazvaný TopList.cz) a jeho umístěním na stránku (do sidebaru) zajistíte automatické používání služby. Autoři webových stránek běžně zařazují kód pro používání služby TopList.cz do šablony vzhledu - ovšem moje řešení pomocí pluginu/widgetu zajistí používání služby bez ohledu na použitou šablonu - můžete šablony vzhledu přepínat dle libosti a statistiky jsou stále zajištěny. K dispozici je plná konfigurace služby (např. vzhled ikony, detaily sledování apod.).

<strong>English:</strong> This plug-in allows for easy integration of web statistics service TopList.cz into your blog run by WordPress. Plug-in adds a new widget (called TopList.cz) to WordPress and by placing the widget on your page (sidebar) you integrate TopList.cz into your blog. It is common to put the code for such service into the theme template, but my solution utilizing it as a widget allows to run the statistics regardless of the theme used - you can switch the themes and it works all the time. The widget contains complete configuration (displayed icon, detailed analytics etc.).

== Installation ==

For English, see below.

<strong>Czech:</strong>

1.	Pokud ještě nemáte svou registraci na serveru toplist.cz, pak je zapotřebí se <a href="https://www.toplist.cz/edit/?a=e" target="_blank">zaregistrovat</a> a získat ID pro své webové stránky.
2.	Nahrajte kompletní adresář pluginu do wp-content/plugins.
3.	Aktivujte plugin TopList.cz v administraci plug-inů.
4.	Přidejte widget TopList.cz v administraci Vzhled->Widgety do postranního panelu.
5.	V konfiguraci widgetu zadejte své ID pro server toplist.cz, případně zvolte další volby. Uložte změny.

<strong>English:</strong>

1.	If you don't have a toplist.cz server registration yet, you have to <a href="https://www.toplist.cz/edit/?a=e" target="_blank">registrate</a> and receive ID number for your web presentation.
2.	Upload the full plugin directory into your wp-content/plugins directory.
3.	Activate the plugin in plugins administration.
4.	Add widget TopList.cz into your sidebar in Widgets administration.
5.	In widget configuration, enter your ID number for toplist.cz server; eventually you can change other options. Save changes.

== Frequently Asked Questions ==

<strong>Czech:</strong>

Od verze 3.2 widget umožňuje také nastavit, jestli se má obrázek (logo) ve sloupci vycentrovat nebo skrýt. To by sice mělo být standardně řešeno použitou šablonou skrz css kaskádový styl, nicméně někteří uživatelé nemusí být dostatečně zkušení v editaci kaskádových stylů, případně nemusejí mít možnost šablonu změnit, proto jsem do widgetu přidal možnost nastavit to přímo v něm.

Pokud chcete nastavit zobrazení widgetu ve vaší šabloně (tak jak to má být), pak do ní přidejte pro vycentrování:

<code>.widget_toplist_cz {text-align:center}</code>

případně pro skrytí zobrazení:

<code>.widget_toplist_cz {display:none}</code>

<strong>English:</strong>

Since version 3.2 you can also specify how to display the widget (logo) in the sidebar, you can center or hide it. This should normally be done via the theme css, but some users are not skilled in editing css styles or they might not have permission to change it, so I have added such option to specify the display directly in the widget config.

If you prefer to set the widget display in the theme css (as it should be), then for centering the widget use:

<code>.widget_toplist_cz {text-align:center}</code>

eventually for hiding the widget:

<code>.widget_toplist_cz {display:none}</code>

== Screenshots ==

1. Konfigurace widgetu / widget configuration
2. Dashboard widget

== Changelog ==

= 4.2 =
* Access to toplist.cz changed from HTTP to HTTPS
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

== License ==

WTFPL License 2.0 applies

<code>           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
                   Version 2, December 2004

Copyright (C) 2004 Sam Hocevar <sam@hocevar.net>

Everyone is permitted to copy and distribute verbatim or modified
copies of this license document, and changing it is allowed as long
as the name is changed.

           DO WHAT THE FUCK YOU WANT TO PUBLIC LICENSE
  TERMS AND CONDITIONS FOR COPYING, DISTRIBUTION AND MODIFICATION

 0. You just DO WHAT THE FUCK YOU WANT TO.</code>