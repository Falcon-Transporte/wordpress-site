<?php
function relatorio_formulario_shortcode()
{

	$current_user = wp_get_current_user();
	$wp_roles = wp_roles();

	$cargo_emissor = 'Cargo desconhecido';

	if (!empty($current_user->roles)) {
		$cargos_legiveis = array_map(function ($role_slug) use ($wp_roles) {
			return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
		}, $current_user->roles);

		$cargo_emissor = implode(', ', $cargos_legiveis);
	}
	$usuarios = get_users();

	$pagina_anterior = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : home_url();

	global $wpdb;

	$editar_id = isset($_GET['editar_id']) ? intval($_GET['editar_id']) : 0;
	$dados_ocorrencia = $editar_id ? $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM wp_relatorios WHERE id = %d",
		$editar_id
	)) : null;

	$data_ocorrido = $dados_ocorrencia ? $dados_ocorrencia->data_criacao : '';
	$hora_ocorrido = $dados_ocorrencia ? $dados_ocorrencia->hora_criacao : '';

	$cargo_implicado = $dados_ocorrencia ? $dados_ocorrencia->cargo_implicado : '';

	$modalidades_selecionadas = [];

	if ($dados_ocorrencia && !empty($dados_ocorrencia->modalidade)) {
		$modalidades_selecionadas = unserialize($dados_ocorrencia->modalidade);
	}

	$imagens_existentes = [];
	if (isset($_GET['editar_id'])) {
		$editar_id = intval($_GET['editar_id']);

		$imagens_existentes = $wpdb->get_results(
			$wpdb->prepare("SELECT imagem_id FROM wp_relatorio_imagens WHERE relatorio_id = %d", $editar_id)
		);
	}

	$descricao_ocorrencia = $dados_ocorrencia ? esc_textarea($dados_ocorrencia->descricao) : '';


	ob_start(); // Inicia o buffer de saída

