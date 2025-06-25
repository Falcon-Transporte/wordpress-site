<?php

function gerenciar_funcionario_shortcode()
{
	global $wpdb;

	$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'todos';
	$implicado_filter = (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && !empty($_GET['user_id'])) ? intval($_GET['user_id']) : '';
	$emissor_filter = isset($_GET['emissor_filter']) ? intval($_GET['emissor_filter']) : '';
	$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
	$itens_por_pagina = 10;
	$offset = ($pagina_atual - 1) * $itens_por_pagina;

	// Obter informações do implicado, se aplicável
	$implicado_info = !empty($implicado_filter) ? get_userdata($implicado_filter) : null;
	$cargo_implicado = 'Cargo desconhecido';
	$implicado_status = isset($implicado_info->user_status) && (string)$implicado_info->user_status === "0"
		? "ATIVO"
		: "INATIVO";

	if ($implicado_info && !empty($implicado_info->roles)) {
		$wp_roles = wp_roles();
		$cargos_legiveis = array_map(function ($role_slug) use ($wp_roles) {
			return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
		}, $implicado_info->roles);
		$cargo_implicado = implode(', ', $cargos_legiveis);
	}

	// Montar query base
	$query = "SELECT SQL_CALC_FOUND_ROWS * FROM wp_relatorios WHERE 1=1";
	$params = [];

	if ($status_filter !== 'todos') {
		$query .= " AND status = %s";
		$params[] = $status_filter;
	}

	if (!empty($implicado_filter)) {
		$query .= " AND implicado = %d";
		$params[] = $implicado_filter;
	}

	if (!empty($emissor_filter)) {
		$query .= " AND user_id = %d";
		$params[] = $emissor_filter;
	}

	$query .= " ORDER BY data_criacao DESC LIMIT %d OFFSET %d";
	array_push($params, $itens_por_pagina, $offset);

	$relatorios = $wpdb->get_results($wpdb->prepare($query, ...$params));
	$total_registros = $wpdb->get_var("SELECT FOUND_ROWS()");
	$total_paginas = ceil($total_registros / $itens_por_pagina);

	$total_pendentes = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM wp_relatorios WHERE status = 'pendente' AND implicado = %d",
			$implicado_filter
		)
	);

	$total_concluidos = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM wp_relatorios WHERE status = 'concluido' AND implicado = %d",
			$implicado_filter
		)
	);

	ob_start();
