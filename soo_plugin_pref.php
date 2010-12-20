<?php

$plugin['name'] = 'soo_plugin_pref';
$plugin['version'] = '0.2.2';
$plugin['author'] = 'Jeff Soo';
$plugin['author_uri'] = 'http://ipsedixit.net/txp/';
$plugin['description'] = 'Plugin preference manager';
$plugin['type'] = 2; // only when include_plugin() or require_plugin() is called
$plugin['order'] = 1;

if (!defined('txpinterface'))
	@include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---

// event handler called by other plugins on plugin_lifecycle and plugin_prefs events
function soo_plugin_pref( $event, $step, $defaults ) {
	preg_match('/^(.+)\.(.+)/', $event, $match);
	list( , $type, $plugin) = $match;
	$message = $step ? soo_plugin_pref_query($plugin, $step, $defaults): '';
	if ( $type == 'plugin_prefs' )
		soo_plugin_pref_ui($plugin, $defaults, $message);
}

// user interface for preference setting (plugin_prefs events)
function soo_plugin_pref_ui( $plugin, $defaults, $message = '' ) {
	$cols = 2;
	$align_rm = ' style="text-align:right;vertical-align:middle"';
	$prefs = soo_plugin_pref_query($plugin, 'select');
	
	// install prefs if necessary
	if ( $defaults and ! $prefs ) {
		soo_plugin_pref_query($plugin, 'enabled', $defaults);
		$prefs = soo_plugin_pref_query($plugin, 'select');
	}
	
	pagetop(gTxt('edit_preferences') . " &#8250; $plugin", $message);
	echo
		n. '<form method="post" name="soo_plugin_pref_form">' .
		n. startTable('list') .
			tr(n. tdcs(hed(
				gTxt('plugin') .' '. gTxt('edit_preferences') . ": $plugin"
				, 1),
			$cols))
	;

	foreach ( $prefs as $pref ) {
		extract($pref);
		$name = str_replace("$plugin.", '', $name);
		$input = $html == 'yesnoradio' ?
 			yesnoRadio($name, $val) :
 			fInput('text', $name, $val, 'edit', '', '', 20);
 		echo
 			n. tr(
 			n.t. tda($defaults[$name]['text'], $align_rm) .
 			n. td($input)
 		);
 	}

	echo
		n. sInput('update') .
		n. eInput("plugin_prefs.$plugin") .
		n. tr(n. tdcs(fInput('submit', 'soo_plugin_pref_update',
			gTxt('save'), 'publish'), $cols)) .
		tr(n. tdcs(href(
			gTxt('go_to') .' '. gTxt('plugins') .' '. gTxt('list')
			, '?event=plugin')
			,$cols)) .
		endTable() . '</form>' .
		n;
}

// preference CRUD
function soo_plugin_pref_query( $plugin, $action, $defaults = array() ) {

	if ( $action == 'select' )
		return safe_rows(
			'name, val, html, position', 
			'txp_prefs', 
			"name like '$plugin.%' order by position asc"
		);

	elseif ( $action == 'update' ) {
		$post = doSlash(stripPost());
		$allowed = array_keys(soo_plugin_pref_vals($plugin));
		foreach ( $post as $name => $val )
			if ( in_array($name, $allowed) )
				if ( ! set_pref("$plugin.$name", $val) )
					$error = true;
		return empty($error) ? gTxt('preferences_saved') : '';
	}
	
	elseif ( $action == 'enabled' ) {
		$prefs = soo_plugin_pref_vals($plugin);
		$add = array_diff_key($defaults, $prefs);
		$remove = array_diff_key($prefs, $defaults);
		foreach ( $add as $name => $pref )
			set_pref(
				$plugin . '.' . $name,
				$pref['val'],
				'plugin_prefs',
				2,
				$pref['html']
			);
		foreach ( $remove as $name => $val )
			safe_delete('txp_prefs', "name = '$plugin.$name'");
		
		// update position values
		foreach ( array_keys($defaults) as $i => $name )
			safe_update('txp_prefs', 
				"position = $i", 
				"name = '$plugin.$name'");
	}

	elseif ( $action == 'deleted' )
		safe_delete('txp_prefs', "name like '$plugin.%'");
}

// get a plugin's prefs; return as associative name:value array
function soo_plugin_pref_vals( $plugin ) {
	$rs = soo_plugin_pref_query($plugin, 'select');
	foreach ( $rs as $r ) {
		extract ($r);
		$name = str_replace("$plugin.", '', $name);
		$out[$name] = $val;
	}
	return isset($out) ? $out : array();
}

# --- END PLUGIN CODE ---

