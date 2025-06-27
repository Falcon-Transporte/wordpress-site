<?php
function formulario_tratativa_shortcode()
{

	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	ob_start();

	// Verifica se o ID do relatório está presente e é válido
	if (!isset($_GET['relatorio_id']) || !is_numeric($_GET['relatorio_id'])) {
		return '<div class="alert alert-danger">Relatório não encontrado ou ID inválido.</div>';
	}

	$analisado_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

	$relatorio_id = intval($_GET['relatorio_id']);
	global $wpdb;

	// Verifica se o relatório existe
	$relatorio = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_relatorios WHERE id = %d", $relatorio_id));
	if (!$relatorio) {
		return '<div class="alert alert-danger">Relatório não encontrado.</div>';
	}

	$tratativa_existente = $wpdb->get_row(
		$wpdb->prepare("SELECT * FROM wp_tratativas WHERE relatorio_id = %d", $relatorio_id)
	);

	// Inicializa valores padrão
	$nivel_gravidade = '';
	$numero_os = '';
	$numero_cat = '';
	$natureza_lesao = [];
	$partes_lesada = [];
	$causa_raiz = [];
	$descricao = '';
	$observacao = '';
	$medida_disciplinar = '';
	$modalidade = [];
	$descricao_ocorrido = '';


	// Se houver tratativa, preencher os valores para edição
	if ($tratativa_existente) {
		$nivel_gravidade = $tratativa_existente->nivel_gravidade;
		$natureza_lesao = maybe_unserialize($tratativa_existente->lesao);
		$partes_lesada = maybe_unserialize($tratativa_existente->parte_lesionada);
		$numero_cat  =  $tratativa_existente->numero_cat;
		$numero_os = $tratativa_existente->numero_os;
		// Correção: tratamento retrocompatível do campo causa_raiz
		$raw_causa_raiz = $tratativa_existente->causa_raiz;
		$causa_raiz = maybe_unserialize($raw_causa_raiz);
		if (!is_array($causa_raiz)) {
			$decoded = json_decode($raw_causa_raiz, true);
			if (is_array($decoded)) {
				$causa_raiz = $decoded;
			} else {
				$causa_raiz = !empty($raw_causa_raiz) ? [$raw_causa_raiz] : [];
			}
		}
		$modalidade = maybe_unserialize($tratativa_existente->modalidade);
		$descricao_ocorrido = $tratativa_existente->descricao_ocorrido;
		$descricao = $tratativa_existente->resolucao_ocorrido;
		$observacao = $tratativa_existente->observacao;
		$medida_disciplinar = $tratativa_existente->medida_disciplinar;
	}

	// Processa o formulário se ele foi enviado via POST
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Sanitiza os dados do formulário
		$nivel_gravidade = sanitize_text_field($_POST['gravidade']);
		$numero_os = sanitize_text_field($_POST['numero_os']);
		$numero_cat = sanitize_text_field($_POST['numero_cat']);
		$natureza_lesao = isset($_POST['natureza_lesao']) ? array_map('sanitize_text_field', $_POST['natureza_lesao']) : array();
		$partes_lesada = isset($_POST['partes_lesada']) ? array_map('sanitize_text_field', $_POST['partes_lesada']) : array();
		$causa_raiz = isset($_POST['causa_raiz']) ? array_map('sanitize_text_field', $_POST['causa_raiz']) : array();
		$descricao = sanitize_textarea_field($_POST['descricao']);
		$descricao_ocorrido = isset($_POST['descricao_ocorrido']) ? sanitize_textarea_field($_POST['descricao_ocorrido']) : '';
		$observacao = sanitize_textarea_field($_POST['observacao']);
		$medida_disciplinar = sanitize_text_field($_POST['medida_disciplinar']);
		$data_criacao = current_time('Y-m-d');
		$hora_criacao = current_time('H:i:s');
		$status = 'concluído'; // O status do relatório é alterado para "concluído"
		$responsavel_id = get_current_user_id(); // Usuário atual é o responsável
		$modalidade = isset($_POST['modalidade']) ? $_POST['modalidade'] : [];
		$modalidade_serialized = maybe_serialize($modalidade);


		if ($tratativa_existente) {
			$resultado = $wpdb->update(
				'wp_tratativas',
				array(
					'nivel_gravidade' => $nivel_gravidade,
					'lesao' => maybe_serialize($natureza_lesao),
					'parte_lesionada' => maybe_serialize($partes_lesada),
					'causa_raiz' => maybe_serialize($causa_raiz),
					'resolucao_ocorrido' => $descricao,
					'observacao' => $observacao,
					'status' => $status,
					'medida_disciplinar' => $medida_disciplinar,
					'numero_os' => $numero_os,
					'numero_cat' => $numero_cat,
					'modalidade' => $modalidade_serialized
				),
				array('id' => $tratativa_existente->id)
			);
		} else {
			// Insere a nova tratativa na tabela wp_tratativas
			$resultado = $wpdb->insert(
				'wp_tratativas',
				array(
					'relatorio_id' => $relatorio_id,
					'nivel_gravidade' => $nivel_gravidade,
					'lesao' => maybe_serialize($natureza_lesao),
					'parte_lesionada' => maybe_serialize($partes_lesada),
					'causa_raiz'   => maybe_serialize($causa_raiz),
					'resolucao_ocorrido' => $descricao,
					'data_criacao' => $data_criacao,
					'hora_criacao' => $hora_criacao,
					'responsavel_id' => $responsavel_id,
					'observacao' => $observacao,
					'status' => $status,
					'medida_disciplinar' => $medida_disciplinar,
					'numero_os' => $numero_os,
					'numero_cat' => $numero_cat,
					'modalidade' => $modalidade_serialized
				)
			);

			if ($resultado !== false) {
				// Atualiza o status do relatório e vincula a tratativa ao relatório
				$tratativa_id = $wpdb->insert_id;
				$wpdb->update(
					'wp_relatorios',
					array('status' => 'concluído', 'tratativa_id' => $tratativa_id),
					array('id' => $relatorio_id)
				);
			} else {
				echo '<div class="alert alert-danger">Erro ao salvar a tratativa. Por favor, tente novamente.</div>';
			}
		}

		if ($resultado !== false) {
			// Atualiza o status do relatório e vincula a tratativa ao relatório
			return '
<div style="text-align: center; padding: 15px; border-left: 5px solid #28a745; color: #155724; font-family: Arial, sans-serif; border-radius: 4px; margin-top: 15px;">
    <strong>✅ Sucesso!</strong> A tratativa foi ' . ($tratativa_existente ? 'atualizada' : 'cadastrada') . '. 
	<p>A RAAITO está disponível no painel de ocorrências.</p>

    <div style="margin-top: 15px;">
        <a href="' . esc_url(site_url('/gerenciar-ocorrencia/')) . '" style="text-decoration: none;">
            <button style="background-color: #28a745; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                Ver Painel de Ocorrências
            </button>
        </a>
        <a href="' . esc_url(add_query_arg(['relatorio_id' => $relatorio_id], site_url("/detalhes-ocorrencia/"))) . '" style="text-decoration: none;">
            <button style="background-color: #6c757d; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer;">
                Voltar
            </button>
        </a>
    </div>
</div>';
			// Redireciona para a página de relatórios com uma mensagem de sucesso
			wp_redirect(add_query_arg('tratativa_aplicada', 'true', site_url("/gerenciar-ocorrencia/" . ($analisado_id ? "?user_id=" . $analisado_id : ''))));

			exit;
		} else {
			echo '<div class="alert alert-danger">Erro ao salvar a tratativa. Por favor, tente novamente.</div>';
		}
	}

	// Mensagem de sucesso caso a tratativa tenha sido aplicada com sucesso
	if (isset($_GET['tratativa_aplicada']) && $_GET['tratativa_aplicada'] === 'true') {
		echo '<div class="alert alert-success">Tratativa aplicada com sucesso!</div>';
	}

	// Exibição do formulário
