<?php

function formulario_cadastro_usuario_shortcode()
{
	ob_start();

	$editar_id = isset($_GET['editar_id']) ? intval($_GET['editar_id']) : null;
	$usuario = $editar_id ? get_userdata($editar_id) : null;
	$modo_edicao = $usuario instanceof WP_User;

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastro_usuario_nonce']) && wp_verify_nonce($_POST['cadastro_usuario_nonce'], 'cadastro_usuario')) {
		$nome_completo = sanitize_text_field($_POST['nome_completo']);
		$cargo         = sanitize_text_field($_POST['cargo']);
		$email         = sanitize_email($_POST['email']);
		$cpf           = sanitize_text_field($_POST['cpf']);
		$senha         = $_POST['senha'];
		$user_status = $_POST['user_status'];

		$erros = [];

		if (!$modo_edicao && email_exists($email)) {
			$erros[] = 'Este e-mail já está em uso.';
		}

		$usuarios = get_users([
			'meta_key' => 'cpf',
			'meta_value' => $cpf,
			'exclude' => $modo_edicao ? [$editar_id] : []
		]);

		if (!empty($usuarios)) {
			$erros[] = 'Este CPF já está cadastrado.';
		}

		if (!is_email($email)) {
			$erros[] = 'E-mail inválido.';
		}

		if (!$modo_edicao && strlen($senha) < 3) {
			$erros[] = 'A senha deve ter pelo menos 3 caracteres.';
		}

		if (empty($erros)) {
			if ($modo_edicao) {
				$user_id = wp_update_user([
					'ID' => $editar_id,
					'user_email' => $email,
					'display_name' => $nome_completo,
				]);

				if (!is_wp_error($user_id)) {
					global $wpdb;
					$wpdb->update(
						$wpdb->users,
						['user_status' => intval($user_status)],
						['ID' => $editar_id],
						['%d'],
						['%d']
					);
				}


				if (!empty($senha)) {
					wp_set_password($senha, $editar_id);
				}

				if (!is_wp_error($user_id)) {
					// Atualiza role
					$user_obj = new WP_User($editar_id);
					$user_obj->set_role($cargo);
				}
			} else {
				$user_id = wp_insert_user([
					'user_login' => $email,
					'user_pass'  => $senha,
					'user_email' => $email,
					'display_name' => $nome_completo,
					'role' => $cargo,
					'user_status' => $user_status,
				]);
			}

			if (!is_wp_error($user_id)) {
				update_user_meta($user_id, 'nome_completo', $nome_completo);
				update_user_meta($user_id, 'cpf', $cpf);

				echo '<p class="msg sucesso">Usuário ' . ($modo_edicao ? 'atualizado' : 'cadastrado') . ' com sucesso!</p>';
			} else {
				echo '<p class="msg erro">Erro: ' . esc_html($user_id->get_error_message()) . '</p>';
			}
		} else {
			foreach ($erros as $erro) {
				echo '<p class="msg erro">' . esc_html($erro) . '</p>';
			}
		}
	}

	$valor = function ($meta) use ($usuario) {
		return $usuario ? esc_attr(get_user_meta($usuario->ID, $meta, true)) : '';
	};

	$email = $usuario ? esc_attr($usuario->user_email) : '';
	$nome = $usuario ? esc_attr($usuario->display_name) : '';
	$role_atual = $usuario ? implode('', $usuario->roles) : '';
?>
	<style>
		.form-user-wrapper {
			max-width: 500px;
			margin: 30px auto;
			background: #ffffff;
			padding: 30px;
			border-radius: 10px;
			box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
		}

		.form-user-wrapper p {
			margin-bottom: 20px;
		}

		.form-user-wrapper label {
			font-weight: bold;
			display: block;
			margin-bottom: 6px;
			color: #333;
			font-size: 15px;
		}

		.form-user-wrapper input[type="text"],
		.form-user-wrapper input[type="email"],
		.form-user-wrapper input[type="password"],
		.form-user-wrapper select {
			width: 100%;
			padding: 12px;
			border-radius: 6px;
			border: 1px solid #ccc;
			background-color: #f9f9f9;
			transition: border-color 0.3s, background-color 0.3s;
			font-size: 14px;
		}

		.form-user-wrapper input[type="text"]:focus,
		.form-user-wrapper input[type="email"]:focus,
		.form-user-wrapper input[type="password"]:focus,
		.form-user-wrapper select:focus {
			border-color: #0073aa;
			background-color: #fff;
			outline: none;
		}

		.form-user-wrapper input[type="submit"] {
			background-color: #0073aa;
			color: white;
			padding: 12px 24px;
			border: none;
			border-radius: 6px;
			cursor: pointer;
			font-weight: bold;
			font-size: 15px;
			transition: background-color 0.3s, transform 0.2s;
			width: 100%;
		}

		.form-user-wrapper input[type="submit"]:hover {
			background-color: #005e8a;
			transform: scale(1.02);
		}

		.msg.sucesso {
			color: green;
			font-weight: bold;
			text-align: center;
			margin-top: 20px;
		}

		.msg.erro {
			color: red;
			font-weight: bold;
			text-align: center;
			margin-top: 20px;
		}
	</style>

	<div class="form-user-wrapper">
		<form method="post">
			<?php wp_nonce_field('cadastro_usuario', 'cadastro_usuario_nonce'); ?>

			<p>
				<label for="nome_completo">Nome completo:</label>
				<input type="text" name="nome_completo" value="<?= esc_attr($nome) ?>" required>
			</p>

			<p>
				<label for="cargo">Função (Cargo):</label>
				<select name="cargo" required>
					<option value="">Selecione uma função</option>
					<?php
					global $wp_roles;
					if (!isset($wp_roles)) {
						$wp_roles = new WP_Roles();
					}
					foreach ($wp_roles->roles as $role_key => $role) {
						if ($role_key === 'administrator') continue; // Oculta 'administrator'
						$selected = ($role_key === ($modo_edicao ? $role_atual : $valor('cargo'))) ? 'selected' : '';
						echo '<option value="' . esc_attr($role_key) . '" ' . $selected . '>' . esc_html($role['name']) . '</option>';
					}
					?>
				</select>
			</p>

			<p>
				<label for="email">E-mail:</label>
				<input type="email" name="email" value="<?= esc_attr($email) ?>" required>
			</p>

			<p>
				<label for="cpf">CPF:</label>
				<input type="text" name="cpf" value="<?= esc_attr($valor('cpf')) ?>" required pattern="\d{11}" title="Digite 11 números, somente números.">
			</p>

			<p>
				<label for="senha">Senha <?= $modo_edicao ? '(deixe em branco para manter)' : '' ?>:</label>
				<input type="password" name="senha" <?= $modo_edicao ? '' : 'required' ?>>
			</p>

			<?php
			$user_status_valor = $usuario ? $usuario->user_status : '0';
			?>
			<p>
				<label for="user_status">Status do Usuário:</label>
				<select name="user_status">
					<option value="0" <?= $user_status_valor == '0' ? 'selected' : '' ?>>Ativo</option>
					<option value="1" <?= $user_status_valor == '1' ? 'selected' : '' ?>>Inativo</option>
				</select>
			</p>


			<p>
				<input type="submit" value="<?= $modo_edicao ? 'Atualizar' : 'Cadastrar' ?>">
			</p>
		</form>
	</div>

<?php
	return ob_get_clean();
}
add_shortcode('formulario_cadastro_usuario', 'formulario_cadastro_usuario_shortcode');