?>
	<form class="custom-form-container container" method="post" action="" enctype="multipart/form-data">
		<div class="form-row">
			<!-- Nome do Emissor -->
			<div class="form-group col-md-6">
				<label for="nome_emissor">Nome do Emissor</label>
				<input type="text" class="form-control" name="nome_emissor" placeholder="Nome do Emissor" value="<?php echo esc_attr($current_user->display_name); ?>" disabled>
			</div>

			<!-- Cargo do Emissor -->
			<div class="form-group col-md-6">
				<label for="cargo_emissor">Cargo do Emissor</label>
				<input type="text" class="form-control" name="cargo_emissor" placeholder="Cargo do Emissor" value="<?php echo esc_attr($cargo_emissor); ?>" disabled>
			</div>
		</div>

		<div class="form-row">
			<!-- Data do Ocorrido -->
			<div class="form-group col-md-6">
				<label for="data_ocorrido">Data do Ocorrido</label>
				<?php $hoje = date('Y-m-d'); ?>
				<input type="date" class="form-control" name="data" placeholder="Data do Ocorrido" value="<?php echo esc_attr($data_ocorrido); ?>" max="<?php echo $hoje; ?>" required>
			</div>

			<!-- Hora do Ocorrido -->
			<div class="form-group col-md-6">
				<label for="hora_ocorrido">Hora do Ocorrido</label>
				<input type="time" class="form-control" name="hora" placeholder="Hora do Ocorrido" id="hora_ocorrido" value="<?php echo esc_attr($hora_ocorrido); ?>" required>

			</div>
		</div>

		<!-- Local -->
		<div class="form-group">
			<label for="local">Selecione um local</label>
			<select id="local" name="local" class="form-control" required>
				<?php
				$locais = [
					'NÃO APLICA',
					'ADENOR BONIFÁCIO',
					'ALCEU MAGALHÃES COUTINHO - PROF',
					'ALFREDO GONÇALVES DA SILVA - VICE',
					'ALI ALI',
					'ANTONIA CICONE - DONA',
					'ARISTIDES JACOB ALVARES',
					'CHARLES HENRY TYLER TOWNSEND - DR',
					'CLARINDA DA CONCEIÇÃO',
					'ESCRITÓRIO',
					'FLORO DA SILVA',
					'GARAGEM NOVA',
					'GARAGEM SECRETARIA DE OBRAS',
					'GUILHERME DONIZETE DOS SANTOS',
					'HELENA SGARB',
					'ISABEL ALVES DO PRADO',
					'ÍTALO ADAMI',
					'JOÃO MARQUES - VER',
					'JOAQUIM PERPÉTUO',
					'JOSÉ MARINHO FERREIRA',
					'JOSÉ PIACENTINI',
					'JOSEFA COSTA DE SOUZA MOURA',
					'JURACI MARCHIONI - VICE PREF.',
					'LEOLINO DOS SANTOS - VER',
					'MANUTENÇÃO',
					'MARIA EMÍLIA DE MORAES NASCIMENTO',
					'NOSSA SENHORA DAS GRAÇAS - PARQUE',
					'ORLANDO BENTO',
					'ROSELI APARECIDA MENDES I - PROF.',
					'ROSELI APARECIDA MENDES II - PROF',
					'SHOZAYEMON SETOKUCHI',
					'TRÂNSITO'
				];

				$local_salvo = $dados_ocorrencia ? $dados_ocorrencia->local : '';

				foreach ($locais as $local_opcao) {
					$selected = ($local_opcao === $local_salvo) ? 'selected' : '';
					echo "<option value=\"" . esc_attr($local_opcao) . "\" $selected>" . esc_html($local_opcao) . "</option>";
				}
				?>
			</select>
		</div>

		<div class="form-group">
			<label for="com_quem_ocorreu">Com quem Ocorreu</label>
			<select id="com_quem_ocorreu" name="com_quem_ocorreu" class="form-control selectpicker" data-live-search="true" required>
				<option value="">Selecione com quem ocorreu</option>
				<option value="NÃO APLICA">NÃO APLICA</option>
				<?php
				// Prioriza edição, depois parâmetro da URL
				$implicado_id = $dados_ocorrencia ? $dados_ocorrencia->implicado : (isset($_GET['implicado']) ? intval($_GET['implicado']) : '');

				foreach ($usuarios as $usuario) :
					$selected = ($implicado_id == $usuario->ID) ? 'selected' : '';
				?>
					<option value="<?php echo esc_attr($usuario->ID); ?>" <?php echo $selected; ?>>
						<?php echo esc_html($usuario->display_name); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<!-- Campo oculto para garantir envio -->
			<input type="hidden" name="com_quem_ocorreu_hidden" id="com_quem_ocorreu_hidden" value="<?php echo esc_attr($implicado_id); ?>">
		</div>

		<script>
			document.addEventListener("DOMContentLoaded", function() {
				var selectField = document.getElementById("com_quem_ocorreu");
				var hiddenField = document.getElementById("com_quem_ocorreu_hidden");

				// Pegamos o valor já preenchido no campo oculto (vindo do PHP)
				var implicadoId = hiddenField.value;

				if (implicadoId && selectField.querySelector(`option[value="${implicadoId}"]`)) {
					selectField.value = implicadoId;

					// Se veio da URL e não estamos editando, desabilita
					<?php if (!$dados_ocorrencia && isset($_GET['implicado'])) : ?>
						selectField.disabled = true;
					<?php endif; ?>
				} else {
					selectField.disabled = false;
				}

				function updateHiddenField() {
					hiddenField.value = selectField.value.trim() ? selectField.value : "NÃO APLICA";
				}

				updateHiddenField();
				selectField.addEventListener("change", updateHiddenField);
			});
		</script>

		<!-- Cargo do Implicado -->
		<div class="form-group">
			<label for="cargo_implicado">Cargo do Implicado</label>
			<input type="text" id="cargo_implicado" name="cargo_implicado" class="form-control" placeholder="Cargo do Implicado"
				value="<?php echo esc_attr($cargo_implicado); ?>" required disabled>

			<input type="hidden" name="cargo_implicado" id="cargo_implicado_hidden" value="<?php echo esc_attr($cargo_implicado); ?>">

			<script type="text/javascript">
				jQuery(document).ready(function($) {
					// Verifica se estamos em modo edição ou se veio implicado na URL
					var implicadoId = $('#com_quem_ocorreu_hidden').val();

					<?php if (!$dados_ocorrencia && isset($_GET['implicado'])) : ?>
						// Se for apenas pela URL (não edição), busca o cargo via AJAX
						if (implicadoId) {
							preencherCargoImplicado(implicadoId);
						}
					<?php endif; ?>

					// Quando o campo "Com quem Ocorreu" muda
					$('#com_quem_ocorreu').on('change', function() {
						var userId = $(this).val();
						if (userId == " ") {
							$('input[name="cargo_implicado"]').val(" ");
							$('#cargo_implicado_hidden').val(" ");
						} else {
							preencherCargoImplicado(userId);
						}
					});

					// Função AJAX para preencher o cargo
					function preencherCargoImplicado(userId) {
						$.ajax({
							url: '<?php echo admin_url('admin-ajax.php'); ?>',
							type: 'POST',
							data: {
								action: 'get_user_cargo',
								user_id: userId
							},
							success: function(response) {
								$('#cargo_implicado').val(response);
								$('#cargo_implicado_hidden').val(response);
							}
						});
					}
				});
			</script>

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
					$selected = in_array($opcao, $modalidades_selecionadas) ? 'selected' : '';
					echo '<option value="' . esc_attr($opcao) . '" ' . $selected . '>' . esc_html($opcao) . '</option>';
				}
				?>
			</select>
		</div>


		<input type="file" id="imagem_ocorrido" style="display:none;" />
		<div id="upload-area" style="border: 1px dashed #ccc; padding: 20px; cursor: pointer;">
			Clique ou arraste imagens aqui
		</div>
		<div id="preview-container" style="display: flex; gap: 10px; margin-top: 10px; margin-bottom: 20px;">
			<?php if (!empty($imagens_existentes)): ?>
				<?php foreach ($imagens_existentes as $img):
					$url = wp_get_attachment_image_url($img->imagem_id, 'thumbnail');
				?>
					<div class="preview-box" data-imagem-id="<?= esc_attr($img->imagem_id) ?>" style="position: relative; display: flex; flex-direction: column; align-items: center;">
						<img src="<?= esc_url($url) ?>" style="width: 100px; height: 100px; object-fit: cover; border: 1px solid #ccc; border-radius: 4px;" />
						<button class="remover-imagem-salva" style="margin-top: 5px; padding: 2px 6px; font-size: 12px; cursor: pointer;">Remover</button>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<script>
			const fileInput = document.getElementById("imagem_ocorrido");
			const uploadArea = document.getElementById("upload-area");
			const previewContainer = document.getElementById("preview-container");

			let selectedCount = document.querySelectorAll('.preview-box').length;
			console.log("Imagens atuais:", selectedCount);

			function atualizarContador() {
				selectedCount = document.querySelectorAll('.preview-box').length;
			}

			uploadArea.addEventListener("click", () => fileInput.click());

			uploadArea.addEventListener("dragover", (e) => {
				e.preventDefault();
				uploadArea.style.background = "#e9ecef";
			});

			uploadArea.addEventListener("dragleave", () => {
				uploadArea.style.background = "";
			});

			uploadArea.addEventListener("drop", (e) => {
				e.preventDefault();
				uploadArea.style.background = "";
				handleFiles(e.dataTransfer.files);
			});

			fileInput.addEventListener("change", (e) => handleFiles(e.target.files));

			function handleFiles(files) {
				const allowedTypes = ["image/jpeg", "image/png", "image/gif"];

				for (let file of files) {
					if (selectedCount >= 3) {
						alert("Limite de 3 imagens atingido.");
						break;
					}

					if (file.size > 2 * 1024 * 1024) {
						alert(`"${file.name}" é muito grande. Máximo 2MB.`);
						continue;
					}

					if (!allowedTypes.includes(file.type)) {
						alert(`"${file.name}" tem formato inválido.`);
						continue;
					}

					uploadImagem(file);
				}
			}

			function uploadImagem(file) {
				const formData = new FormData();
				formData.append("action", "salvar_imagem_sessao");
				formData.append("imagem", file);

				fetch("/wp-admin/admin-ajax.php", {
						method: "POST",
						body: formData,
						credentials: "same-origin",
					})
					.then((res) => res.json())
					.then((data) => {
						if (data.success) {
							let nomeArquivo = data.data.nome_arquivo;
							displayPreview(file, nomeArquivo);
							atualizarContador();
						} else {
							alert("Erro ao enviar: " + data.data);
						}
					})
					.catch((error) => {
						console.error("Erro:", error);
					});
			}

			function displayPreview(file, nomeArquivo) {
				const reader = new FileReader();
				reader.onload = function(e) {
					const previewBox = document.createElement("div");
					previewBox.setAttribute("data-nome-arquivo", nomeArquivo);
					previewBox.classList.add("preview-box");
					previewBox.style.position = "relative";
					previewBox.style.display = "flex";
					previewBox.style.flexDirection = "column";
					previewBox.style.alignItems = "center";

					const img = document.createElement("img");
					img.src = e.target.result;
					img.style.width = "100px";
					img.style.height = "100px";
					img.style.objectFit = "cover";
					img.style.border = "1px solid #ccc";
					img.style.borderRadius = "4px";

					const removeBtn = document.createElement("button");
					removeBtn.textContent = "Remover";
					removeBtn.style.marginTop = "5px";
					removeBtn.style.padding = "2px 6px";
					removeBtn.style.fontSize = "12px";
					removeBtn.style.cursor = "pointer";

					removeBtn.addEventListener("click", (e) => {
						e.preventDefault();
						if (!confirm("Tem certeza que deseja remover esta imagem?")) return;

						const nomeArquivoUnique = previewBox.getAttribute("data-nome-arquivo");
						fetch('/wp-admin/admin-ajax.php', {
								method: 'POST',
								credentials: 'same-origin',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded'
								},
								body: new URLSearchParams({
									action: 'remover_imagem_sessao',
									nome_arquivo: nomeArquivoUnique
								})
							})
							.then(res => res.json())
							.then(data => {
								if (data.success) {
									previewBox.remove();
									atualizarContador();
								} else {
									alert("Erro ao remover imagem da sessão.");
								}
							});
					});

					previewBox.appendChild(img);
					previewBox.appendChild(removeBtn);
					previewContainer.appendChild(previewBox);
				};
				reader.readAsDataURL(file);
			}

			document.querySelectorAll('.remover-imagem-salva').forEach(btn => {
				btn.addEventListener('click', function(e) {
					e.preventDefault();
					const previewBox = this.closest('.preview-box');
					const imagemId = previewBox.getAttribute('data-imagem-id');

					if (confirm("Tem certeza que deseja remover esta imagem?")) {
						fetch('/wp-admin/admin-ajax.php', {
								method: 'POST',
								credentials: 'same-origin',
								headers: {
									'Content-Type': 'application/x-www-form-urlencoded'
								},
								body: new URLSearchParams({
									action: 'remover_imagem_salva',
									imagem_id: imagemId
								})
							})
							.then(res => res.json())
							.then(data => {
								if (data.success) {
									previewBox.remove();
									atualizarContador();
								} else {
									alert("Erro ao remover imagem.");
								}
							});
					}
				});
			});
		</script>

		<!-- Descrição -->
		<div class="form-group">
			<label for="descricao">Descrição do Ocorrido</label>
			<textarea name="descricao" class="form-control" placeholder="Descrição" required><?= esc_textarea($descricao_ocorrencia) ?></textarea>
		</div>


		<div class="form-group">
			<a href="<?php echo esc_url($pagina_anterior); ?>" class="btn btn-secondary">Voltar</a>
		</div>

		<input type="hidden" name="previous_page" value="<?php echo isset($pagina_anterior) ? esc_url($pagina_anterior) : ''; ?>">

		<!-- Submit -->
		<div class="form-group">
			<input type="submit" name="enviar_relatorio" class="btn btn-primary" value="Enviar Relatório">
		</div>

		<?php if ($editar_id): ?>
			<input type="hidden" name="editar_id" value="<?php echo esc_attr($editar_id); ?>">
		<?php endif; ?>

	</form>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const form = document.querySelector('form');
			const dataInput = document.getElementById('data_ocorrido') || document.querySelector('[name="data"]');
			const horaInput = document.getElementById('hora_ocorrido') || document.querySelector('[name="hora"]');

			if (!form || !dataInput || !horaInput) return;

			form.addEventListener('submit', function(e) {
				const dataSelecionada = new Date(dataInput.value);
				const hoje = new Date();
				hoje.setHours(0, 0, 0, 0);

				const agora = new Date();
				const [horaStr, minutoStr] = horaInput.value.split(':');
				const horaSelecionada = new Date();
				horaSelecionada.setHours(parseInt(horaStr), parseInt(minutoStr), 0, 0);

				const mesmoDia = dataSelecionada.toDateString() === hoje.toDateString();

				if (mesmoDia && horaSelecionada > agora) {
					e.preventDefault();
					alert("A data de hoje não permite um horário maior que o atual.");
				}
			});
		});
	</script>

