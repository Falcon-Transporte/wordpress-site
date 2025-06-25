<?php

function dashboard_ocorrencias_unificado_shortcode()
{
	global $wpdb;

	// ===== 1. Ocorrências por Mês =====
	$resultados_mes = $wpdb->get_results("
        SELECT DATE_FORMAT(data_criacao, '%Y-%m') AS mes, COUNT(*) AS total
        FROM wp_relatorios
        GROUP BY mes
        ORDER BY mes ASC
    ");

	$labels_mes = [];
	$dados_mes = [];

	foreach ($resultados_mes as $row) {
		$labels_mes[] = date('M/Y', strtotime($row->mes . '-01'));
		$dados_mes[] = (int) $row->total;
	}

	// ===== 2. Ocorrências por Modalidade =====
	$resultados_modalidade = $wpdb->get_results("SELECT modalidade FROM wp_relatorios");

	$modalidade_count = [];

	foreach ($resultados_modalidade as $row) {
		$modalidades = maybe_unserialize($row->modalidade);
		if (is_array($modalidades)) {
			foreach ($modalidades as $mod) {
				$mod = trim($mod);
				if (!empty($mod)) {
					$modalidade_count[$mod] = ($modalidade_count[$mod] ?? 0) + 1;
				}
			}
		} elseif (!empty($row->modalidade)) {
			$mod = trim($row->modalidade);
			$modalidade_count[$mod] = ($modalidade_count[$mod] ?? 0) + 1;
		}
	}

	$labels_modalidade = array_keys($modalidade_count);
	$dados_modalidade = array_values($modalidade_count);

	// ===== 3. Ocorrências por Local =====
	$resultados_local = $wpdb->get_results("
        SELECT local, COUNT(*) AS total
        FROM wp_relatorios
        GROUP BY local
        ORDER BY total DESC
        LIMIT 10
    ");

	$labels_local = [];
	$dados_local = [];

	foreach ($resultados_local as $row) {
		$labels_local[] = esc_html($row->local);
		$dados_local[] = (int) $row->total;
	}

	$resultado_cargo = $wpdb->get_results("
    SELECT r.cargo_implicado, COUNT(*) as total
    FROM wp_relatorios r
    GROUP BY r.cargo_implicado
    ORDER BY total DESC
");

	$cargos = [];
	$totais = [];

	foreach ($resultado_cargo as $row) {
		$cargos[] = $row->cargo_implicado;
		$totais[] = (int) $row->total;
	}

	ob_start();
?>
	<style>
		.dashboard-graficos {
			display: flex;
			flex-wrap: wrap;
			gap: 20px;
			margin: 20px;
			padding: 0;
			justify-content: center;
		}

		.grafico-container {
			flex: 1 1 45%;
			min-width: 300px;
			background-color: #fff;
			border-radius: 16px;
			padding: 15px;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			border: 1px solid #eee;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
		}

		canvas {
			width: 100% !important;
			max-width: 100%;
			height: auto !important;
		}

		#graficoModalidades {
			min-height: 500px;
		}

		@media (max-width: 768px) {
			.grafico-container {
				flex: 1 1 100%;
			}

			canvas {
				height: 400px !important;
				/* altura generosa para labels verticais */
			}
		}
	</style>

	<div class="dashboard-graficos">

		<div class="grafico-container">
			<canvas id="graficoModalidades"></canvas>
		</div>
		<div class="grafico-container">
			<canvas id="graficoPorMes"></canvas>
		</div>
		<div class="grafico-container">
			<canvas id="graficoPorCargo"></canvas>
		</div>
		<div class="grafico-container">
			<canvas id="graficoPorLocal"></canvas>
		</div>

	</div>

	<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const mesLabels = <?php echo json_encode($labels_mes); ?>;
			const mesData = <?php echo json_encode($dados_mes); ?>;
			const modalidadeLabels = <?php echo json_encode($labels_modalidade); ?>;
			const modalidadeData = <?php echo json_encode($dados_modalidade); ?>;

			function wrapText(text, maxCharsPerLine) {
				const words = text.split(' ');
				const lines = [];
				let currentLine = '';

				words.forEach(word => {
					if ((currentLine + word).length <= maxCharsPerLine) {
						currentLine += (currentLine ? ' ' : '') + word;
					} else {
						if (currentLine) lines.push(currentLine);
						currentLine = word;
					}
				});

				if (currentLine) lines.push(currentLine);
				return lines;
			}

			new Chart(document.getElementById('graficoModalidades').getContext('2d'), {
				type: 'bar',
				data: {
					labels: modalidadeLabels,
					datasets: [{
						label: '',
						data: modalidadeData,
						backgroundColor: 'rgba(75, 192, 192, 0.7)',
						borderColor: 'rgba(75, 192, 192, 1)',
						borderWidth: 1
					}]
				},
				plugins: [ChartDataLabels],
				options: {
					indexAxis: 'y',
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						title: {
							display: true,
							text: 'Ocorrências por Modalidade'
						},
						datalabels: {
							anchor: 'center',
							align: 'end',
							clamp: true,
							formatter: (value, context) => {
								const label = context.chart.data.labels[context.dataIndex];
								const wrapped = wrapText(label, 18); // reduzido para telas pequenas
								wrapped[wrapped.length - 1] += ` (${value})`;
								return wrapped;
							},
							font: {
								weight: 'bold',
								size: 12,
							},
							color: '#000'
						}
					},
					scales: {
						x: {
							beginAtZero: true
						},
						y: {
							ticks: {
								display: false
							}
						}
					}
				}
			});


			// Gráfico por Mês
			new Chart(document.getElementById('graficoPorMes').getContext('2d'), {
				type: 'bar',
				data: {
					labels: mesLabels,
					datasets: [{
						label: 'Ocorrências por Mês',
						data: mesData,
						backgroundColor: 'rgba(54, 162, 235, 0.7)',
						borderColor: 'rgba(54, 162, 235, 1)',
						borderWidth: 1
					}]
				},
				options: {
					plugins: {
						legend: {
							display: false
						},
						title: {
							display: true,
							text: 'Ocorrências por Mês',
							font: {
								size: 16
							}
						},
						datalabels: {
							color: '#000',
							anchor: 'end',
							align: 'start',
							font: {
								weight: 'bold',
								size: 12
							},
							formatter: Math.round
						}
					},
					responsive: true,
					maintainAspectRatio: false,
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								precision: 0
							}
						}
					}
				},
				plugins: [ChartDataLabels]
			});

			new Chart(document.getElementById('graficoPorLocal').getContext('2d'), {
				type: 'doughnut',
				data: {
					labels: <?php echo json_encode($labels_local); ?>,
					datasets: [{
						label: 'Ocorrências por Local',
						data: <?php echo json_encode($dados_local); ?>,
						backgroundColor: [
							'rgba(255, 99, 132, 0.7)',
							'rgba(255, 159, 64, 0.7)',
							'rgba(255, 205, 86, 0.7)',
							'rgba(75, 192, 192, 0.7)',
							'rgba(54, 162, 235, 0.7)',
							'rgba(153, 102, 255, 0.7)',
							'rgba(201, 203, 207, 0.7)',
							'rgba(0, 128, 128, 0.7)',
							'rgba(148, 0, 211, 0.7)',
							'rgba(220, 20, 60, 0.7)'
						],
						borderColor: '#fff',
						borderWidth: 2
					}]
				},
				options: {
					responsive: true,
					plugins: {
						title: {
							display: true,
							text: 'Locais com Mais Ocorrências'
						},
						legend: {
							position: 'bottom'
						}
					}
				}
			});

			new Chart(document.getElementById('graficoPorCargo').getContext('2d'), {
				type: 'bar',
				data: {
					labels: <?php echo json_encode($cargos); ?>,
					datasets: [{
						label: 'Total de Ocorrências',
						data: <?php echo json_encode($totais); ?>,
						backgroundColor: 'rgba(153, 102, 255, 0.7)',
						borderColor: 'rgba(153, 102, 255, 1)',
						borderWidth: 1
					}]
				},
				plugins: [ChartDataLabels],
				options: {
					indexAxis: 'y',
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						title: {
							display: true,
							text: 'Ocorrências por Cargo'
						},
						datalabels: {
							anchor: 'center',
							align: 'center',
							font: {
								weight: 'bold',
								size: 12
							},
							color: '#fff',
							formatter: (value) => value
						}
					},
					scales: {
						x: {
							beginAtZero: true
						},
						y: {
							ticks: {
								display: true
							}
						}
					}
				}
			});
		});
	</script>
<?php
	return ob_get_clean();
}
add_shortcode('dashboard_ocorrencias', 'dashboard_ocorrencias_unificado_shortcode');