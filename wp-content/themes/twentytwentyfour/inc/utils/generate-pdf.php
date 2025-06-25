<?php
// Função para gerar o PDF ao clicar no botão
function gerar_pdf_ocorrencia()
{
	if (isset($_GET['generate_pdf']) && $_GET['generate_pdf'] === 'true' && isset($_GET['relatorio_id'])) {
		require_once(get_template_directory() . '/tcpdf/tcpdf.php');
		// Caminho para a biblioteca TCPDF

		global $wpdb;
		$relatorio_id = intval($_GET['relatorio_id']);

		// Resgata os dados do relatório e tratativa
		$relatorio = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_relatorios WHERE id = %d", $relatorio_id));
		$tratativa = $wpdb->get_row($wpdb->prepare("SELECT * FROM wp_tratativas WHERE id = %d", $relatorio->tratativa_id));

		if (!$relatorio || !$tratativa) {
			wp_die('Relatório ou tratativa não encontrada.');
		}

		$dataFormatada = DateTime::createFromFormat('Y-m-d', $relatorio->data_criacao)->format('d/m/Y');
		$dataTratativa =  DateTime::createFromFormat('Y-m-d', $tratativa->data_criacao)->format('d/m/Y');
		// Recupera informações do usuário associado ao relatório (emissor)
		$usuario_id = $relatorio->user_id;
		$user_info = get_userdata($usuario_id);
		$nome_usuario = $user_info ? $user_info->display_name : 'Usuário desconhecido';
		$descricao_ocorrencia = $relatorio ? htmlspecialchars_decode(stripslashes($relatorio->descricao), ENT_QUOTES) : '';
		$numero_cat = $tratativa->numero_cat ? $tratativa->numero_cat : "NÃO APLICA";
		$numero_os = $tratativa->numero_os ? $tratativa->numero_os : "NÃO APLICA";

		if ($user_info && !empty($user_info->roles)) {
			$wp_roles = wp_roles(); // maneira correta de obter os papéis/roles no WordPress

			$cargos_legiveis = array_map(function ($role_slug) use ($wp_roles) {
				return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
			}, $user_info->roles);

			$cargo = implode(', ', $cargos_legiveis);
		} else {
			$cargo = 'Cargo desconhecido';
		}

		// Recupera informações do implicado
		$implicado_info = get_userdata($relatorio->implicado);
		$nome_implicado = $implicado_info ? $implicado_info->display_name : esc_html($relatorio->implicado);

		// Cargo do implicado
		if ($implicado_info && !empty($implicado_info->roles)) {
			$cargos_implicado = array_map(function ($role_slug) use ($wp_roles) {
				return $wp_roles->roles[$role_slug]['name'] ?? ucfirst($role_slug);
			}, $implicado_info->roles);
			$cargo_implicado = implode(', ', $cargos_implicado);
		} else {
			$cargo_implicado = 'Cargo desconhecido';
		}

		$responsavel_id = $tratativa->responsavel_id;
		$responsavel_info = get_userdata($responsavel_id);
		$nome_responsavel = $responsavel_info ? $responsavel_info->display_name : 'Usuário desconhecido';

		$lesao = !empty($tratativa->lesao) && unserialize($tratativa->lesao) !== [] ? $tratativa->lesao : null;
		$parte_lesionada = !empty($tratativa->parte_lesionada) && unserialize($tratativa->parte_lesionada) !== [] ? $tratativa->parte_lesionada : null;

		function is_serialized_data($data)
		{
			return is_string($data) && preg_match('/^(a|O|s|i|b|d|N):/', $data) && @unserialize($data) !== false;
		}

		$causa_raiz_valor = null;

		if (!empty($tratativa->causa_raiz)) {
			if (is_serialized_data($tratativa->causa_raiz)) {
				$array_causa_raiz = @unserialize($tratativa->causa_raiz);
				if (is_array($array_causa_raiz) && !empty($array_causa_raiz)) {
					$causa_raiz_valor = implode(', ', $array_causa_raiz);
				}
			} else {
				$causa_raiz_valor = $tratativa->causa_raiz; // valor antigo em texto simples
			}
		}
		// Inicializa o TCPDF
		$pdf = new TCPDF();
		$pdf->SetMargins(5, 10, 5);
		$pdf->AddPage();
		$image_url = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/uploads/2024/10/LOGO-removebg-1.jpg';

		if ($image_url) {
			$pdf->Image($image_url, 10, 10, 30, 20);
		}

		// Define o título com fonte reduzida e fundo escuro
		$pdf->SetFont('helvetica', 'B', 11);
		$pdf->SetFillColor(200, 200, 200); // Cor de fundo cinza claro
		$pdf->Ln(25);
		$pdf->MultiCell(0, 8, 'FE-003 RELATÓRIO DE ANÁLISE DE ACIDENTE INCIDENTE TRABALHO E OCORRENCIAS - RAAITO', 1, 'C', true);

		// Dados do relatório com fundo escuro e bordas em todas as células
		$pdf->SetFont('helvetica', '', 10);
		$pdf->Ln(3); // Menor espaçamento entre o título e o conteúdo

		// Função auxiliar para criar campo com borda e fundo cinza claro
		function addField($pdf, $label, $value, $label_inline = false, $multi_cell = false)
		{
			$pdf->SetFillColor(230, 230, 230); // Fundo cinza claro
			if ($label_inline) {
				if ($label == 'RESPONSÁVEL TRATATIVA:' || $label == 'MEDIDA DISCIPLINAR:' || $label == 'V - CAUSA RAIZ:' || $label == 'IV - PARTE LESIONADA:' || $label == 'III - NATUREZA DA LESÃO:' || $label == 'II - GRAU OCORRÊNCIA:') {
					$pdf->SetFont('helvetica', 'B', 10);
					$pdf->Cell(52, 6, $label, 1, 0, 'L', true); // Label com borda e fundo
					$pdf->SetFont('helvetica', '', 10);
					$pdf->Cell(0, 6, $value, 1, 1, 'L', false); // Valor com borda
				} else if ($label == 'EMEB:') {
					$pdf->SetFont('helvetica', 'B', 10);
					$pdf->Cell(35, 6, $label, 1, 0, 'L', true); // Label com borda e fundo
					$pdf->SetFont('helvetica', '', 10);
					$pdf->Cell(0, 6, $value, 1, 1, 'L', false);
				} else if ($label == 'Assinatura Gestão Pessoal e Frotas | GPF | Data:' || $label == 'Assinatura da Administração Geral e Processos | ADG | Data:') {

					$pdf->SetFont('helvetica', 'B', 10);
					$pdf->Cell(110, 6, $label, 1, 0, 'L', true); // Label com borda e fundo
					$pdf->SetFont('helvetica', '', 10);
					$pdf->Cell(0, 6, $value, 1, 1, 'L', false);
				} else {
					$pdf->SetFont('helvetica', 'B', 10);
					$pdf->Cell(70, 6, $label, 1, 0, 'L', true); // Label com borda e fundo
					$pdf->SetFont('helvetica', '', 10);
					$pdf->Cell(0, 6, $value, 1, 1, 'L', false); // Valor com borda
				}
			} else if ($multi_cell) {
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(0, 6, $label, 1, 1, 'C', true); // Label com borda e fundo
				$pdf->SetFont('helvetica', '', 10);
				$pdf->MultiCell(0, 12, $value, 1, 'L', false); // Valor com borda
			} else {
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(0, 6, $label, 1, 1, 'C', true); // Label com borda e fundo
				$pdf->SetFont('helvetica', '', 10);
				$pdf->MultiCell(0, 6, $value, 1, 'L', false); // Valor com borda
			}
		}

		// Nova função para criar dois campos lado a lado
		function addFieldInlinePair($pdf, $label1, $value1, $label2, $value2)
		{
			if ($label1 == "HORA TRATATIVA:") {
				$pdf->SetFillColor(230, 230, 230); // Fundo cinza claro
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(52, 6, $label1, 1, 0, 'L', true);
				$pdf->SetFont('helvetica', '', 10);
				$pdf->Cell(61.5, 6, $value1, 1, 0, 'L', false);
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(38, 6, $label2, 1, 0, 'L', true);
				$pdf->SetFont('helvetica', '', 10);
				$pdf->Cell(0, 6, $value2, 1, 1, 'L', false);
			} else if($label1 == "NÚMERO OS (ORDEM DE SERVIÇO):") {
				$pdf->SetFillColor(230, 230, 230); // Fundo cinza claro
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(65, 6, $label1, 1, 0, 'L', true);
				$pdf->SetFont('helvetica', '', 10);
				$pdf->Cell(48.5, 6, $value1, 1, 0, 'L', false);
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(38, 6, $label2, 1, 0, 'L', true);
				$pdf->SetFont('helvetica', '', 10);
				$pdf->Cell(0, 6, $value2, 1, 1, 'L', false);
			} 
			else {
				$pdf->SetFillColor(230, 230, 230); // Fundo cinza claro
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(35, 6, $label1, 1, 0, 'L', true);
				$pdf->SetFont('helvetica', '', 10);
				$pdf->Cell(78.5, 6, $value1, 1, 0, 'L', false);
				$pdf->SetFont('helvetica', 'B', 10);
				$pdf->Cell(38, 6, $label2, 1, 0, 'L', true);
				$pdf->SetFont('helvetica', '', 10);
				$pdf->Cell(0, 6, $value2, 1, 1, 'L', false);
			}
		}

		function addFieldTriple($pdf, $label1, $value1, $label2, $value2, $label3, $value3)
		{
			$pdf->SetFillColor(230, 230, 230); // Fundo cinza claro
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->Cell(35, 6, $label1, 1, 0, 'L', true);
			$pdf->SetFont('helvetica', '', 10);
			$pdf->Cell(25, 6, $value1, 1, 0, 'L', false);
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->Cell(38, 6, $label2, 1, 0, 'L', true);
			$pdf->SetFont('helvetica', '', 10);
			$pdf->Cell(15.5, 6, $value2, 1, 0, 'L', false);
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->Cell(38, 6, $label3, 1, 0, 'L', true);
			$pdf->SetFont('helvetica', '', 10);
			$pdf->Cell(0, 6, $value3, 1, 1, 'L', false);
		}

		addFieldTriple(
			$pdf,
			'NÚMERO RAAITO:',
			$relatorio->numero_protocolo,
			'HORA OCORRÊNCIA:',
			$relatorio->hora_criacao,
			'DATA OCORRÊNCIA:',
			$dataFormatada
		);

		addFieldInlinePair($pdf, 'NOME EMISSOR:', $nome_usuario, 'CARGO EMISSOR:', $cargo);
		addField($pdf, 'I - MODALIDADE:', implode(', ', unserialize($relatorio->modalidade)));
		// Adiciona os campos ao PDF
		addFieldInlinePair($pdf, 'IMPLICADO:', $nome_implicado, 'CARGO IMPLICADO:', $cargo_implicado);
		addField($pdf, 'EMEB:', $relatorio->local, true);
		addField($pdf, 'II - GRAU OCORRÊNCIA:', $tratativa->nivel_gravidade, true);

		// Verifica se há lesão
		if (!empty($lesao) && !empty($parte_lesionada)) {
			addField($pdf, 'III - NATUREZA DA LESÃO:', implode(', ', unserialize($lesao)), true);
			addField($pdf, 'IV - PARTE LESIONADA:', implode(', ', unserialize($parte_lesionada)), true);
		}
		addField($pdf, 'V - CAUSA RAIZ:', $causa_raiz_valor, true);
		
		addFieldInlinePair($pdf, 'NÚMERO OS (ORDEM DE SERVIÇO):', $numero_os, 'NÚMERO CAT:', $numero_cat);

		addField($pdf, 'MEDIDA DISCIPLINAR:', $tratativa->medida_disciplinar, true);

		addField($pdf, 'VI - DESCRIÇÃO OCORRIDO (AÇÕES DIRETAS, DESCREVER DE FORMA SUSCINTA):', $descricao_ocorrencia, false, true);
		// Recupera imagens relacionadas ao relatório
		$imagens = $wpdb->get_results($wpdb->prepare("SELECT * FROM wp_relatorio_imagens WHERE relatorio_id = %d", $relatorio_id));

		if (!empty($imagens)) {
			$pdf->SetFont('helvetica', 'B', 10);
			$pdf->Cell(0, 6, 'VII - Imagens da ocorrência:', 1, 1, 'C', true); // Label com borda e fundo
			$pdf->SetFont('helvetica', '', 10);

			$x_start = 18; // Posição inicial na horizontal (margem esquerda)
			$y_start = $pdf->GetY(); // Posição vertical onde as imagens começam

			$image_width = 50; // Largura de cada imagem (ajuste conforme necessário)
			$image_height = 30; // Altura das imagens (ajuste conforme necessário)
			$margin_right = 10; // Margem direita entre as imagens

			foreach ($imagens as $index => $imagem) {
				$image_path = get_attached_file($imagem->imagem_id); // Caminho físico da imagem

				if ($image_path && file_exists($image_path)) {
					// Se a posição X ultrapassar o limite da largura da página, muda para a próxima linha
					if ($x_start + $image_width + $margin_right > $pdf->getPageWidth() - 10) {
						$x_start = 10; // Reset para a posição inicial
						$y_start += $image_height + 5; // Move para a próxima linha
					}

					// Adiciona uma célula com borda para a imagem
					$pdf->SetXY($x_start, $y_start);
					$pdf->Cell($image_width, $image_height, '', 1, 0, 'C', false);

					// Insere a imagem dentro da célula
					$pdf->Image($image_path, $x_start + 1, $y_start + 1, $image_width - 2, $image_height - 2);

					$x_start += $image_width + $margin_right;
				}
			}

			// Ajusta a posição Y para continuar o conteúdo
			$pdf->SetY($y_start + $image_height + 5);
		}

		addField($pdf, 'RESPONSÁVEL TRATATIVA:', $nome_responsavel, true);

		addFieldInlinePair($pdf, 'HORA TRATATIVA:', $tratativa->hora_criacao, 'DATA TRATATIVA:', $dataTratativa);

		addField($pdf, 'VIII - AÇÕES APÓS O OCORRIDO:', $tratativa->resolucao_ocorrido, false, true);

		addField($pdf, 'IX - OBSERVAÇÕES GERAIS E LAUDOS EXTERNOS (SE NECESSÁRIO):', $tratativa->observacao, false, true);

		// Espaço para Assinaturas com borda e fundo claro
		$pdf->SetFont('helvetica', 'B', 10);
		// Cabeçalho das Assinaturas
		$pdf->Cell(0, 6, 'X - Assinaturas:', 1, 1, 'C', true); // Título 'Assinaturas' com borda e fundo

		addField($pdf, 'Assinatura do Funcionário | Data:', "", true);

		addField($pdf, 'Assinatura Gestão Pessoal e Frotas | GPF | Data:', "", true);

		addField($pdf, 'Assinatura da Administração Geral e Processos | ADG | Data:', "", true);
		
		addField($pdf, 'Assinatura da Diretoria:', "", true);

		// Saída do PDF (força o download)
		$pdf->Output('relatorio_ocorrencia_' . $relatorio->numero_protocolo . '.pdf', 'I');
		exit;
	}
}
add_action('init', 'gerar_pdf_ocorrencia');
