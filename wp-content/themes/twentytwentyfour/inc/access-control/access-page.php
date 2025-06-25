<?php

add_action('template_redirect', function () {
    // Verifica se estamos na página "ocorrencias" e se há o parâmetro 'implicado' na URL
    if (is_page('ocorrencias') && isset($_GET['implicado'])) {
        $implicado = sanitize_text_field($_GET['implicado']);

        // Verificação adicional do parâmetro 'implicado'
        if (!is_numeric($implicado)) {
            wp_die('O parâmetro "implicado" é inválido.');
        }

        $user = wp_get_current_user();

        // Slugs das funções permitidas
        $roles_permitidas = ['administrator', 'agp', 'gpf', 'rh', 'escritorio', 'ti'];

        // Se o usuário não tem nenhuma das funções permitidas, bloqueia o acesso
        if (!array_intersect($roles_permitidas, (array) $user->roles)) {
            wp_die('Acesso negado. Você não tem permissão para visualizar esta página.');
        }
    }
});
