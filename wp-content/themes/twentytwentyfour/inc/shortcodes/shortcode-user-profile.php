<?php
function display_user_profile()
{
	ob_start();

	if (is_user_logged_in()) {
		// Obtém os dados do usuário logado
		$current_user = wp_get_current_user();
		$name_parts = explode(' ', trim($current_user->display_name));

		// O primeiro nome é sempre a primeira palavra
		$first_name = $name_parts[0];

		// O sobrenome será o restante do nome completo
		$last_name = count($name_parts) > 1 ? implode(' ', array_slice($name_parts, 1)) : '';

?>
		<div class="container mt-5">
			<div class="card shadow-sm mx-auto" style="max-width: 600px;">
				<div class="card-header bg-secondary text-white text-center">
					<h2 class="mb-0">Bem-vindo(a), <?php echo esc_html($first_name); ?></h2>
				</div>
				<div class="card-body">
					<div class="row mb-3">
						<div class="col-4 text-muted"><strong>Nome:</strong></div>
						<div class="col-8"><?php echo esc_html($first_name); ?></div>
					</div>
					<div class="row mb-3">
						<div class="col-4 text-muted"><strong>Sobrenome:</strong></div>
						<div class="col-8"><?php echo esc_html($last_name); ?></div>
					</div>
					<div class="row mb-3">
						<div class="col-4 text-muted"><strong>Email:</strong></div>
						<div class="col-8"><?php echo esc_html($current_user->user_email); ?></div>
					</div>
				</div>
			</div>
		</div>
	<?php
	} else {
		// Caso não esteja logado e nenhum ID de usuário foi fornecido
		echo '<div class="container mt-5"><div class="alert alert-danger">Você precisa estar logado para ver esta página.</div></div>';
	}

	return ob_get_clean();
}
add_shortcode('profile_page', 'display_user_profile');