<?php

	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	$image_ids = [];

	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_relatorio'])) {
		global $wpdb;

		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$image_ids = [];

		$editar_id = isset($_POST['editar_id']) ? intval($_POST['editar_id']) : 0;

		if (isset($_SESSION['imagens_ocorrencia']) && is_array($_SESSION['imagens_ocorrencia'])) {

			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			foreach ($_SESSION['imagens_ocorrencia'] as $file) {
				$sideload = [
					'name'     => $file['name'],
					'type'     => $file['type'],
					'tmp_name' => $file['tmp_name'],
					'error'    => 0,
					'size'     => $file['size']
				];

				$upload_id = media_handle_sideload($sideload, 0);

				if (!is_wp_error($upload_id)) {
					$image_ids[] = $upload_id;
				} else {
					error_log("Erro no upload da imagem: " . $upload_id->get_error_message());
				}
			}

			foreach ($_SESSION['imagens_ocorrencia'] as $file) {
				@unlink($file['tmp_name']);
			}
			unset($_SESSION['imagens_ocorrencia']);
		}

		$data_criacao = isset($_POST['data']) ? sanitize_text_field($_POST['data']) : '';
		$hora_criacao = isset($_POST['hora']) ? sanitize_text_field($_POST['hora']) : '';
		$local = isset($_POST['local']) ? sanitize_text_field($_POST['local']) : '';
		$implicado = !empty($_POST['com_quem_ocorreu']) ? sanitize_text_field($_POST['com_quem_ocorreu']) : (!empty($_POST['com_quem_ocorreu_hidden']) ? sanitize_text_field($_POST['com_quem_ocorreu_hidden']) : 'NÃO APLICA');
		$cargo_implicado = isset($_POST['cargo_implicado']) ? sanitize_text_field($_POST['cargo_implicado']) : '';
		$descricao = isset($_POST['descricao']) ? sanitize_textarea_field($_POST['descricao']) : '';
		$modalidade = isset($_POST['modalidade']) ? $_POST['modalidade'] : [];
		$modalidade_serialized = maybe_serialize($modalidade);

		$erro_hora = false;

		$convert_data = DateTime::createFromFormat('Y-m-d', $data_criacao);
		$data_hoje = new DateTime(current_time('Y-m-d'));
		$hora_atual = new DateTime(current_time('H:i'));

		if ($convert_data && $data_criacao === $data_hoje->format('Y-m-d')) {
			// Valida a hora somente se a data for hoje
			$hora_ocorrida = DateTime::createFromFormat('H:i', $hora_criacao);
			if ($hora_ocorrida && $hora_ocorrida > $hora_atual) {
				$erro_hora = true;
			}
		}

		if ($erro_hora) {
			echo '<div class="alert alert-danger">A hora do ocorrido não pode ser maior que a hora atual.</div>';
			return ob_get_clean(); // Interrompe o envio do relatório
		}

		if ($editar_id) {
			// Modo edição: atualizar relatório
			$wpdb->update(
				'wp_relatorios',
				[
					'data_criacao' => $data_criacao,
					'hora_criacao' => $hora_criacao,
					'local' => $local,
					'implicado' => $implicado,
					'cargo_implicado' => $cargo_implicado,
					'modalidade' => $modalidade_serialized,
					'descricao' => $descricao,
				],
				['id' => $editar_id]
			);

			foreach ($image_ids as $image_id) {
				$wpdb->insert('wp_relatorio_imagens', [
					'relatorio_id' => $editar_id,
					'imagem_id'    => $image_id
				]);
			}

			wp_redirect(add_query_arg(['editado' => 'true'], '/sucesso-ocorrencia/'));
			exit;
		} else {
			// Modo criação
			$data_atual = current_time('Ymd');
			$ultimo_protocolo = $wpdb->get_var($wpdb->prepare(
				"SELECT numero_protocolo FROM wp_relatorios WHERE numero_protocolo LIKE %s ORDER BY numero_protocolo DESC LIMIT 1",
				$data_atual . '%'
			));
			$numero_iterado = $ultimo_protocolo ? (int)substr($ultimo_protocolo, -4) + 1 : 1;
			$numero_protocolo = $data_atual . sprintf('%04d', $numero_iterado);

			$hora_dia_ocorrencia = date('Y-m-d H:i:s');

			$result = $wpdb->insert(
				'wp_relatorios',
				[
					'user_id' => get_current_user_id(),
					'data_criacao' => $data_criacao,
					'hora_criacao' => $hora_criacao,
					'local' => $local,
					'implicado' => $implicado,
					'cargo_implicado' => $cargo_implicado,
					'modalidade' => $modalidade_serialized,
					'descricao' => $descricao,
					'numero_protocolo' => $numero_protocolo,
					'status' => 'pendente',
					'hora_dia_ocorrencia' => $hora_dia_ocorrencia
				]
			);

			if ($result !== false) {
				$relatorio_id = $wpdb->insert_id;

				foreach ($image_ids as $image_id) {
					$wpdb->insert('wp_relatorio_imagens', [
						'relatorio_id' => $relatorio_id,
						'imagem_id'    => $image_id
					]);
				}

				wp_redirect(add_query_arg(['success' => 'true'], '/sucesso-ocorrencia/'));
				exit;
			} else {
				wp_redirect($pagina_anterior);
				exit;
			}
		}
	}

	return ob_get_clean();
}
add_shortcode('relatorio_formulario', 'relatorio_formulario_shortcode');


