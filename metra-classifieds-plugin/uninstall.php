<?php
/**
 * Uninstall handler for METRA Classifieds.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Keep data by default. If you later add a "delete data on uninstall" option,
// implement deletion here.

add_action('init', function() {
    load_plugin_textdomain('metra-classifieds-plugin', false, dirname(plugin_basename(__FILE__)) . '/languages');
});
