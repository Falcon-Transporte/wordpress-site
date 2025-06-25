<?php

function success_message_shortcode()
{
	ob_start();
	echo '<div style="display:flex; align-items:center; flex-direction:column; height: 400px; justify-content: space-around;">';
	echo '<h1 style="margin-top: 10%; margin-bottom: 10%;"> Recebemos sua ocorrência </h1>';
	echo '<div class="alert alert-success" role="alert" style="text-align:center;">';
	echo 'Em breve receberá a devolutiva de sua ocorrência.';
	echo '</div>';
	echo '<div style="display: inline;">';

	// Obtém o ID do usuário analisado da URL, se existir
	$analisado_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

	// Define a URL do botão "Minhas Ocorrências"
	$minhas_ocorrencias_url = esc_url(add_query_arg('user_id', $analisado_id, '/minhas-ocorrencias/'));

	// Obtém a URL da página antes do formulário (duas páginas atrás)
	$previous_page = isset($_GET['previous_page']) ? urldecode($_GET['previous_page']) : '/';

	echo '<a href="' . $minhas_ocorrencias_url . '" class="btn btn-secondary mt-3">Minhas Ocorrências</a>';
	echo '</div>';
	echo '</div>';

	return ob_get_clean();
}
add_shortcode('success_message', 'success_message_shortcode');

