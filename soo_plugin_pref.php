<?php
$plugin['version'] = '0.2.3';
$plugin['author'] = 'Jeff Soo';
$plugin['author_uri'] = 'http://ipsedixit.net/txp/';
$plugin['description'] = 'Plugin preference manager';
$plugin['type'] = 2; // only when include_plugin() or require_plugin() is called
$plugin['order'] = 1;
$plugin['allow_html_help'] = 1;

if (! defined('txpinterface')) {
    global $compiler_cfg;
    @include_once('config.php');
    @include_once($compiler_cfg['path']);
}

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

?>