add_action('wp_ajax_salvar_imagem_sessao', 'salvar_imagem_sessao');
add_action('wp_ajax_nopriv_salvar_imagem_sessao', 'salvar_imagem_sessao');

function salvar_imagem_sessao()
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	if (!isset($_SESSION['imagens_ocorrencia'])) {
		$_SESSION['imagens_ocorrencia'] = [];
	}

	if (!empty($_FILES['imagem'])) {
		$file = $_FILES['imagem'];
		$allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
		$file_mime_type = mime_content_type($file['tmp_name']);

		if (!in_array($file_mime_type, $allowed_mime_types)) {
			wp_send_json_error('Tipo de arquivo não permitido.');
		}

		if (count($_SESSION['imagens_ocorrencia']) >= 3) {
			wp_send_json_error('Limite de 3 imagens atingido.');
		}

		$upload_dir = wp_upload_dir();
		$temp_dir = $upload_dir['basedir'] . '/temp';
		if (!file_exists($temp_dir)) {
			mkdir($temp_dir, 0755, true);
		}

		$unique_name = uniqid() . '-' . sanitize_file_name($file['name']);
		$target = $temp_dir . '/' . $unique_name;

		if (move_uploaded_file($file['tmp_name'], $target)) {
			$_SESSION['imagens_ocorrencia'][] = [
				'name'     => $unique_name,
				'tmp_name' => $target,
				'type'     => $file['type'],
				'size'     => $file['size'],
				'error'    => 0
			];
			wp_send_json_success([
				'mensagem' => "Imagem salva na sessão!",
				'nome_arquivo' => $unique_name
			]);
		} else {
			wp_send_json_error('Erro ao mover arquivo.');
		}
	}

	wp_send_json_error('Nenhuma imagem enviada.');
}

