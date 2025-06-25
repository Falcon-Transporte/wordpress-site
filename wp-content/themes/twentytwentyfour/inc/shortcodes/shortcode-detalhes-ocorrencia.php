<?php

function detalhes_ocorrencia_shortcode()
{
	// Verifica se o parâmetro 'relatorio_id' foi passado
	if (!isset($_GET['relatorio_id'])) {
		return 'Relatório não encontrado.';
	}

	global $wpdb;

	$current_user = wp_get_current_user();
	$user_roles = (array) $current_user->roles;
	$is_restricted_user = array_intersect(['administrator', 'escritorio', 'ti', 'rh', 'gpf', 'agp'], $user_roles);

	// Sanitiza o valor de 'relatorio_id' e busca o relatório no banco de dados
	$relatorio_id = intval($_GET['relatorio_id']);
	$relatorio = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_relatorios WHERE id = %d", $relatorio_id));

	// Verifica se o relatório existe
	if (!$relatorio) {
		return 'Relatório não encontrado.';
	}

	$usuario_id = $relatorio->user_id;
	$user_info = get_userdata($usuario_id);
	$nome_usuario = $user_info ? $user_info->display_name : 'Usuário desconhecido';
	$wp_roles = wp_roles(); // pega apenas uma vez

	// Cargo do emissor
	if ($user_info && !empty($user_info->roles)) {
		$cargos_usuario = array_map(function ($role_slug) use ($wp_roles) {
			return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
		}, $user_info->roles);
		$cargo_usuario = implode(', ', $cargos_usuario);
	} else {
		$cargo_usuario = 'Cargo desconhecido';
	}

	// Implicado
	$implicado_info = get_userdata($relatorio->implicado);
	$nome_implicado = $implicado_info ? $implicado_info->display_name : esc_html($relatorio->implicado);

	// Cargo do implicado
	if ($implicado_info && !empty($implicado_info->roles)) {
		$cargos_implicado = array_map(function ($role_slug) use ($wp_roles) {
			return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
		}, $implicado_info->roles);
		$cargo_implicado = implode(', ', $cargos_implicado);
	} else {
		$cargo_implicado = 'Cargo desconhecido';
	}

	$dataFormatada = DateTime::createFromFormat('Y-m-d', $relatorio->data_criacao)->format('d/m/Y');

	$analisado_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

	$tratativa = $relatorio->tratativa_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_tratativas WHERE id = %d", $relatorio->tratativa_id)) : null;

	// Inicializa valores padrão
	$nivel_gravidade = null;
	$numero_os = null;
	$numero_cat = null;
	$lesao = null;
	$partes_lesada = null;
	$causa_raiz = null;
	$descricao = null;
	$observacao = null;
	$medida_disciplinar = null;
	$dataTratativa = null;
	$status = null;
	$hora_criacao = null;

	if ($tratativa) {
		$nivel_gravidade = $tratativa->nivel_gravidade;
		$lesao = !empty($tratativa->lesao) && unserialize($tratativa->lesao) !== [] ? unserialize($tratativa->lesao) : null;
		$partes_lesada = !empty($tratativa->parte_lesionada) && unserialize($tratativa->parte_lesionada) !== [] ? unserialize($tratativa->parte_lesionada) : null;

		function is_serialized_data($data)
		{
			return is_string($data) && preg_match('/^(a|O|s|i|b|d|N):/', $data) && @unserialize($data) !== false;
		}

		if (!empty($tratativa->causa_raiz)) {
			if (is_serialized_data($tratativa->causa_raiz)) {
				$array_causa_raiz = @unserialize($tratativa->causa_raiz);
				if (is_array($array_causa_raiz) && !empty($array_causa_raiz)) {
					$causa_raiz = implode(', ', $array_causa_raiz);
				}
			} else {
				$causa_raiz = $tratativa->causa_raiz; // valor antigo em texto simples
			}
		}

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
	}

	// Obtém o ID do usuário analisado (se disponível na URL)

	ob_start();
	/* CSS diretamente no template */
// Exibir detalhes do relatório
	echo '<style>
	.info-bloco {
	  background-color: #f8f9fa;
	  border-left: 4px solid #0d6efd;
	  padding: 12px 16px;
	  border-radius: 6px;
	  margin-bottom: 12px;
	}

	.info-bloco strong {
	  color: #495057;
	  display: block;
	  font-size: 0.9rem;
	  margin-bottom: 4px;
	}

	.info-bloco span {
	  color: #212529;
	  font-size: 1rem;
	  font-weight: 500;
	}

	.ocorrencia-imagens {
	  display: flex;
	  flex-wrap: wrap;
	  gap: 12px;
	  margin-top: 12px;
	}

	.ocorrencia-imagens img {
	  max-width: 200px;
	  height: auto;
	  border-radius: 6px;
	  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
	  transition: transform 0.3s ease;
	}

	.ocorrencia-imagens img:hover {
	  transform: scale(1.05);
	}
	</style>';
	echo '<div class="container mt-4">';

	echo '<div class="card mb-4 shadow-sm">';
	echo '<div class="card-body">';
	echo '<h3>Detalhes da Ocorrência</h3>';
	echo '<div class="info-bloco"><strong>Número de Protocolo:</strong><span>' . esc_html($relatorio->numero_protocolo) . '</span></div>';
	echo '<div class="info-bloco"><strong>Nome do Emissor:</strong><span>' . esc_html($nome_usuario) . '</span></div>';
	echo '<div class="info-bloco"><strong>Cargo do Emissor:</strong><span>' . esc_html($cargo_usuario) . '</span></div>';
	echo '<div class="info-bloco"><strong>Data:</strong><span>' . esc_html($dataFormatada) . '</span></div>';
	echo '<div class="info-bloco"><strong>Hora:</strong><span>' . esc_html($relatorio->hora_criacao) . '</span></div>';
	echo '<div class="info-bloco"><strong>Local:</strong><span>' . esc_html($relatorio->local) . '</span></div>';
	echo '<div class="info-bloco"><strong>Implicado:</strong><span>' . esc_html($nome_implicado) . '</span></div>';
	echo '<div class="info-bloco"><strong>Cargo do Implicado:</strong><span>' . esc_html($cargo_implicado) . '</span></div>';
	echo '<div class="info-bloco"><strong>Modalidade:</strong><span>' . esc_html(implode(', ', unserialize($relatorio->modalidade))) . '</span></div>';

	$imagens = $wpdb->get_results($wpdb->prepare(
		"SELECT imagem_id FROM wp_relatorio_imagens WHERE relatorio_id = %d",
		$relatorio->id
	));

	if (!empty($imagens)) {
		echo '<div class="info-bloco"><strong>Imagens da Ocorrência:</strong>';
		echo '<div class="ocorrencia-imagens">';
		foreach ($imagens as $imagem) {
			$image_url = wp_get_attachment_url($imagem->imagem_id);
			if ($image_url) {
				echo '<img src="' . esc_url($image_url) . '" alt="Imagem da ocorrência">';
			}
		}
		echo '</div></div>';
	}
	echo '<div class="info-bloco"><strong>Descrição:</strong><span>' . htmlspecialchars_decode(stripslashes($relatorio->descricao)) . '</span></div>';
	echo '<div class="info-bloco"><strong>Status:</strong><span>' . esc_html(ucfirst($relatorio->status)) . '</span></div>';

	$usuario_logado_id = get_current_user_id();
	$dataHoraCriacaoStr = $relatorio->hora_dia_ocorrencia ?? null;
    if (!empty($dataHoraCriacaoStr)) {
		$dataHoraCriacao = DateTime::createFromFormat('Y-m-d H:i:s', $dataHoraCriacaoStr);
		$agora = new DateTime();

		if ($dataHoraCriacao) {
			$intervalo = $agora->getTimestamp() - $dataHoraCriacao->getTimestamp();
			$limite5min = 24 * 60 * 60; // 5 minutos

			if ((int)$usuario_logado_id === (int)$usuario_id) {
				$editar_url = esc_url(add_query_arg([
					'editar_id' => $relatorio->id,
				], site_url('/editar-ocorrencia')));
				echo '<a href="' . $editar_url . '" class="btn btn-warning mt-3">Revisar Ocorrência</a>';
			}
		} else {
			echo '<p class="text-danger">Erro ao interpretar data e hora do relatório.</p>';
		}
	} else {
		echo '<p class="text-muted">Este relatório foi criado antes da atualização do sistema e não possui informação de data/hora para revisão.</p>';
	}

	if ($relatorio->status === 'pendente' && $is_restricted_user && empty($tratativa)) {
		$tratativa_url = esc_url(add_query_arg([
			'relatorio_id' => $relatorio->id,
			'user_id' => $analisado_id
		], site_url('/formulario-tratativa')));
		echo '<a href="' . $tratativa_url . '" class="btn btn-success mt-3" style="margin-left: 10px;">Aplicar Tratativa</a>';
	}
	echo '</div>';
	echo '</div>';

	if ($tratativa) {
		echo '<div class="card mb-4 shadow-sm">';
		echo '<div class="card-body">';
		echo '<h3>Detalhes da Tratativa</h3>';
		echo '<div class="info-bloco"><strong>Nível da gravidade:</strong><span>' . esc_html($nivel_gravidade) . '</span></div>';
		if ($lesao && $partes_lesada) {
			echo '<div class="info-bloco"><strong>Lesão:</strong><span>' . esc_html(implode(', ', $lesao)) . '</span></div>';
			echo '<div class="info-bloco"><strong>Parte do corpo lesionada:</strong><span>' . esc_html(implode(', ', $partes_lesada)) . '</span></div>';
		}
		if ($numero_cat) {
			echo '<div class="info-bloco"><strong>Número CAT:</strong><span>' . esc_html($numero_cat) . '</span></div>';
		}
		if ($numero_os) {
			echo '<div class="info-bloco"><strong>Número OS (Ordem de Serviço):</strong><span>' . esc_html($numero_os) . '</span></div>';
		}
		echo '<div class="info-bloco"><strong>Causa raiz:</strong><span>' . esc_html($causa_raiz) . '</span></div>';
		echo '<div class="info-bloco"><strong>Houve Medida Disciplinar?</strong><span>' . esc_html($medida_disciplinar) . '</span></div>';
		echo '<div class="info-bloco"><strong>Data de Criação:</strong><span>' . esc_html($dataTratativa) . '</span></div>';
		echo '<div class="info-bloco"><strong>AÇÕES APÓS O OCORRIDO, OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS:</strong><span>' . htmlspecialchars_decode(stripslashes($descricao)) . '</span></div>';
		echo '<div class="info-bloco"><strong>OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS:</strong><span>' . htmlspecialchars_decode(stripslashes($observacao)) . '</span></div>';
		echo '<div class="info-bloco"><strong>Hora da Criação:</strong><span>' . esc_html($hora_criacao) . '</span></div>';
        echo '<div class="info-bloco"><strong>Status:</strong><span>' . esc_html(ucfirst($status)) . '</span></div>';

		$dataHoraCriacaoStr = $tratativa->data_criacao . " " . $hora_criacao;
		if (!empty($dataHoraCriacaoStr)) {
			$dataHoraCriacao = DateTime::createFromFormat('Y-m-d H:i:s', $dataHoraCriacaoStr);
			$agora = new DateTime();
			if ($dataHoraCriacao && get_current_user_id() === (int)$tratativa->responsavel_id) {
				$editar_tratativa_url = esc_url(add_query_arg([
					'relatorio_id' => $relatorio->id,
				], site_url('/formulario-tratativa')));
				echo '<a href="' . $editar_tratativa_url . '" class="btn btn-warning mt-3">Revisar Tratativa</a>';
			}
		} else {
			echo '<p class="text-muted">Este relatório foi criado antes da atualização do sistema e não possui informação de data/hora para revisão.</p>';
		}
		echo '</div>';
		echo '</div>';
	}

$origem = $_SESSION['origem_detalhes_ocorrencia'] ?? '';
$voltar_url = 'javascript:history.back();'; // fallback padrão

if (!empty($origem)) {
    switch (true) {
        case str_starts_with($origem, 'usuario'):
            $voltar_url = site_url($origem);
            break;
        case $origem === 'minhas-ocorrencias':
            $voltar_url = site_url('/minhas-ocorrencias/');
            break;
        case $origem === 'gerenciar-ocorrencia':
            $voltar_url = site_url('/gerenciar-ocorrencia/');
            break;
        default:
            $voltar_url = site_url('/');
    }

    // adiciona ?clear_origem=1 para limpar depois de voltar
    $voltar_url = add_query_arg('clear_origem', '1', $voltar_url);
}

echo '<a href="' . esc_url($voltar_url) . '" class="btn btn-secondary mt-3">Voltar</a>';

	echo '</div>';

	return ob_get_clean();
}
add_shortcode('detalhes_ocorrencia', 'detalhes_ocorrencia_shortcode');

