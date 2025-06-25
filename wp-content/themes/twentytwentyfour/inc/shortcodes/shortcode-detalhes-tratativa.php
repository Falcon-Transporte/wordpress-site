<?php

function detalhes_tratativa_shortcode()
{
	// Verifica se o parâmetro 'relatorio_id' foi passado
	if (!isset($_GET['relatorio_id'])) {
		return 'Relatório não encontrado.';
	}

	global $wpdb;

	// Sanitiza o valor de 'relatorio_id' e busca o relatório no banco de dados
	$relatorio_id = intval($_GET['relatorio_id']);
	$relatorio = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_relatorios WHERE id = %d", $relatorio_id));

	// Verifica se o relatório existe
	if (!$relatorio) {
		return 'Relatório não encontrado.';
	}

	// Busca a tratativa relacionada ao relatório
	$tratativa = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_tratativas WHERE id = %d", $relatorio->tratativa_id));

	if (!$tratativa) {
		return 'Tratativa não encontrada.';
	}

	// Recupera informações da tratativa
	$nivel_gravidade = $tratativa->nivel_gravidade;
	$lesao = !empty($tratativa->lesao) && unserialize($tratativa->lesao) !== [] ? unserialize($tratativa->lesao) : null;
	$parte_lesionada = !empty($tratativa->parte_lesionada) && unserialize($tratativa->parte_lesionada) !== [] ? unserialize($tratativa->parte_lesionada) : null;

	function is_serialized_data($data)
	{
		return is_string($data) && preg_match('/^(a|O|s|i|b|d|N):/', $data) && @unserialize($data) !== false;
	}

	$causa_raiz_valor = null;

	if (!empty($tratativa->causa_raiz)) {
		if (is_serialized_data($tratativa->causa_raiz)) {
			$array_causa_raiz = @unserialize($tratativa->causa_raiz);
			if (is_array($array_causa_raiz) && !empty($array_causa_raiz)) {
				$causa_raiz_valor = implode(', ', $array_causa_raiz);
			}
		} else {
			$causa_raiz_valor = $tratativa->causa_raiz; // valor antigo em texto simples
		}
	}
	$numero_cat = null;
	$numero_os = null;

	if (!empty($tratativa->numero_cat)) {
		$numero_cat = $tratativa->numero_cat;
	}

	if (!empty($tratativa->numero_os)) {
		$numero_os = $tratativa->numero_os;
	}

	$descricao = $tratativa->resolucao_ocorrido;
	$medida_disciplinar = $tratativa->medida_disciplinar;
	$observacao = $tratativa->observacao;
	$dataTratativa = DateTime::createFromFormat('Y-m-d', $tratativa->data_criacao)->format('d/m/Y');
	$hora_criacao = $tratativa->hora_criacao;
	$status = $tratativa->status;

	ob_start();

	// Exibir detalhes da tratativa
	echo '<div class="container mt-4">';
	echo '<h3>Detalhes da Tratativa</h3>';
	echo '<p><strong>Nível da gravidade:</strong> ' . esc_html($nivel_gravidade) . '</p>';
	if ($lesao && $parte_lesionada) {
		echo '<p><strong>Lesão:</strong> ' . esc_html(implode(', ', $lesao)) . '</p>';
		echo '<p><strong>Parte do corpo lesionada:</strong> ' . esc_html(implode(', ', $parte_lesionada)) . '</p>';
	}
	if ($numero_cat) {
		echo '<p><strong>Número CAT:</strong> ' . esc_html($numero_cat) . '</p>';
	}
	if ($numero_os) {
		echo '<p><strong>Número OS (Ordem de Serviço):</strong> ' . esc_html($numero_os) . '</p>';
	}
	echo '<p><strong>Causa raiz:</strong> ' . esc_html($causa_raiz_valor) . '</p>';
	echo '<p><strong>Houve Medida Disciplinar?</strong> ' . esc_html($medida_disciplinar) . '</p>';
	echo '<p><strong>Data de Criação:</strong> ' . esc_html($dataTratativa) . '</p>';
	echo '<p><strong>AÇÕES APÓS O OCORRIDO, OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS:</strong> ' . esc_html($descricao) . '</p>';
	echo '<p><strong>OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS:</strong> ' . esc_html($observacao) . '</p>';
	echo '<p><strong>Hora da Criação:</strong> ' . esc_html($hora_criacao) . '</p>';
	echo '<p><strong>Status:</strong> ' . esc_html($status) . '</p>';

	// Verifica se há uma página anterior válida
	$voltar_url = !empty($_SERVER['HTTP_REFERER']) ? esc_url($_SERVER['HTTP_REFERER']) : home_url('/');
	$dataHoraCriacaoStr = $tratativa->data_criacao . " " . $hora_criacao;
	if (!empty($dataHoraCriacaoStr)) {
		$dataHoraCriacao = DateTime::createFromFormat('Y-m-d H:i:s', $dataHoraCriacaoStr);
		$agora = new DateTime();

		if ($dataHoraCriacao) {
			$intervalo = $agora->getTimestamp() - $dataHoraCriacao->getTimestamp();
			$limite5min = 24 * 60 * 60; // 5 minutos

			if (get_current_user_id() === (int)$tratativa->responsavel_id) {
				$editar_tratativa_url = esc_url(add_query_arg([
					'relatorio_id' => $relatorio->id,
				], site_url('/formulario-tratativa'))); // ajuste essa URL conforme sua página

				echo '<a href="' . $editar_tratativa_url . '" class="btn btn-warning mt-3">Revisar Tratativa</a>';
			}
		} else {
			echo '<p class="text-danger">Erro ao interpretar data e hora do relatório.</p>';
		}
	} else {
		echo '<p class="text-muted">Este relatório foi criado antes da atualização do sistema e não possui informação de data/hora para revisão.</p>';
	}

	echo '<div class="mt-4">';
	echo '<a href="' . $voltar_url . '" class="btn btn-secondary">Voltar</a>';
	echo '</div>';


	return ob_get_clean();
}
add_shortcode('detalhes_tratativa', 'detalhes_tratativa_shortcode');