add_action('wp_ajax_remover_imagem_salva', 'remover_imagem_salva');

function remover_imagem_salva()
{
	global $wpdb;

	if (!current_user_can('edit_posts')) {
		wp_send_json_error('Permissão negada.');
	}

	$imagem_id = isset($_POST['imagem_id']) ? intval($_POST['imagem_id']) : 0;

	// Exclui da tabela de relacionamento
	$wpdb->delete('wp_relatorio_imagens', ['imagem_id' => $imagem_id]);

	// Exclui o anexo do WP
	wp_delete_attachment($imagem_id, true);

	wp_send_json_success('Imagem removida.');
}

function get_user_cargo_ajax()
{
	if (isset($_POST['user_id'])) {
		$user_id = intval($_POST['user_id']);
		$user_info = get_userdata($user_id);

		if ($user_info && !empty($user_info->roles)) {
			$wp_roles = wp_roles(); // Recupera os papéis
			$cargos_legiveis = array_map(function ($role_slug) use ($wp_roles) {
				return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
			}, $user_info->roles);

			$cargo = implode(', ', $cargos_legiveis);
			echo esc_html($cargo);
		} else {
			echo 'Cargo desconhecido';
		}
	}
	wp_die();
}
add_action('wp_ajax_get_user_cargo', 'get_user_cargo_ajax');

function remover_imagem_sessao()
{
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_start();
	}

	if (!isset($_SESSION['imagens_ocorrencia'])) {
		wp_send_json_error('Nenhuma imagem na sessão.');
	}

	$nome_arquivo = sanitize_file_name($_POST['nome_arquivo'] ?? '');

	// Verificar se o nome da imagem existe na sessão
	foreach ($_SESSION['imagens_ocorrencia'] as $key => $imagem) {
		if ($imagem['name'] === $nome_arquivo) {
			// Deleta o arquivo da pasta temporária
			unlink($imagem['tmp_name']); // Remove o arquivo temporário do servidor
			unset($_SESSION['imagens_ocorrencia'][$key]);
			$_SESSION['imagens_ocorrencia'] = array_values($_SESSION['imagens_ocorrencia']); // Reindexa o array
			wp_send_json_success('Imagem removida da sessão.');
		}
	}

	wp_send_json_error('Imagem não encontrada na sessão.');
}
add_action('wp_ajax_remover_imagem_sessao', 'remover_imagem_sessao');