?>

	<style>
		.kpi-box {
			display: flex;
			background-color: #f4f4f4;
			border-radius: 12px;
			min-width: 120px;
			text-align: center;
			box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
			padding: 15px;
			align-items: center;
			flex-direction: column;
		}

		.kpi-box a {
			text-decoration: none;
		}

		.kpi-number {
			font-size: 36px;
			font-weight: bold;
			color: #2c3e50;
		}

		.kpi-label {
			font-size: 16px;
			color: #555;
		}

		.kpis-container {
			display: flex;
			flex-wrap: wrap;
			gap: 20px;
			justify-content: center;
			align-items: center;
			margin-bottom: 30px;
		}

		.add-button {
			display: flex;
			align-items: center;
			justify-content: center;
			background-color: #2ecc71;
			color: white;
			border: none;
			border-radius: 50%;
			width: 50px;
			height: 50px;
			font-size: 36px;
			font-weight: bold;
			font-family: 'Arial', sans-serif;
			/* fonte com boa geometria para centralizar + */
			cursor: pointer;
			box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
			transition: background-color 0.3s ease;
			outline: none;
			padding: 0;
			line-height: 1;
		}


		.add-button:focus {
			outline: 0;
		}

		.add-button:hover {
			background-color: #27ae60;
		}

		/* Melhorar alinhamento dos filtros */


		.card {
			background-color: #ffffff;
			border-radius: 16px;
			box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
			border: 1px solid #e6e6e6;
		}

		.card-body .form-label {
			display: block;
			margin-bottom: 0.35rem;
			font-weight: 600;
			color: #444;
		}

		.card-body .form-control-sm,
		.card-body .form-select-sm,
		.card-body .input-group-sm {
			width: 100%;
			display: block;
		}

		.card-body .input-group-sm>.form-control {
			border-top-right-radius: 0;
			border-bottom-right-radius: 0;
		}

		.card h5 {
			font-size: 1.15rem;
			color: #34495e;
			margin-bottom: 1rem;
		}

		#protocolo_filter_group {
			display: flex;
			width: 100%;
		}

		#protocolo_filter_group .protocolo-input {
			flex: 1 1 auto;
			min-width: 0;
			height: 36px;
			font-size: 0.875rem;
			padding: 0.25rem 0.5rem;
			border-top-right-radius: 0;
			border-bottom-right-radius: 0;
			border-right: none;
			/* evita dupla borda com o botão */
		}

		#protocolo_filter_group .protocolo-btn {
			height: 32px;
			font-size: 0.875rem;
			padding: 0 10px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-top-left-radius: 0;
			border-bottom-left-radius: 0;
		}

		.selectpicker {
			box-shadow: none !important;
			border: 1px solid #e0e0e0 !important;
			background-color: #fafafa !important;
		}

		.row.g-3>div {
			border-bottom: 1px solid #f0f0f0;
			padding-bottom: 10px;
		}

		@media (min-width: 768px) {
			.row.g-3>div {
				border-bottom: none;
			}

		}
	</style>

	<div class="container mt-5">
		<?php if ($implicado_info) : ?>
			<!-- Seção de perfil do implicado, se houver filtro -->
			<div class="card shadow-sm mb-4">
				<div class="card-header bg-secondary text-white text-center">
					<h4>Perfil do Implicado</h4>
				</div>
				<div class="card-body">
					<p><strong>Nome:</strong> <?php echo esc_html($implicado_info->display_name); ?></p>
					<p><strong>Email:</strong> <?php echo esc_html($implicado_info->user_email); ?></p>
					<p><strong>Função:</strong> <?php echo esc_html($cargo_implicado); ?></p>
					<p><strong>Condição: </strong><?php echo esc_html($implicado_status); ?></p>
					<a href="<?php echo esc_url(site_url('/gerenciar-usuario')); ?>" class="btn btn-secondary">Voltar</a>
					<a href="<?php echo esc_url(site_url('/cadastro-usuario/?editar_id=' . $implicado_filter)); ?>" class="btn btn-secondary">Editar</a>
				</div>
			</div>
		<?php endif; ?>
		<div class="kpis-container">
			<!-- Botão de adicionar nova ocorrência -->
			<div class="kpi-box">
				<a href="<?php echo esc_url(site_url('/ocorrencia/?implicado=' . $implicado_filter)); ?>" title="Adicionar nova ocorrência">
					<button class="add-button">+</button>
				</a>
				<div class="kpi-label">Nova Ocorrência</div>
			</div>

			<div class="kpi-box">
				<div class="kpi-number"><?php echo $total_pendentes; ?></div>
				<div class="kpi-label">Ocorrências Pendentes</div>
			</div>
			<div class="kpi-box">
				<div class="kpi-number"><?php echo $total_concluidos; ?></div>
				<div class="kpi-label">Ocorrências Concluídas</div>
			</div>

		</div>

		<form method="GET" action="">
			<input type="hidden" name="user_id" value="<?php echo esc_attr($implicado_filter); ?>" />

			<div class="card shadow-sm border-0 mb-4" style="background-color: #fefefe; border-radius: 16px;">
				<div class="card-body">
					<h5 class="mb-4 fw-semibold text-primary d-flex align-items-center">
						<i class="bi bi-funnel-fill me-2"></i>Filtrar por:
					</h5>

					<div class="row g-3">

						<!-- Status -->
						<div class="col-md-3">
							<label for="status_filter" class="form-label text-muted fw-medium">Status da Ocorrência</label>
							<select id="status_filter" name="status_filter" class="form-select form-select-sm selectpicker shadow-sm" onchange="this.form.submit()">
								<option value="todos" <?php selected($status_filter, 'todos'); ?>>Todos</option>
								<option value="pendente" <?php selected($status_filter, 'pendente'); ?>>Pendente</option>
								<option value="concluído" <?php selected($status_filter, 'concluído'); ?>>Concluído</option>
							</select>
						</div>

						<!-- Emissor -->
						<div class="col-md-3">

							<label for="emissor_filter" class="form-label text-muted fw-medium">Emissor</label>
							<select id="emissor_filter" name="emissor_filter" class="form-select form-select-sm selectpicker shadow-sm" data-live-search="true" onchange="this.form.submit()">
								<option value="">Todos</option>
								<?php
								$usuarios = get_users();
								foreach ($usuarios as $usuario) {
									echo '<option value="' . esc_attr($usuario->ID) . '" ' . selected($emissor_filter, $usuario->ID, false) . '>' . esc_html($usuario->display_name) . '</option>';
								}
								?>
							</select>
						</div>

						<div class="col-md-3">
							<label for="protocolo" class="form-label">Protocolo</label>
							<div class="input-group input-group-sm" id="protocolo_filter_group">
								<input type="text" id="protocolo" class="form-control protocolo-input"
									value="<?php echo esc_attr(isset($_GET['protocolo_filter']) ? $_GET['protocolo_filter'] : ''); ?>" name="protocolo_filter"
									class="form-control" placeholder="Ex: 2025052201" />
								<button type="submit" class="btn btn-outline-secondary" title="Pesquisar protocolo">
									<i class="bi bi-search"></i>
								</button>
							</div>
						</div>





					</div>
				</div>
			</div>
		</form>
		<!-- Tabela de relatórios -->
		<?php if (!empty($relatorios)) : ?>
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered">
					<thead class="thead-light">
						<tr>
							<th style="width: 15%;">Protocolo</th>
							<th style="width: 20%;">Nome do Emissor</th>
							<th style="width: 15%;">Cargo</th>
							<th style="width: 12%;">Data</th>
							<th style="width: 10%;">Hora</th>
							<th style="width: 18%;">Implicado</th>
							<th style="width: 10%;">Status</th>
							<th style="width: 10%;">Ações</th>
						</tr>
					</thead>
					<tbody>

						<?php foreach ($relatorios as $relatorio) :
							$user_info = get_userdata($relatorio->user_id);
							$implicado_info = get_userdata($relatorio->implicado);

							$nome_usuario = $user_info ? $user_info->display_name : 'Desconhecido';
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
							$nome_implicado = $implicado_info ? $implicado_info->display_name : 'Desconhecido';

							$dataFormatada = DateTime::createFromFormat('Y-m-d', $relatorio->data_criacao)->format('d/m/Y');
                            
							$detalhes_url_ocorrencia = esc_url(add_query_arg([
								'relatorio_id' => $relatorio->id,
								'user_id' => $implicado_filter,
								'origem' => 'usuario/?user_id=' . $implicado_filter
							], site_url('/detalhes-ocorrencia')));

							$detalhes_url_tratativa = esc_url(add_query_arg([
								'relatorio_id' => $relatorio->id,
								'user_id' => $implicado_filter
							], site_url('/detalhes-tratativa')));

							$gerar_pdf_url = esc_url(add_query_arg(['generate_pdf' => 'true', 'relatorio_id' => $relatorio->id], site_url('/gerar-pdf')));
						?>

							<tr>
								<td><?php echo esc_html($relatorio->numero_protocolo); ?></td>
								<td><?php echo esc_html($nome_usuario); ?></td>
								<td><?php echo esc_html($cargo_usuario); ?></td>
								<td><?php echo esc_html($dataFormatada); ?></td>
								<td><?php echo esc_html($relatorio->hora_criacao); ?></td>
								<td><?php echo esc_html($nome_implicado); ?></td>
								<td><?php echo esc_html($relatorio->status); ?></td>
								<td>
									<a href="<?php echo $detalhes_url_ocorrencia; ?>" class="btn btn-primary btn-sm w-100">Detalhes</a>
									<?php if ($relatorio->status !== 'pendente') : ?>
										<a href="<?php echo $gerar_pdf_url; ?>" class="btn btn-dark btn-sm mt-1 w-100">RAAITO</a>
									<?php endif; ?>
								</td>
							</tr>

						<?php endforeach; ?>

					</tbody>
				</table>
			</div>

			<nav>
				<ul class="pagination justify-content-center">
					<?php for ($i = 1; $i <= $total_paginas; $i++) : ?>
						<li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
							<a class="page-link" href="?pagina=<?php echo $i; ?>&status_filter=<?php echo esc_attr($status_filter); ?>&user_id=<?php echo esc_attr($implicado_filter); ?>&emissor_filter=<?php echo esc_attr($emissor_filter); ?>">
								<?php echo $i; ?>
							</a>
						</li>
					<?php endfor; ?>
				</ul>
			</nav>

		<?php else : ?>
			<p class="text-center">Não há relatórios com os filtros aplicados.</p>
		<?php endif; ?>
	</div>

<?php
	return ob_get_clean();
}
add_shortcode('gerenciar_funcionario', 'gerenciar_funcionario_shortcode');