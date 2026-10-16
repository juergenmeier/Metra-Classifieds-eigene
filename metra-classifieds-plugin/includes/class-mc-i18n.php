<?php
declare(strict_types=1);

namespace MetraClassifieds;

if (!defined('ABSPATH')) {
    exit;
}

final class MC_I18n {
    /**
     * Remove stale legacy translation artifacts from the plugin languages directory.
     *
     * Older development builds left translated .po/.mo files and an extra POT file in
     * place. Because WordPress updates do not delete removed files, those artifacts can
     * continue to override the plugin's English default strings. We keep only the
     * canonical files that belong to the current plugin structure.
     */
    public static function cleanup_legacy_language_files(): void {
        $lang_dir = MC_PLUGIN_DIR . 'languages/';
        if (!is_dir($lang_dir)) {
            return;
        }

        $keep = [
            'index.php',
            'metra-classifieds-plugin.pot',
            'metra-classifieds-plugin-en_US.po',
            'metra-classifieds-plugin-de_DE.po',
            'metra-classifieds-plugin-de_DE_formal.po',
        ];

        $files = glob($lang_dir . '*.{po,mo,pot}', GLOB_BRACE);
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $basename = basename((string) $file);
            if (!in_array($basename, $keep, true) && is_file($file)) {
                @unlink($file);
            }
        }
    }

    public static function load_textdomain(): void {
        self::cleanup_legacy_language_files();
        load_plugin_textdomain('metra-classifieds-plugin', false, dirname(plugin_basename(MC_PLUGIN_FILE)) . '/languages');
    }
}
