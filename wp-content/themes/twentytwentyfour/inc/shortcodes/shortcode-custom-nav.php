<?php

function custom_nav_shortcode($atts)
{
	$atts = shortcode_atts(array(
		'container' => 'nav',
		'container_class' => 'custom-nav-container',
		'container_id' => 'custom-nav',
		'menu_class' => 'custom-menu',
		'menu_id' => 'custom-menu',
		'unique_id' => 'header-menu',
		'show_hamburguer' => 'true'
	), $atts, 'custom_nav');

	$current_user = wp_get_current_user();
	$user_roles = (array) $current_user->roles;
	$is_restricted_user = array_intersect(['administrator', 'escritorio', 'ti', 'rh', 'gpf', 'agp'], $user_roles);

	$menu_items = [
		['url' => get_home_url(), 'label' => 'Início'],
		['url' => get_home_url() . '/quem-somos/', 'label' => 'Quem Somos'],
		['url' => get_home_url() . '/contato/', 'label' => 'Contatos'],
		['url' => get_home_url() . '/login', 'label' => 'Acessar Falcon'],
		['url' => get_home_url() . '/regulamento-interno/', 'label' => 'Regulamento Interno'],
		['url' => get_home_url() . '/gerenciar-usuario/', 'label' => 'Gestão de Funcionário', 'restricted_roles' => ['administrator', 'escritorio', 'ti', 'rh', 'gpf', 'agp']],
	];

	$output = '<' . esc_attr($atts['container']) . ' class="' . esc_attr($atts['container_class']) . '" id="' . esc_attr($atts['container_id']) . '">';
	if ($atts['show_hamburguer'] === 'true') {
		$output .= '<div class="hamburger" id="hamburger">&#9776;</div>';
	}
	$output .= '<ul class="' . esc_attr($atts['menu_class']) . '" id="' . esc_attr($atts['unique_id']) . '">';

	foreach ($menu_items as $item) {
		$show_item = true;

		if (is_user_logged_in()) {
			if ($item['label'] == 'Acessar Falcon') {
				$show_item = false;
			}

			if (isset($item['restricted_roles'])) {
				$show_item = false;
				foreach ($item['restricted_roles'] as $role) {
					if (in_array(strtolower($role), $user_roles)) {
						$show_item = true;
						break;
					}
				}
			}
		} else {
			if (in_array($item['label'], ['Perfil', 'Gestão de Ocorrência', 'Regulamento Interno', 'Gestão de Funcionário'])) {
				$show_item = false;
			}
		}

		if ($show_item) {
			$output .= '<li class="custom-menu-item"><a class="custom-menu-link" href="' . esc_url($item['url']) . '">' . esc_html($item['label']) . '</a></li>';
		}
	}

	if ($is_restricted_user) {
		$output .= '<li class="custom-menu-item dropdown">';
		$output .= '<a href="#" class="custom-menu-link dropdown-toggle">Ocorrências</a>';
		$output .= '<ul class="dropdown-menu">';
		$output .= '<li><a class="custom-menu-link" href="' . esc_url(get_home_url() . '/gerenciar-ocorrencia/') . '">Gestão de Ocorrência</a></li>';
		$output .= '<li><a class="custom-menu-link" href="' . esc_url(get_home_url() . '/dashboard-ocorrencias/') . '">Dashboard de Ocorrências</a></li>';
		$output .= '<li><a class="custom-menu-link" href="' . esc_url(get_home_url() . '/minhas-ocorrencias/') . '">Ocorrências Enviadas</a></li>';
		$output .= '</ul></li>';
	} else {
		if (is_user_logged_in()) {
			$output .= '<li class="custom-menu-item"><a class="custom-menu-link" href="' . esc_url(get_home_url() . '/minhas-ocorrencias/') . '">Ocorrências Enviadas</a></li>';
		}
	}

if (is_user_logged_in()) {
    // Perfil
    $output .= '<li class="custom-menu-item user-profile"><a class="custom-menu-link" href="' . esc_url(get_home_url() . '/perfil-do-usuario/') . '">Perfil</a></li>';
    
    // Logout
    $logout_url = wp_logout_url(get_home_url());
    $output .= '<li class="custom-menu-item logout-button"><a class="custom-menu-link logout-link" href="' . esc_url($logout_url) . '">Sair</a></li>';
}


	$output .= '</ul></' . esc_attr($atts['container']) . '>';

	return $output;
}
add_shortcode('custom_nav', 'custom_nav_shortcode');