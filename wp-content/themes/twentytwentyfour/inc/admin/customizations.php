<?php

// Oculta a barra de pesquisa no admin bar
function hide_admin_bar_search()
{
	echo '<style>#wp-admin-bar-search { display: none; }</style>';
}
add_action('admin_bar_menu', 'hide_admin_bar_search');