if (0) {
?>
<!-- CSS SECTION
# --- BEGIN PLUGIN CSS ---
<style type="text/css">
div#sed_help pre {padding: 0.5em 1em; background: #eee; border: 1px dashed #ccc;}
div#sed_help h1, div#sed_help h2, div#sed_help h3, div#sed_help h3 code {font-family: sans-serif; font-weight: bold;}
div#sed_help h1, div#sed_help h2, div#sed_help h3 {margin-left: -1em;}
div#sed_help h2, div#sed_help h3 {margin-top: 2em;}
div#sed_help h1 {font-size: 2.4em;}
div#sed_help h2 {font-size: 1.8em;}
div#sed_help h3 {font-size: 1.4em;}
div#sed_help h4 {font-size: 1.2em;}
div#sed_help h5 {font-size: 1em;margin-left:1em;font-style:oblique;}
div#sed_help h6 {font-size: 1em;margin-left:2em;font-style:oblique;}
div#sed_help li {list-style-type: disc;}
div#sed_help li li {list-style-type: circle;}
div#sed_help li li li {list-style-type: square;}
div#sed_help li a code {font-weight: normal;}
div#sed_help li code:first-child {background: #ddd;padding:0 .3em;margin-left:-.3em;}
div#sed_help li li code:first-child {background:none;padding:0;margin-left:0;}
div#sed_help dfn {font-weight:bold;font-style:oblique;}
div#sed_help .required, div#sed_help .warning {color:red;}
div#sed_help .default {color:green;}
</style>
# --- END PLUGIN CSS ---
-->
<!-- HELP SECTION
# --- BEGIN PLUGIN HELP ---
 <div id="sed_help">

h1. soo_plugin_pref

 <div id="toc">

h2. Contents

* "Overview":#overview
* "Usage":#usage
* "Info for plugin authors":#authors
** "Configuration":#config
** "Functions":#functions
** "Limitations":#limitations
* "History":#history

 </div>

h2(#overview). Overview

This is an admin-side plugin for managing plugin preferences. 

For users, it provides a consistent admin interface to set preferences for *soo_plugin_pref*-compatible plugins, and automatically installs or removes those preferences from the database as appropriate. Of course, when you upgrade a plugin your existing preference values are retained. 

For plugin authors, it allows you to add preference settings to your plugins without having to create the user interface or the preference-handling functions.

It uses the plugin prefs/lifecycle features introduced in Txp 4.2.0, so %(required)Txp 4.2.0 or greater is required%.

h2(#usage). Usage

*soo_plugin_pref* only works with plugins that are designed to use it. (See "support forum thread":http://forum.textpattern.com/viewtopic.php?id=31732 for a list of compatible plugins.) As of version 0.2.1, you can install plugins in any order. A compatible plugin's preferences will be installed the first time you "activate it or click its *Options* link in the plugin list":http://textbook.textpattern.net/wiki/index.php?title=Plugins#Panel_layout_.26_controls while *soo_plugin_pref* is active. Its preferences will be removed from the database when you delete it while *soo_plugin_pref* is active.

To set a plugin's preferences, "click its *Options* link in the plugin list":http://textbook.textpattern.net/wiki/index.php?title=Plugins#Panel_layout_.26_controls.

h2(#authors). Info for plugin authors

*If you are not a plugin author, you can safely ignore the rest of this help text.*

h3(#config). Configuration

To configure a plugin to work with *soo_plugin_pref*: 

If this is a public-side plugin, set the type so that it will also load on the admin side:

pre. $plugin['type'] = 1; 

Set the plugin flags at the top of the plugin template (using the "http://textpattern.googlecode.com/svn/development/4.x-plugin-template/":http://textpattern.googlecode.com/svn/development/4.x-plugin-template/ template all you need to do is uncomment the @$plugin['flags']@ line):

pre. if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); 
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); 
$plugin['flags'] = PLUGIN_HAS_PREFS | PLUGIN_LIFECYCLE_NOTIFY;

Then somewhere in the plugin code section (substituting your plugin's name for @abc_my_plugin@, and whatever name you choose for the callback function):

pre. require_plugin('soo_plugin_pref');
add_privs('plugin_prefs.abc_my_plugin','1,2');
add_privs('plugin_lifecycle.abc_my_plugin','1,2');
register_callback('abc_my_plugin_prefs', 'plugin_prefs.abc_my_plugin');
register_callback('abc_my_plugin_prefs', 'plugin_lifecycle.abc_my_plugin');

and finally, define your plugin's preferences and route the @plugin_prefs@ and @plugin_lifecycle@ events to @soo_plugin_pref()@ in your callback function. Here's an example:

pre. function abc_my_plugin_prefs( $event, $step ) {
	$defaults = array(
		'foo' => array(
			'val'	=> 'foo',
			'html'	=> 'text_input',
			'text'	=> 'Helpful description',
		),
		'bar' => array(
			'val'	=> 1,
			'html'	=> 'yesnoradio',
			'text'	=> 'Equally helpful description',
		),
	);
	soo_plugin_pref($event, $step, $defaults);
}

For each preference, @val@ is the default value, and @html@ is the type of HTML input element used to display the preference in the admin interface; these go to the corresponding columns in @txp_prefs@. @text@ is the label that will appear in the admin interface; it is not stored in the database.

Preference names in the database are in the format "plugin_name.key", where "plugin_name" is your plugin's name, and "key" is the array key from the @$defaults@ array.

Each preference will be assigned a position value corresponding to its position in the defaults array, starting at 0. This determines its relative order in the admin interface.

Other @txp_prefs@ columns are set as follows:
* @event@ is always set to "plugin_prefs"
* @prefs_id@ is always set to @1@
* @type@ is always set to @2@ (hidden from main Prefs page)

h4. Alternative configuration if prefs are optional

If you wish to offer *soo_plugin_pref* preference management as an option rather than a requirement:

Set the plugin flags as above.

Prefix the error suppression operator ('@') to the @require_plugin()@ line, but otherwise set @add_privs()@ and @register_callback()@ as above.

Define your preference defaults in a separate function:

pre. function abc_my_plugin_defaults( ) {
	return array(
		'foo' => array(
			'val'	=> 'foo',
			'html'	=> 'text_input',
			'text'	=> 'Helpful description',
		),
		'bar' => array(
			'val'	=> 1,
			'html'	=> 'yesnoradio',
			'text'	=> 'Equally helpful description',
		),
	);
}

Then add a conditional check to your callback function:

pre. function abc_my_plugin_prefs( $event, $step ) {
	if ( function_exists('soo_plugin_pref') )
		soo_plugin_pref($event, $step, abc_my_plugin_defaults());
	else {
		// any custom preference handling goes here
	}
}

If nothing else, you should display a message for a @plugin_prefs.abc_my_plugin@ event if *soo_plugin_pref* is not installed. The version below checks the event type, then attempts to cobble together a meaningful message using @gTxt()@ fragments:

pre. function abc_my_plugin_prefs( $event, $step ) {
	if ( function_exists('soo_plugin_pref') )
		return soo_plugin_pref($event, $step, abc_my_plugin_defaults());
	if ( substr($event, 0, 12) == 'plugin_prefs' ) {
		$plugin = substr($event, 13);
		$message = '<p><br /><strong>' . gTxt('edit') . " $plugin " .
			gTxt('edit_preferences') . ':</strong><br />' . 
			gTxt('install_plugin') . ' <a
			href="http://ipsedixit.net/txp/92/soo_plugin_pref">soo_plugin_pref</a></p>';
		pagetop(gTxt('edit_preferences') . " &#8250; $plugin", $message);
	}
}

h3(#functions). Functions

There's also @soo_plugin_pref_vals( $plugin )@, a handy little function that returns your plugin's preferences as an associative array. It strips the plugin name and '.' from the name in the database, so that the array keys match those in your defaults array. My typical use of this, with the alternative (i.e., *soo_plugin_pref* optional) configuration shown above:

pre. if ( function_exists('soo_plugin_pref_vals') )
	$abc_my_plugin = soo_plugin_pref_vals('abc_my_plugin');
else 
	foreach ( abc_my_plugin_defaults() as $name => $atts )
		$abc_my_plugin[$name] = $atts['val'];


where @$abc_my_plugin@ is global.

h3(#limitations). Limitations

* Currently the only allowed values for @html@ are @text_input@ and @yesnoradio@.
* *soo_plugin_pref* only handles global preferences. If your plugin has a mix of global and per-user preferences, you will have to code all the handling of the per-user preferences.

h2(#history). Version History

h3. 0.2.2 (9/28/2009)

Fixed bug in pref position re-indexing

h3. 0.2.1 (9/26/2009)

* Pre-installing *soo_plugin_pref* is no longer required for automatic preference installation
* Each preference is now assigned a position value that determines relative order in the admin interface, and that corresponds to its position in the defaults array

h3. 0.2 (9/17/2009)

This version uses a different identifying scheme for preferences and hence is not compatible with the previous version. The plugin name is no longer stored in the @event@ column, so there is no longer any restriction on plugin name length.

h3. 0.1 (9/5/2009)

Basic plugin preference management:

* Automatic installation/removal of preferences on plugin install/deletion
* Simple admin interface for setting preferences


 </div>
# --- END PLUGIN HELP ---
-->
<?php
}

?>