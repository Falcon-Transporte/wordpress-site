<?php

function enqueue_custom_nav_style()
{
	wp_enqueue_style('custom-nav-style', get_template_directory_uri() . '/css/custom-nav-style.css');
	wp_enqueue_style('anexo-imagens', get_template_directory_uri() . '/css/anexo-imagens.css');
	wp_enqueue_style('buscar-usuarios-css', get_stylesheet_directory_uri() . '/css/buscar-usuarios.css', [], '1.0.0', 'all');
	wp_enqueue_style('bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');
	wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css');
	wp_enqueue_style('bootstrap-select-css', 'https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/css/bootstrap-select.min.css');
	wp_enqueue_style('bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_nav_style');

function enqueue_custom_nav_script()
{
	wp_enqueue_script('custom-nav-script', get_template_directory_uri() . '/scripts/custom-nav-script.js', array('jquery'), null, true);
	wp_enqueue_script('bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array('jquery'), null, true);
	wp_enqueue_script('select2-js', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js', array('jquery'), null, true);
	wp_enqueue_script('bootstrap-select-js', 'https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.18/dist/js/bootstrap-select.min.js', array('jquery'), null, true);
	wp_add_inline_script('bootstrap-select-js', 'jQuery(document).ready(function() { jQuery(".selectpicker").selectpicker(); });');
}
add_action('wp_enqueue_scripts', 'enqueue_custom_nav_script');
