<?php
// Auto-generated registry — all Lucide icons
// v0.0599: replaced manual 52-entry list with full library (1694 icons)
// Rollback: replace with previous manual registry
function metra_lucide_icons(){
    $dir = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'assets/lucide/';
    $files = glob( $dir . '*.svg' );
    $icons = [];
    foreach( $files as $file ){
        $name = basename( $file, '.svg' );
        $icons[ $name ] = $name;
    }
    ksort( $icons );
    return $icons;
}
