<?php
// Shortcode para exibir h2 e img apenas para usuários logados
function h2_img_logado_shortcode()
{
	if (!is_user_logged_in()) {
		return '';
	}

	$output = '<h2 style="font-family: sans-serif; font-weight: 810; font-size: 1.85rem;">' . esc_html__('Entre em contato com o recursos humanos', 'text-domain') . '</h2>';
	$whatsapp_url = esc_url('https://api.whatsapp.com/send?phone=5511951288852');
	$whatsapp_img_src = esc_url(content_url('/uploads/2024/06/whatsapp-1.png'));

	$output .= '<a href="' . $whatsapp_url . '" target="_blank">';
	$output .= '<img fetchpriority="high" decoding="async" width="72" height="72" src="' . $whatsapp_img_src . '" alt="WhatsApp" style="width:72px;height:auto;margin-top:25px" />';
	$output .= '</a>';

	return $output;
}
add_shortcode('h2_img_logado', 'h2_img_logado_shortcode');
