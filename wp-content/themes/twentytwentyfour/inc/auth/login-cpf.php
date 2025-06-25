<?php

// Permite login com CPF
function login_com_cpf($user, $username, $password)
{
	if (preg_match('/^\d{11}$/', $username)) {
		$users = get_users([
			'meta_key'   => 'cpf',
			'meta_value' => $username,
			'number'     => 1,
		]);

		if (!empty($users)) {
			$username = $users[0]->user_login;
		} else {
			return new WP_Error('invalid_cpf', __('CPF inválido.', 'text-domain'));
		}
	}

	return wp_authenticate_username_password(null, $username, $password);
}
add_filter('authenticate', 'login_com_cpf', 20, 3);

// Mensagem de erro personalizada no login
function custom_login_error_message()
{
	return __('Seu CPF ou senha estão incorretos. Por favor, tente novamente.', 'text-domain');
}
add_filter('login_errors', 'custom_login_error_message');

// Personaliza o texto de recuperação de senha
function custom_lost_password_texts($translated_text, $text, $domain)
{
	if ($text === 'Digite o seu nome de usuário ou endereço de e-mail. Você receberá um e-mail com instruções sobre como redefinir a sua senha.') {
		return __('Por favor, insira seu endereço de e-mail. Você receberá um link para criar uma nova senha por e-mail.', 'text-domain');
	}
	return $translated_text;
}
add_filter('gettext', 'custom_lost_password_texts', 20, 3);