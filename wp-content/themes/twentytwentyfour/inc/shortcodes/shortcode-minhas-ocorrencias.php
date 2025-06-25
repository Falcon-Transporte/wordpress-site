<?php
function minhas_ocorrencias_shortcode()
{
	global $wpdb;

	$current_user_id = get_current_user_id();
	$implicado_filter = (isset($_GET['user_id']) && is_numeric($_GET['user_id']) && !empty($_GET['user_id'])) ? intval($_GET['user_id']) : '';
	$status_filter = isset($_GET['status_filter']) ? sanitize_text_field($_GET['status_filter']) : 'todos';
	$pagina_atual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
	$itens_por_pagina = 10;
	$offset = ($pagina_atual - 1) * $itens_por_pagina;

	// Consulta do histórico particular do emissor
	$query_hist = "SELECT SQL_CALC_FOUND_ROWS * FROM wp_relatorios WHERE user_id = %d";
	$params_hist = [$current_user_id];

	if ($status_filter !== 'todos') {
		$query_hist .= " AND status = %s";
		$params_hist[] = $status_filter;
	}

	if (!empty($implicado_filter)) {
		$query_hist .= " AND implicado = %d";
		$params_hist[] = $implicado_filter;
	}

	$query_hist .= " ORDER BY data_criacao DESC LIMIT %d OFFSET %d";
	$params_hist[] = $itens_por_pagina;
	$params_hist[] = $offset;

	$historico = $wpdb->get_results($wpdb->prepare($query_hist, ...$params_hist));
	$total_registros_hist = $wpdb->get_var("SELECT FOUND_ROWS()");
	$total_paginas_hist = ceil($total_registros_hist / $itens_por_pagina);

	$total_pendentes = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM wp_relatorios WHERE status = 'pendente' AND user_id = %d",
			$current_user_id
		)
	);

	$abertas_hoje = (int) $wpdb->get_var("
    SELECT COUNT(*) 
    FROM wp_relatorios 
    WHERE DATE(hora_dia_ocorrencia) = CURDATE()
");

	$total_concluidos = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM wp_relatorios WHERE status = 'concluido' AND user_id = %d",
			$current_user_id
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
		<div class="kpis-container">
			<!-- Botão de adicionar nova ocorrência -->
			<div class="kpi-box">
				<a href="<?php echo esc_url(site_url('/ocorrencia/')); ?>" title="Adicionar nova ocorrência">
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
			<div class="kpi-box">
				<div class="kpi-number"><?php echo $abertas_hoje; ?></div>
				<div class="kpi-label">Abertas Hoje</div>
			</div>

		</div>

		<form method="GET" action="">
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

						<!-- Implicado -->
						<div class="col-md-3">
							<label for="user_id" class="form-label text-muted fw-medium">Implicado</label>
							<select id="user_id" name="user_id" class="form-select form-select-sm selectpicker shadow-sm" data-live-search="true" onchange="this.form.submit()">
								<option value="">Todos</option>
								<?php
								$usuarios = get_users();
								foreach ($usuarios as $usuario) {
									echo '<option value="' . esc_attr($usuario->ID) . '" ' . selected($implicado_filter, $usuario->ID, false) . '>' . esc_html($usuario->display_name) . '</option>';
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

		<?php if (!empty($historico)) : ?>
			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered">
					<thead class="thead-light">
						<tr>
							<th style="width: 25%;">Protocolo</th>
							<th style="width: 25%;">Data</th>
							<th style="width: 20%;">Hora</th>
							<th style="width: 30%;">Implicado</th>
							<th style="width: 25%;">Status</th>
							<th>Ações</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($historico as $relatorio):
							$implicado_info = get_userdata($relatorio->implicado);
							$nome_implicado = $implicado_info ? $implicado_info->display_name : 'Desconhecido';
							$dataFormatada = DateTime::createFromFormat('Y-m-d', $relatorio->data_criacao)->format('d/m/Y');
						?>
							<tr>
								<td><?php echo esc_html($relatorio->numero_protocolo); ?></td>
								<td><?php echo esc_html($dataFormatada); ?></td>
								<td><?php echo esc_html($relatorio->hora_criacao); ?></td>
								<td><?php echo esc_html($nome_implicado); ?></td>
								<td><?php echo esc_html($relatorio->status); ?></td>
								<td>
									<!-- Botão para visualizar detalhes da ocorrência -->
									<a href="<?php echo esc_url(add_query_arg([
													'relatorio_id' => $relatorio->id,
													'origem' => 'minhas-ocorrencias'
												], site_url('/detalhes-ocorrencia/'))); ?>" class="btn btn-primary">
										Detalhes
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<!-- Paginação -->
			<nav>
				<ul class="pagination justify-content-center">
					<?php for ($i = 1; $i <= $total_paginas_hist; $i++) : ?>
						<li class="page-item <?php echo ($i == $pagina_atual) ? 'active' : ''; ?>">
							<a class="page-link" href="<?php echo esc_url(add_query_arg(['pagina' => $i, 'status_filter' => $status_filter])); ?>">
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
add_shortcode('minhas_ocorrencias', 'minhas_ocorrencias_shortcode');
