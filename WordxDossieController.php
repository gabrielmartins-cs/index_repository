<?php

namespace App\Http\Controllers;

class WordxDossieController extends Controller
{
	public function criarWordx($idarquivodocx, $textojson, $teses, $dadosXmlArteria, $nomedapasta, $sintesefatica, $paragrafosConclusao)
	{
		//dd($idarquivodocx, $textojson, $teses, $dadosXmlArteria, $nomedapasta, $sintesefatica, $paragrafosConclusao);
		$phpWord = new \PhpOffice\PhpWord\PhpWord();

		$dadosDoProcesso = json_encode($dadosXmlArteria) ? json_decode($dadosXmlArteria, true) : [];

		$numeroscpjud = $dadosDoProcesso['17591||'] ?? null; //5454 MA
		$valordacausa = $dadosDoProcesso['20836||'] ?? null; //20000
		$nome_do_autor  = $dadosDoProcesso['16107||'] ?? null; //MARIA MARTINS
		$numerocnj = $dadosDoProcesso['20378||'] ?? null; //01456-45-2026.4.01.4444
		$datadistribuicao = $dadosDoProcesso['18342||'] ?? null; //01/01/2005

		// ==========================================
		// REGRA DO PLURAL
		// ==========================================
		$sufixoPlural = '';
		$textoBruto = is_string($textojson) ? $textojson : json_encode($textojson);
		preg_match_all('/\b\d{14}\b/', $textoBruto, $matches);
		if (!empty($matches[0])) {
			$certificadosUnicos = array_unique($matches[0]);
			if (count($certificadosUnicos) > 1) {
				$sufixoPlural = 's';
			}
		}
		// ==========================================

		// --- (CERTIFICADO E DATA) ---
		$numeroCertificado = $dataDoCertificado = null;
		$blocosJson = is_string($textojson) ? json_decode($textojson, true) : $textojson;

		foreach ($blocosJson['blocos'] ?? [] as $bloco) {
			if (($bloco['tipo'] ?? '') === 'texto' && !empty($bloco['valor'])) {
				$valor = $bloco['valor'];

				// Regra unificada do Certificado: o "nº" passou a ser opcional com (?: n[º°o])?
				if (!$numeroCertificado && preg_match('/certificado(?: individual)?(?: n[º°o])?[\s\n]+(\d+)/ui', $valor, $m)) {
					$numeroCertificado = $m[1];
				}

				// Regra unificada da Data: procura por "adquirido em" OU "periodo de vigencia"
				if (!$dataDoCertificado && preg_match('/(?:adquirido em|per[ií]odo de vig[eê]ncia)[\s\n]+(\d{2}\/\d{2}\/\d{4})/ui', $valor, $m)) {
					$dataDoCertificado = $m[1];
				}

				// Interrompe o loop se já encontrou ambos
				if ($numeroCertificado && $dataDoCertificado) break;
			}
		}

		// ---TEMPO DECORRIDO ---
		$anosDecorridosInt = 0;
		$anosDecorridosFormatado = "00";
		if ($dataDoCertificado && $datadistribuicao) {
			$dtCertificado = \DateTime::createFromFormat('d/m/Y', $dataDoCertificado);
			$dtDistribuicao = \DateTime::createFromFormat('d/m/Y', $datadistribuicao);
			if ($dtCertificado && $dtDistribuicao) {
				$diferenca = $dtCertificado->diff($dtDistribuicao);
				$anosDecorridosInt = $diferenca->y;
				$anosDecorridosFormatado = str_pad($anosDecorridosInt, 2, '0', STR_PAD_LEFT);
			}
		}

		// dd($datadistribuicao, "até ", $dataDoCertificado, " São ", $anosDecorridosInt, " anos.");


		$fusoSaoPaulo = new \DateTimeZone('America/Sao_Paulo');
		$agora = new \DateTime('now', $fusoSaoPaulo);
		$dataHoraFormatada = $agora->format('d/m/Y');

		$valor = 'R$ ' . number_format((float)$valordacausa, 2, ',', '.');

		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

		$section = $phpWord->addSection([
			'marginTop' => 1701 //03cm
		]);

		$caminhoArquivo = public_path('automacao_dossie/' . $nomedapasta . '/dossie_' . rand() . '.docx');

		$diretorio = dirname($caminhoArquivo);
		if (!is_dir($diretorio)) {
			mkdir($diretorio, 0777, true); // Cria a pasta recursivamente se não existir
		}

		$imagemfundo = public_path('automacao_dossie/imageheader.jpg');

		// === 1. IMAGEM DE FUNDO (PAPEL TIMBRADO) ===
		$header = $section->addHeader();
		if (file_exists($imagemfundo)) {
			$header->addImage($imagemfundo, [
				'width'            => 595.27,
				'height'           => 841.89,
				'positioning'      => 'absolute',
				'posHorizontal'    => 'center', // Centraliza horizontalmente
				'posHorizontalRel' => 'page',   // Em relação à página
				'posVertical'      => 'center', // Centraliza verticalmente
				'posVerticalRel'   => 'page',   // Em relação à página
				'wrappingStyle'    => 'behind', // Mantém atrás do texto
			]);
		}

		// ==========================================
		// 1. A TABELA
		// ==========================================
		$section->addTextBreak(1, ['size' => 15]);

		$section->addText(
			"Espelho de Dossiê",
			['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000'], // Estilo do Texto
			['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
		);

		$section->addTextBreak(1, ['size' => 15]);

		$tabelaTituloProcesso = $section->addTable([
			'borderSize'  => 6,
			'borderColor' => '000000',
			'cellMargin'  => 50
		]);

		// Cria a linha
		$tabelaTituloProcesso->addRow();

		// COLUNA 1 (Largura: 4500)
		$coluna1 = $tabelaTituloProcesso->addCell(4500, ['valign' => 'center']);
		$coluna1->addText(
			"Data: " . $dataHoraFormatada,
			['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']
		);

		// COLUNA 2 (Largura: 4500)
		$coluna2 = $tabelaTituloProcesso->addCell(4500, ['valign' => 'center']);
		$coluna2->addText(
			"SCPJUD:" . $numeroscpjud,
			['bold' => false, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']
		);

		$section->addTextBreak(1, ['size' => 15]);

		///////////////////DADOS DO PROCESSO//////////////////////////// 

		// =========================================================================
		// === DADOS DO PROCESSO JUDICIAL (QUADRO 1) ===
		// =========================================================================

		$empresa  = $sintesefatica['empresa'] ?? '';
		$alegacao = $sintesefatica['alegacao'] ?? '';
		$pedido_1 = $sintesefatica['pedido_1'] ?? '';
		$pedido_2 = $sintesefatica['pedido_2'] ?? '';

		$section->addTextBreak(1, ['size' => 15]);

		// TÍTULO CENTRALIZADO E SUBLINHADO (Fora do quadro)
		$section->addText(
			"DADOS DO PROCESSO JUDICIAL",
			['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'underline' => 'single', 'color' => '000000'],
			['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 240]
		);

		// CRIANDO O PRIMEIRO QUADRO (Apenas Autor, Processo e Valor)
		$tabelaQuadro1 = $section->addTable([
			'borderSize'  => 6,
			'borderColor' => '000000',
			'cellMargin'  => 100
		]);
		$tabelaQuadro1->addRow();
		$celulaQuadro1 = $tabelaQuadro1->addCell(9000, ['valign' => 'top']);

		// LINHA 1: AUTOR
		$runAutor = $celulaQuadro1->addTextRun(['spaceAfter' => 360]);
		$runAutor->addText("AUTOR: ", ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);
		$runAutor->addText(mb_strtoupper($nome_do_autor, 'UTF-8'), ['bold' => false, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);

		// LINHA 2: PROCESSO
		$runProcesso = $celulaQuadro1->addTextRun(['spaceAfter' => 360]);
		$runProcesso->addText("PROCESSO: ", ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);
		$runProcesso->addText($numerocnj, ['bold' => false, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);

		// LINHA 3: VALOR DA CAUSA
		$runValor = $celulaQuadro1->addTextRun(['spaceAfter' => 120]);
		$runValor->addText("VALOR DA CAUSA: ", ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);
		$runValor->addText($valor, ['bold' => false, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);

		$section->addTextBreak(1, ['size' => 15]);

		// =========================================================================
		// === SÍNTESE FÁTICA E PEDIDOS (QUADRO 2) ===
		// =========================================================================

		$section->addText(
			"SÍNTESE FÁTICA E PEDIDOS",
			['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'underline' => 'single', 'color' => '000000'],
			['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 240]
		);

		$tabelaQuadro2 = $section->addTable([
			'borderSize'  => 6,
			'borderColor' => '000000',
			'cellMargin'  => 100
		]);
		$tabelaQuadro2->addRow();
		$celulaQuadro2 = $tabelaQuadro2->addCell(9000, ['valign' => 'top']);

		$celulaQuadro2->addText("\t Trata-se de ação ajuizada em face da $empresa e a parte autora alega: ", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t \t (i) $alegacao ;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 60, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t \t (ii) Diante disso, ingressou com a presente demanda judicial;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 240, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

		$celulaQuadro2->addText("\t Nesse sentido, requer:", ['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t \t (i) $pedido_1 ;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 60, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t \t (ii) $pedido_2 ;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

		$section->addTextBreak(1, ['size' => 15]);

		// =========================================================================
		// === 3. PROCESSAMENTO DO JSON (CRIANDO A TABELA E INSERINDO IMAGENS) ===
		// =========================================================================

		$dadosJson = json_decode($textojson, true);

		if (isset($dadosJson['sucesso']) && $dadosJson['sucesso'] === true && isset($dadosJson['blocos'])) {

			// --- ESTILOS ---
			$estiloTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
			$estiloParagrafoTabela = ['lineHeight' => 1.5];

			$phpWord->addParagraphStyle('EstiloABNTGlobal', [
				'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
				'indentation' => ['firstLine' => 708],
				'lineHeight'  => 1.5,
				'spaceAfter'  => 120
			]);

			$estiloParagrafoTopico = [
				'spaceBefore' => 240,
				'lineHeight'  => 1.5,
				'spaceAfter'  => 240,
				'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
			];

			$estiloTituloDados = [
				'spaceAfter' => 120,
				'lineHeight'  => 1.5,
				'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
			];

			$estiloTabelaNome = 'TabelaDadosContrato';
			$phpWord->addTableStyle($estiloTabelaNome, [
				'borderSize'  => 6,
				'borderColor' => '4F81BD',
				'cellMargin'  => 50,
			]);

			// === PRÉ-VERIFICAÇÃO DE PADRÃO (FALLBACK) ===
			$possuiPadrao = false;
			foreach ($dadosJson['blocos'] as $blocoCheck) {
				if ($blocoCheck['tipo'] === 'texto') {
					$linhasCheck = explode("\n", $blocoCheck['valor']);
					foreach ($linhasCheck as $linhaC) {
						if (preg_match('/^I\s*[–\-—\.]\s*DADOS DO CONTRATO/ui', trim($linhaC))) {
							$possuiPadrao = true;
							break 2;
						}
					}
				}
			}

			$podeImprimir = !$possuiPadrao;
			$encerrouDocumento = false;

			// =========================================================================
			// === LISTA DE PARÁGRAFOS PARA SUPRIMIR (REMOVER) ===
			// =========================================================================
			$trechosParaOmitir = [
				"2. OBJETIVO DO SEGURO",
				"2.1 O presente seguro tem por objetivo amortizar ou custear, total ou parcialmente",
			];

			// =========================================================================
			// === LOOP PRINCIPAL DOS BLOCOS ===
			// =========================================================================
			foreach ($dadosJson['blocos'] as $bloco) {

				if ($encerrouDocumento) {
					break;
				}

				// === SE O BLOCO FOR TEXTO ===
				if ($bloco['tipo'] === 'texto') {

					$linhasDoTexto = explode("\n", $bloco['valor']);

					$emTabela = false;
					$chaves = [];
					$valores = [];
					$passoTabela = 'chave';
					$ignorandoRelato = false;

					foreach ($linhasDoTexto as $linha) {
						$linhaLimpa = trim($linha);

						// === NOVA REGRA: SUPRESSÃO DINÂMICA DE PARÁGRAFOS ===
						$deveSuprimirLinhaAtual = false;

						// 1. Verifica os trechos fixos da lista
						foreach ($trechosParaOmitir as $trecho) {
							if (mb_stripos($linhaLimpa, $trecho) !== false) {
								$deveSuprimirLinhaAtual = true;
								break;
							}
						}

						// 2. Regra dinâmica: remove textos sem números (que não sejam da tabela, Obs ou títulos)
						if (!$deveSuprimirLinhaAtual && !$emTabela && $linhaLimpa !== '') {
							$temNumero    = preg_match('/\d/', $linhaLimpa);
							$iniciaComObs = preg_match('/^obs/ui', $linhaLimpa);
							$eTitulo      = preg_match('/^[IVX]+\s*[–\-—\.]/ui', $linhaLimpa) || preg_match('/^[IVX]+\s*[–\-—\.]*\s*CONCLUS[ÃA]O/ui', $linhaLimpa);

							// Se NÃO tem número, NÃO inicia com Obs e NÃO é um título estrutural -> suprime!
							if (!$temNumero && !$iniciaComObs && !$eTitulo) {
								$deveSuprimirLinhaAtual = true;
							}
						}

						if ($deveSuprimirLinhaAtual) {
							continue; // Pula esta linha e vai para a próxima sem imprimi-la
						}
						// ======================================================

						// REGRA DA CONCLUSÃO
						if (preg_match('/^[IVX]+\s*[–\-—\.]*\s*CONCLUS[ÃA]O/ui', $linhaLimpa)) {
							$encerrouDocumento = true;
							break;
						}

						// === REGRA 1: SUPRESSÃO DO BREVE RELATO ===
						if ($ignorandoRelato) {
							if (preg_match('/^(III|IV|V|VI|VII|VIII|IX|X)\s*[–\-—\.]/ui', $linhaLimpa)) {
								$ignorandoRelato = false;
							} else {
								continue;
							}
						}

						// REGRA 2: Gatilho para iniciar a impressão na seção de DADOS
						if (preg_match('/^I\s*[–\-—\.]\s*DADOS DO CONTRATO/ui', $linhaLimpa)) {
							$podeImprimir = true;
							$section->addText($linhaLimpa, ['bold' => true, 'name' => 'Tahoma', 'size' => 11], $estiloTituloDados);
							$emTabela = true;
							continue;
						}

						if (!$podeImprimir || $linhaLimpa === '') {
							continue;
						}

						// LÓGICA DE MONTAGEM DA TABELA E SUPRESSÃO IMEDIATAMENTE APÓS ELA
						if ($emTabela) {
							if (preg_match('/^II\s*[–\-—\.]/ui', $linhaLimpa)) {
								$emTabela = false;

								// --- DESENHA A TABELA ---
								$table = $section->addTable($estiloTabelaNome);
								for ($i = 0; $i < count($chaves); $i++) {
									$bgColor = ($i % 2 === 0) ? 'FFFFFF' : 'E9EDF4';
									$table->addRow();
									$cell1 = $table->addCell(4000, ['bgColor' => $bgColor, 'valign' => 'center']);
									$cell1->addText($chaves[$i], ['bold' => true, 'name' => 'Tahoma', 'size' => 10]);
									$valorAtual = isset($valores[$i]) ? $valores[$i] : '';
									$cell2 = $table->addCell(5000, ['bgColor' => $bgColor, 'valign' => 'center']);
									$cell2->addText($valorAtual, ['name' => 'Tahoma', 'size' => 10]);
								}

								// --- VERIFICA SE ESTE TÓPICO "II" É O BREVE RELATO ---
								if (preg_match('/^II\s*[–\-—\.]\s*BREVE RELATO DA INICIAL/ui', $linhaLimpa)) {
									$ignorandoRelato = true;
								} else {
									$section->addText($linhaLimpa, ['bold' => true, 'name' => 'Tahoma', 'size' => 11], $estiloParagrafoTopico);
								}
								continue;
							}

							if ($passoTabela === 'chave') {
								$chaves[] = mb_strtoupper($linhaLimpa, 'UTF-8');
								$passoTabela = 'valor';
							} else {
								$valores[] = $linhaLimpa;
								$passoTabela = 'chave';
							}
						} else {
							// FORA DA TABELA
							if (preg_match('/^II\s*[–\-—\.]\s*BREVE RELATO DA INICIAL/ui', $linhaLimpa)) {
								$ignorandoRelato = true;
								continue;
							}

							if (preg_match('/^III\s*[–\-—\.]\s*(?:DOS\s+)?FATOS/ui', $linhaLimpa)) {
								$section->addText("INFORMAÇÕES TÉCNICAS - CONSIDERAÇÕES DA SEGURADORA", ['bold' => true, 'name' => 'Tahoma', 'size' => 11], $estiloParagrafoTopico);
								continue;
							}

							if (preg_match('/^[IVX]+\s*[–-]/', $linhaLimpa)) {
								$section->addText($linhaLimpa, ['bold' => true, 'name' => 'Tahoma', 'size' => 11], $estiloParagrafoTopico);
							} else {
								$section->addText("\t" . $linhaLimpa, $estiloTexto, 'EstiloABNTGlobal');
							}
						}
					}

					if ($emTabela && count($chaves) > 0) {
						$table = $section->addTable($estiloTabelaNome);
						for ($i = 0; $i < count($chaves); $i++) {
							$bgColor = ($i % 2 === 0) ? 'FFFFFF' : 'E9EDF4';
							$table->addRow();
							$cell1 = $table->addCell(4000, ['bgColor' => $bgColor, 'valign' => 'center']);
							$cell1->addText($chaves[$i], ['bold' => true, 'name' => 'Tahoma', 'size' => 12], $estiloParagrafoTabela);
							$valorAtual = isset($valores[$i]) ? $valores[$i] : '';
							$cell2 = $table->addCell(5000, ['bgColor' => $bgColor, 'valign' => 'center']);
							$cell2->addText($valorAtual, ['name' => 'Tahoma', 'size' => 12], $estiloParagrafoTabela);
						}
						$section->addTextBreak(1);
					}
				}

				// === SE O BLOCO FOR IMAGEM ===
				elseif ($bloco['tipo'] === 'imagem') {
					if (!$podeImprimir || $encerrouDocumento) {
						continue;
					}

					$caminhoFisicoImagem = public_path(ltrim($bloco['url_render'], '/'));
					$caminhoFisicoImagem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoFisicoImagem);

					if (file_exists($caminhoFisicoImagem)) {
						$section->addTextBreak(1);
						$section->addImage($caminhoFisicoImagem, [
							'width'     => 450,
							'height'    => null,
							'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
						]);
						$section->addTextBreak(1);
					} else {
						$section->addText('[Imagem não localizada no servidor: ' . $bloco['nome'] . ']', ['color' => 'FF0000', 'italic' => true]);
					}
				}
			}
		} else {
			$section->addText('Erro: JSON inválido ou vazio.', ['color' => 'FF0000']);
		}

		// =========================================================================
		// === RESTANTE DO DOCUMENTO (Ressalva, Dados do Processo, Teses) ===
		// =========================================================================

		$section->addTextBreak(1, ['name' => 'Tahoma', 'size' => 12], ['lineHeight' => 1.5]);

		$mensagemDefesa = "Ressalta-se que o presente dossiê constitui uma sugestão de defesa, elaborada com base nas informações disponíveis até o momento. Cabendo ao prestador a análise criteriosa do conteúdo, podendo acrescentar, ajustar ou suprimir quaisquer elementos que julgar pertinentes para melhor atender à sua estratégia de defesa.";

		$phpWord->addParagraphStyle('EstiloRessalva', [
			'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
			'spaceBefore' => 0,
			'spaceAfter' => 120,
			'indentation' => ['firstLine' => 720]
		]);

		$section->addText("\t" . $mensagemDefesa, ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => 'FF0000'], 'EstiloRessalva');

		$section->addTextBreak(1, ['size' => 15]);

		$tabelaTituloProcesso3 = $section->addTable([
			'borderSize'  => 6,
			'borderColor' => '000000',
			'cellMargin'  => 50
		]);
		$tabelaTituloProcesso3->addRow();
		$celulaTitulo = $tabelaTituloProcesso3->addCell(9000, ['valign' => 'center']);
		$celulaTitulo->addText(" DAS RECOMENDAÇÕES PARA A DEFESA:", ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);

		// =========================================================
		// === AQUI SÃO EXIBIDAS AS TESES (ORGANIZADAS POR GRUPO) ===
		// =========================================================

		$tesesAgrupadas = [
			'preliminares' => [],
			'prejudiciais' => [],
			'merito'       => []
		];

		foreach ($teses as $tese) {
			$categoria = $tese->categoria ?? 'merito';
			$tesesAgrupadas[$categoria][] = $tese;
		}

		$ordemCategorias = [
			'preliminares' => 'DAS PRELIMINARES',
			'prejudiciais' => 'DAS PREJUDICIAIS',
			'merito'       => 'DO MÉRITO'
		];

		$romanos = [
			1 => 'I',
			2 => 'II',
			3 => 'III',
			4 => 'IV',
			5 => 'V',
			6 => 'VI',
			7 => 'VII',
			8 => 'VIII',
			9 => 'IX',
			10 => 'X',
			11 => 'XI',
			12 => 'XII',
			13 => 'XIII',
			14 => 'XIV',
			15 => 'XV',
			16 => 'XVI',
			17 => 'XVII',
			18 => 'XVIII',
			19 => 'XIX',
			20 => 'XX',
			21 => 'XXI',
			22 => 'XXII',
			23 => 'XXIII',
			24 => 'XXIV',
			25 => 'XXV',
			26 => 'XXVI',
			27 => 'XXVII',
			28 => 'XXVIII',
			29 => 'XXIX',
			30 => 'XXX',
			31 => 'XXXI',
			32 => 'XXXII',
			33 => 'XXXIII',
			34 => 'XXXIV',
			35 => 'XXXV',
			36 => 'XXXVI',
			37 => 'XXXVII',
			38 => 'XXXVIII',
			39 => 'XXXIX',
			40 => 'XL'
		];

		$grupoContador = 1;

		foreach ($ordemCategorias as $chaveCategoria => $tituloCategoria) {

			if (empty($tesesAgrupadas[$chaveCategoria])) {
				continue;
			}

			$numRomanoGrupo = $romanos[$grupoContador] ?? $grupoContador;

			$section->addText(
				$numRomanoGrupo . ". " . $tituloCategoria,
				['name' => 'Tahoma', 'size' => 12, 'bold' => true, 'color' => '000000'],
				['spaceBefore' => 240, 'spaceAfter' => 240, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
			);

			$subContador = 1;

			foreach ($tesesAgrupadas[$chaveCategoria] as $tese) {

				if (!empty($tese->conteudo) && is_string($tese->conteudo)) {
					$tagsBusca = [
						'%DATACONTRATACAO%',
						'%DATAAJUIZAMENTOACAO%',
						'%TEMPODECORRIDO%',
						'%NUMEROCERTIFICADO%',
						'%S_%'
					];

					$valoresSubstituicao = [
						$dataDoCertificado ?? '[DATA DA CONTRATAÇÃO NÃO ENCONTRADA]',
						$datadistribuicao ?? '[DATA DO AJUIZAMENTO NÃO ENCONTRADA]',
						$anosDecorridosFormatado ?? '00',
						$numeroCertificado ?? '[NÚMERO DO CERTIFICADO NÃO ENCONTRADO]',
						$sufixoPlural
					];

					// str_ireplace ignora maiúsculas/minúsculas, então funciona com %DATACONTRATACAO% ou %DataContratacao%
					$tese->conteudo = str_ireplace($tagsBusca, $valoresSubstituicao, $tese->conteudo);
				}
				if (isset($tese->tipo) && $tese->tipo === 'citacao') {
					$estiloCitacaoTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
					$estiloCitacaoParagrafo = [
						'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
						'indentation' => ['left' => 2268], // Recuo da citação
						'lineHeight'  => 1.0,
						'spaceBefore' => 240,
						'spaceAfter'  => 240
					];

					if (!empty($tese->conteudo)) {

						if (strip_tags($tese->conteudo) !== $tese->conteudo) {
							preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $tese->conteudo, $matches);

							if (!empty($matches[1])) {
								$paragrafosHtml = $matches[1];
							} else {
								$paragrafosHtml = explode("\n", $tese->conteudo);
							}

							foreach ($paragrafosHtml as $htmlDoParagrafo) {
								$htmlLimpo = trim(strip_tags($htmlDoParagrafo));

								if ($htmlLimpo !== '') {
									$textRun = $section->addTextRun($estiloCitacaoParagrafo);
									$htmlComFonte = '<span style="font-family: Tahoma; font-size: 12pt; color: #1B2232;">' . trim($htmlDoParagrafo) . '</span>';
									\PhpOffice\PhpWord\Shared\Html::addHtml($textRun, $htmlComFonte, false, false);
								}
							}
						} else {
							$paragrafos = explode("\n", $tese->conteudo);
							foreach ($paragrafos as $paragrafo) {
								$paragrafoLimpo = trim($paragrafo);
								if ($paragrafoLimpo !== '') {
									$section->addText($paragrafoLimpo, $estiloCitacaoTexto, $estiloCitacaoParagrafo);
								}
							}
						}
					}
				} elseif (isset($tese->tipo) && $tese->tipo === 'titulo') {
					$estiloTituloCustomTexto = [
						'name'      => 'Tahoma',
						'size'      => 12,
						'bold'      => true,
						'underline' => 'single',
						'color'     => '1B2232'
					];
					$estiloTituloCustomParagrafo = [
						'lineHeight'        => 1.5,
						'spaceBefore'       => 240,
						'spaceAfter'        => 240,
						'contextualSpacing' => false,
						'alignment'         => \PhpOffice\PhpWord\SimpleType\Jc::LEFT
					];

					$numRomanoSub = $romanos[$subContador] ?? $subContador;
					$numeracaoFinal = $numRomanoGrupo . "." . $numRomanoSub;

					$section->addText($numeracaoFinal . "\t" . $tese->conteudo, $estiloTituloCustomTexto, $estiloTituloCustomParagrafo);
					$section->addText("\xC2\xA0", ['name' => 'Tahoma', 'size' => 12], ['lineHeight' => 1.5]);

					$subContador++;
				} elseif (isset($tese->tipo) && $tese->tipo === 'imagem') {
					$caminhoFisicoImagem = public_path(ltrim($tese->conteudo, '/'));
					$caminhoFisicoImagem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoFisicoImagem);

					if (file_exists($caminhoFisicoImagem)) {
						$section->addTextBreak(1);
						$section->addImage($caminhoFisicoImagem, [
							'width'     => 450,
							'height'    => null,
							'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
						]);
						$section->addTextBreak(1);
					} else {
						$section->addText('[Erro: Imagem da tese não localizada no servidor]', ['color' => 'FF0000', 'italic' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
					}
				} else {
					if (!empty($tese->conteudo)) {
						if (strip_tags($tese->conteudo) !== $tese->conteudo) {

							preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $tese->conteudo, $matches);

							if (!empty($matches[1])) {
								$paragrafosHtml = $matches[1];
							} else {
								$paragrafosHtml = explode("\n", $tese->conteudo);
							}

							$estiloParagrafoABNT = [
								'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
								'lineHeight'  => 1.5,
								'spaceAfter'  => 120,
								'indentation' => ['firstLine' => 720]
							];

							foreach ($paragrafosHtml as $htmlDoParagrafo) {
								$htmlLimpo = trim(strip_tags($htmlDoParagrafo));

								if ($htmlLimpo !== '') {
									$textRun = $section->addTextRun($estiloParagrafoABNT);
									$textRun->addText("\t", ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232']);

									$htmlComFonte = '<span style="font-family: Tahoma; font-size: 12pt; color: #1B2232;">' . trim($htmlDoParagrafo) . '</span>';
									\PhpOffice\PhpWord\Shared\Html::addHtml($textRun, $htmlComFonte, false, false);
								}
							}
						} else {
							$paragrafos = explode("\n", $tese->conteudo);
							foreach ($paragrafos as $paragrafo) {
								$paragrafoLimpo = trim($paragrafo);
								if ($paragrafoLimpo !== '') {
									$estiloTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
									$section->addText("\t" . $paragrafoLimpo, $estiloTexto, [
										'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
										'lineHeight' => 1.5,
										'spaceAfter' => 120,
										'indentation' => ['firstLine' => 720]
									]);
								}
							}
						}
					}
				}
			}

			$grupoContador++;
		}

		// =========================================================================
		// === CONCLUSÃO ===
		// =========================================================================
		$temConclusao = false;
		foreach (['preliminares', 'prejudiciais', 'merito'] as $cat) {
			if (!empty($paragrafosConclusao[$cat])) {
				$temConclusao = true;
				break;
			}
		}

		if ($temConclusao) {

			$section->addTextBreak(1, ['size' => 15]);

			$tabelaConclusao = $section->addTable([
				'borderSize'  => 6,
				'borderColor' => '000000',
				'cellMargin'  => 50
			]);
			$tabelaConclusao->addRow();
			$celulaTituloConclusao = $tabelaConclusao->addCell(9000, ['valign' => 'center']);
			$celulaTituloConclusao->addText(" DA CONCLUSÃO:", ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);

			$section->addTextBreak(1, ['size' => 11], ['lineHeight' => 1.5]);


			$estiloParagrafoIntrodutorio = [
				'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
				'lineHeight'  => 1.5,
				'spaceAfter'  => 120,
				'indentation' => ['firstLine' => 720]
			];
			$section->addText(
				"\tAnte todo o exposto e fundamentado, requer-se a Vossa Excelência o acolhimento das teses defensivas apresentadas, nos seguintes termos:",
				['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'],
				$estiloParagrafoIntrodutorio
			);



			$estiloTextoConclusao = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
			$estiloParagrafoConclusao = [
				'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
				'lineHeight'  => 1.5,
				'spaceAfter'  => 120,
				'indentation' => ['firstLine' => 720]
			];

			$estiloSubtopico = [
				'spaceBefore' => 240,
				'spaceAfter'  => 120,
				'indentation' => ['left' => 360]
			];

			$letra = 'a';

			// 1. PRELIMINARES
			if (!empty($paragrafosConclusao['preliminares'])) {
				$section->addTextBreak(1);
				$section->addText($letra . ") Preliminarmente:", ['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'], $estiloSubtopico);
				foreach ($paragrafosConclusao['preliminares'] as $paragrafo) {

					$section->addText("\t" . $paragrafo, $estiloTextoConclusao, $estiloParagrafoConclusao);
				}
				$letra++;
			}

			// 2. PREJUDICIAIS DE MÉRITO
			if (!empty($paragrafosConclusao['prejudiciais'])) {
				$section->addTextBreak(1);
				$section->addText($letra . ") Como prejudicial de mérito:", ['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'], $estiloSubtopico);
				foreach ($paragrafosConclusao['prejudiciais'] as $paragrafo) {
					$section->addText("\t" . $paragrafo, $estiloTextoConclusao, $estiloParagrafoConclusao);
				}
				$letra++;
			}

			// 3. MÉRITO
			if (!empty($paragrafosConclusao['merito'])) {
				$section->addTextBreak(1);
				$section->addText($letra . ") No mérito:", ['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'], $estiloSubtopico);
				foreach ($paragrafosConclusao['merito'] as $paragrafo) {
					$section->addText("\t" . $paragrafo, $estiloTextoConclusao, $estiloParagrafoConclusao);
				}
			}
		}

		// === 5. SALVAR ARQUIVO FINAL ===
		$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$objWriter->save($caminhoArquivo);

		return $caminhoArquivo;
	}
}