add_action('template_redirect', function () {
    $is_detalhes = is_page('detalhes-ocorrencia') && isset($_GET['relatorio_id']);
    $is_tratativa = is_page('formulario-tratativa') && isset($_GET['relatorio_id']);

    if (!$is_detalhes && !$is_tratativa) return;

    if (!isset($_SESSION)) {
        session_start();
    }

      // Permite limpar a origem
        if (isset($_GET['clear_origem']) && $_GET['clear_origem'] == '1') {
            unset($_SESSION['origem_detalhes_ocorrencia']);
            return;
        }

        // Se a origem vier via GET, armazena ou atualiza
        if (isset($_GET['origem']) && !empty($_GET['origem'])) {
            $_SESSION['origem_detalhes_ocorrencia'] = sanitize_text_field($_GET['origem']);
            return;
        }

    // Se já existe uma origem salva, NÃO sobrescreve
    if (!empty($_SESSION['origem_detalhes_ocorrencia'])) {
        return;
    }

    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    if (!empty($referer)) {
        $referer_parts = parse_url($referer);
        $referer_path = $referer_parts['path'] ?? '';
        $referer_path = str_replace(site_url(), '', $referer_path);
        $referer_slug = trim(basename($referer_path), '/');

        $origens_validas = ['usuario', 'minhas-ocorrencias', 'gerenciar-ocorrencia'];
        $origens_bloqueadas = ['editar-ocorrencia', 'formulario-tratativa'];

        if (
            in_array($referer_slug, $origens_validas) &&
            !in_array($referer_slug, $origens_bloqueadas) &&
            !str_contains($referer, 'detalhes-ocorrencia')
        ) {
            $_SESSION['origem_detalhes_ocorrencia'] = $referer;
        }
    }
});