?>
	<div class="container mt-4">
		<form method="post" class="form-group">

			<div class="form-group">
				<label for="gravidade">Defina o nível de gravidade:</label>
				<select id="gravidade" name="gravidade" class="form-control selectpicker" required>
					<option value="LEVE" <?php selected($nivel_gravidade, 'LEVE'); ?>>LEVE</option>
					<option value="GRAVE" <?php selected($nivel_gravidade, 'GRAVE'); ?>>GRAVE</option>
					<option value="GRAVÍSSIMO" <?php selected($nivel_gravidade, 'GRAVÍSSIMO'); ?>>GRAVÍSSIMO</option>
					<option value="DANOS MATERIAIS" <?php selected($nivel_gravidade, 'DANOS MATERIAIS'); ?>>DANOS MATERIAIS</option>
				</select>
			</div>

			<?php
			if (!$tratativa_existente) {
			?>
				<div class="form-group">
					<label for="houve_lesao">Houve Lesão?</label>
					<select id="houve_lesao" class="form-control">
						<option value="">Selecione</option>
						<option value="sim">Sim</option>
						<option value="nao">Não</option>
					</select>
				</div>
			<?php
			}
			?>


			<?php
			if (empty($natureza_lesao) && empty($partes_lesada)) {
			?>
				<div id="campos_lesao" style="display: none;">
				<?php
			} else {
				?>
					<div id="campos_lesao" style="display: block;">
					<?php
				}
					?>
					<div class="form-group">
						<label for="natureza_lesao">Natureza da Lesão:</label>
						<select id="natureza_lesao" name="natureza_lesao[]" class="form-control selectpicker" multiple data-live-search="true">
							<?php
							$opcoes_lesao = ["FRATURA", "DISTENSÃO/TORÇÃO", "CONTUSÃO", "CORTE/PERFURAÇÃO", "ESCORIAÇÃO", "IRRITAÇÃO", "LOMBALGIA", "QUEIMADURA", "OUTROS"];
							foreach ($opcoes_lesao as $opcao) {
								$selected = in_array($opcao, $natureza_lesao) ? 'selected' : '';
								echo "<option value=\"$opcao\" $selected>$opcao</option>";
							}
							?>
						</select>
					</div>

					<div class="form-group">
						<label for="partes_lesada">Partes do corpo lesadas:</label>
						<select id="partes_lesada" name="partes_lesada[]" class="form-control selectpicker" multiple data-live-search="true">
							<?php
							$opcoes_partes = ["CABEÇA", "OLHOS", "VIAS RESPIRATÓRIAS", "TRONCO", "BRAÇO", "PERNA", "MÃOS", "PÉS", "OUTROS"];
							foreach ($opcoes_partes as $opcao) {
								$selected = in_array($opcao, $partes_lesada) ? 'selected' : '';
								echo "<option value=\"$opcao\" $selected>$opcao</option>";
							}
							?>
						</select>
					</div>
					</div>

					<?php
					if (!$tratativa_existente) {
					?>
						<div class="form-group">
							<label for="houve_cat">Houve CAT?</label>
							<select id="houve_cat" class="form-control">
								<option value="">Selecione</option>
								<option value="sim">Sim</option>
								<option value="nao">Não</option>
							</select>
						</div>

					<?php
					}
					?>


					<?php
					if (empty($numero_cat)) {
					?>
						<div id="campos_cat" style="display: none;">
						<?php
					} else {
						?>
							<div id="campos_cat" style="display: block;">
							<?php
						}

							?>
							<div class="form-group">
								<label for="numero_cat">Número CAT</label>
								<input type="text" class="form-control" name="numero_cat" placeholder="Número CAT" value="<?php echo esc_attr($numero_cat); ?>">
							</div>
							</div>

							<?php
							if (!$tratativa_existente) {
							?>
								<div class="form-group">
									<label for="houve_os">Houve OS (Ordem de serviço)?</label>
									<select id="houve_os" class="form-control">
										<option value="">Selecione</option>
										<option value="sim">Sim</option>
										<option value="nao">Não</option>
									</select>
								</div>

							<?php
							}
							?>

							<?php
							if (empty($numero_os)) {
							?>
								<div id="campos_os" style="display: none;">
								<?php
							} else {
								?>
									<div id="campos_os" style="display: block;">
									<?php
								}
									?>
									<div class="form-group">

										<label for="numero_os">Número OS (Ordem de Serviço)</label>
										<input type="text" class="form-control" name="numero_os" placeholder="Numero OS" value="<?php echo esc_attr($numero_os); ?>">
									</div>
									</div>

									<script>
										document.getElementById('houve_cat').addEventListener('change', function() {
											const campoCat = document.getElementById('campos_cat');
											if (this.value === 'sim') {
												campoCat.style.display = 'block';
											} else {
												campoCat.style.display = 'none';
											}
										});

										document.getElementById('houve_os').addEventListener('change', function() {
											const campoOS = document.getElementById('campos_os');
											if (this.value === 'sim') {
												campoOS.style.display = 'block';
											} else {
												campoOS.style.display = 'none';
											}
										});

										// Lógica para exibir/ocultar os campos de lesão
										document.getElementById('houve_lesao').addEventListener('change', function() {
											const camposLesao = document.getElementById('campos_lesao');
											if (this.value === 'sim') {
												camposLesao.style.display = 'block';
											} else {
												camposLesao.style.display = 'none';
											}
										});
									</script>

									<!-- Causa Raiz -->
									<div class="form-group">
										<label for="causa_raiz">Causa Raiz:</label>
										<select id="causa_raiz" name="causa_raiz[]" class="form-control selectpicker" multiple data-live-search="true" required>
											<?php
											$opcao_causa_raiz = ["NEGLIGÊNCIA DE ENCARREGADOS/SUPERVISÃO", "NEGLIGÊNCIA FUNCIONÁRIOS", "IMPRUDÊNCIA", "IMPERÍCIA", "CAUSA/FATOR EXTERNO", "COLISÃO", "FALHA MECÂNICA/ELÉTRICA VEÍCULO", "NEGLIGÊNCIA", "OCORRÊNCIA COM ALUNOS/MONITORES/EMEBS", "OUTROS"];
											foreach ($opcao_causa_raiz as $opcao) {
												$selected = in_array($opcao, $causa_raiz) ? 'selected' : '';
												echo "<option value=\"$opcao\" $selected>$opcao</option>";
											}
											?>
										</select>
									</div>

									<div class="form-group">
										<label for="medida_disciplinar">Houve Medida Disciplinar?</label>
										<select id="medida_disciplinar" name="medida_disciplinar" class="form-control">
											<?php
											if (!$tratativa_existente) {
											?>
												<option value="" disabled selected>Selecione</option>
											<?php
											}
											?>
											<option value="SIM" <?php selected($medida_disciplinar, 'sim'); ?>>Sim</option>
											<option value="NÃO" <?php selected($medida_disciplinar, 'nao'); ?>>Não</option>
										</select>
									</div>

									<!-- Modalidade -->
									<div class="form-group">
										<label for="modalidade">Selecione uma ou mais modalidades</label>
										<select id="modalidade" name="modalidade[]" class="form-control selectpicker" multiple data-live-search="true" required>
											<option value="" disabled <?php echo empty($modalidades_selecionadas) ? 'selected' : ''; ?>>Selecione</option>

											<?php
											$opcoes_modalidade = [
												"OCORRÊNCIA EMEB (MOTORISTA, MONITORES, ALUNOS, ETC.)",
												"ACIDENTE DE PERCURSO - TRÂNSITO (GARAGEM ATÉ EMEB/RETORNO)",
												"ACIDENTE DE TRABALHO (EXCETO ACIDENTE DE PERCURSO)",
												"ACIDENTE DE TRAJETO FUNCIONÁRIO",
												"DIREÇÃO PERIGOSA NO TRÂNSITO",
												"INCIDENTE (OCORRÊNCIA QUE PODERIA GERAR ACIDENTE DE TRABALHO)",
												"MANUTENÇÃO E REPARO DE VEÍCULO",
												"OUTROS"
											];

											foreach ($opcoes_modalidade as $opcao) {
												$selected = in_array($opcao, $modalidade) ? 'selected' : '';
												echo '<option value="' . esc_attr($opcao) . '" ' . $selected . '>' . esc_html($opcao) . '</option>';
											}
											?>
										</select>
									</div>
									
									<!-- Descrição -->
									<div class="form-group">
										<label for="descricao_ocorrido">Descrição do Ocorrido</label>
										<textarea name="descricao_ocorrido" class="form-control" placeholder="Descrição" required><?= esc_textarea($descricao_ocorrido) ?></textarea>
									</div>

									<!-- Descrição -->
									<div class="form-group">
										<label for="descricao">AÇÕES APÓS O OCORRIDO, OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS:</label>
										<textarea id="descricao" name="descricao" class="form-control" rows="5" required><?= htmlspecialchars_decode(stripslashes($descricao)) ?></textarea>
									</div>

									<!-- Observacao -->
									<div class="form-group">
										<label for="observacao">OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS:</label>
										<textarea id="observacao" name="observacao" class="form-control" rows="5" required><?= htmlspecialchars_decode(stripslashes($observacao)) ?></textarea>
									</div>

									<!-- Botões de Ação -->
									<div class="d-flex justify-content-between">
										<button type="submit" class="btn btn-primary"><?php echo $tratativa_existente ? 'Atualizar Tratativa' : 'Aplicar Tratativa'; ?></button>
										<a href="javascript:history.back()" class="btn btn-secondary">Cancelar</a>
									</div>
		</form>
	</div>
<?php

	return ob_get_clean(); // Retorna o conteúdo do buffer
}
add_shortcode('formulario_tratativa', 'formulario_tratativa_shortcode');
