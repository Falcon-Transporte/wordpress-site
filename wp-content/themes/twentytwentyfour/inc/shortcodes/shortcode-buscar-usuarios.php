<?php

function buscar_usuarios_shortcode()
{
	ob_start();
?>
	<script>
		document.addEventListener("DOMContentLoaded", function() {
			const searchInput = document.getElementById("user_search");
			const resultsContainer = document.getElementById("user_results");

			searchInput.addEventListener("input", function() {
				const query = searchInput.value.trim();

				if (query.length > 0) {
					const url = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>?action=buscar_usuarios_ajax&query=' + encodeURIComponent(query);

					fetch(url)
						.then(response => response.json())
						.then(data => {
							resultsContainer.innerHTML = "";

							if (data.length > 0) {
								data.forEach(usuario => {
									const roles = Array.isArray(usuario.roles) ? usuario.roles : [];
									const statusLabel = Number(usuario.user_status) === 1 ?
										'<span style="color: red; font-weight: bold;">Inativo</span>' :
										'<span style="color: green; font-weight: bold;">Ativo</span>';

									const card = `
										<div class="user-card" onclick="redirectToProfile(${usuario.ID})">
											<h5>${usuario.display_name}</h5>
											<p><strong>ID:</strong> ${usuario.ID}</p>
											<p><strong>E-mail:</strong> ${usuario.user_email}</p>
											<p><strong>Função:</strong> ${roles.join(', ')}</p>
											<p><strong>Condição:</strong> ${statusLabel}</p>
										</div>`;
									resultsContainer.insertAdjacentHTML("beforeend", card);
								});
							} else {
								resultsContainer.innerHTML = '<p class="no-results">Nenhum usuário encontrado.</p>';
							}
						});
				} else {
					resultsContainer.innerHTML = '';
				}
			});
		});

		function redirectToProfile(userId) {
			const profileUrl = '<?php echo esc_url(site_url('/usuario')); ?>';
			window.location.href = `${profileUrl}?user_id=${userId}`;
		}

		function redirectToCadastro() {
			const cadastroUrl = '<?php echo esc_url(site_url('/cadastro-usuario')); ?>';
			window.location.href = cadastroUrl;
		}
	</script>
	<div class="buscar-usuarios-wrapper">
		<div class="search-container">
			<div class="search-header">
				<h3>Buscar Funcionário(a)</h3>
				<button class="btn-add" onclick="redirectToCadastro()">Cadastrar Novo</button>
			</div>
			<form>
				<div class="input-group">
					<input type="text" id="user_search" class="form-control" placeholder="Digite o nome ou e-mail do usuário">
					<div class="input-group-append">
						<button class="btn btn-primary" type="button"><i class="fas fa-search"></i></button>
					</div>
				</div>
			</form>
		</div>

		<div class="user-results" id="user_results"></div>
	</div>


<?php
	return ob_get_clean();
}
add_shortcode('buscar_usuarios', 'buscar_usuarios_shortcode');

function buscar_usuarios_ajax()
{
	$query = isset($_GET['query']) ? sanitize_text_field($_GET['query']) : '';
	if (empty($query)) {
		wp_send_json([]);
	}

	$args = [
		'search'         => '*' . esc_attr($query) . '*',
		'search_columns' => ['user_login', 'user_nicename', 'user_email', 'display_name', 'user_status'],
		'number'         => 10,
		'fields'         => ['ID', 'display_name', 'user_email', 'user_status'],
	];

	$user_query = new WP_User_Query($args);
	$usuarios = $user_query->get_results();
	$resultado = [];

	$wp_roles = wp_roles(); // Para obter nomes legíveis dos cargos

	foreach ($usuarios as $usuario) {
		$userdata = get_userdata($usuario->ID);

		// Obtem cargos legíveis
		$cargos_legiveis = [];
		if ($userdata && !empty($userdata->roles)) {
			$cargos_legiveis = array_map(function ($role_slug) use ($wp_roles) {
				return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
			}, $userdata->roles);
		}

		$resultado[] = [
			'ID'            => $usuario->ID,
			'display_name'  => $usuario->display_name,
			'user_email'    => $usuario->user_email,
			'roles'         => $cargos_legiveis,
			'user_status'   => $usuario->user_status,
		];
	}

	wp_send_json($resultado);
}

add_action('wp_ajax_buscar_usuarios_ajax', 'buscar_usuarios_ajax');
add_action('wp_ajax_nopriv_buscar_usuarios_ajax', 'buscar_usuarios_ajax');