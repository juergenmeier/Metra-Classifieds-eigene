<?php
function metra_lucide_render($key){
    $icons = metra_lucide_icons();
    if(!isset($icons[$key])) return '';
    $file = plugin_dir_path(__FILE__).'../../assets/lucide/'.$icons[$key].'.svg';
    if(!file_exists($file)) return '';
    return (string) file_get_contents($file);
}
