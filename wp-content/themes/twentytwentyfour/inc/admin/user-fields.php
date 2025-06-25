<?php

// Adiciona campo de CPF ao perfil do usuário
function adicionar_campo_cpf($user)
{
	?>
	<h3><?php esc_html_e('Informações Adicionais', 'text-domain'); ?></h3>
	<table class="form-table">
		<tr>
			<th><label for="cpf"><?php esc_html_e('CPF', 'text-domain'); ?></label></th>
			<td>
				<input type="text" name="cpf" id="cpf" value="<?php echo esc_attr(get_the_author_meta('cpf', $user->ID)); ?>" class="regular-text" />
				<br /><span class="description"><?php esc_html_e('Por favor, insira seu CPF.', 'text-domain'); ?></span>
			</td>
		</tr>
	</table>
	<?php
}
add_action('show_user_profile', 'adicionar_campo_cpf');
add_action('edit_user_profile', 'adicionar_campo_cpf');

// Salva o CPF com sanitização e validação
function salvar_campo_cpf($user_id)
{
	if (!current_user_can('edit_user', $user_id)) {
		return;
	}

	if (isset($_POST['cpf'])) {
		$cpf = sanitize_text_field($_POST['cpf']);

		if (preg_match('/^\d{11}$/', $cpf)) {
			update_user_meta($user_id, 'cpf', $cpf);
		} else {
			add_filter('user_profile_update_errors', function ($errors) {
				$errors->add('invalid_cpf', __('CPF inválido. Insira apenas números (11 dígitos).', 'text-domain'));
			});
		}
	}
}
add_action('personal_options_update', 'salvar_campo_cpf');
add_action('edit_user_profile_update', 'salvar_campo_cpf');
