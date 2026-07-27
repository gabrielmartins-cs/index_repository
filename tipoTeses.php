	// =========================================================
		// === AQUI SÃO EXIBIDAS AS minhas TESES ===
		// =========================================================

		$contador = 1;
		foreach ($teses as $tese) {
			if (isset($tese->tipo) && $tese->tipo === 'citacao') {
				$estiloCitacaoTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
				$estiloCitacaoParagrafo = [
					'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
					'indentation' => ['left' => 2268],
					'lineHeight'  => 1.0,
					'spaceBefore' => 240,
					'spaceAfter'  => 240
				];
				$section->addText($tese->conteudo, $estiloCitacaoTexto, $estiloCitacaoParagrafo);
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

				$section->addText($contador . " - " . $tese->conteudo, $estiloTituloCustomTexto, $estiloTituloCustomParagrafo);
				$section->addText("\xC2\xA0", ['name' => 'Tahoma', 'size' => 12], ['lineHeight' => 1.5]);
				$contador++;

				// =========================================================
				// === NOVO BLOCO: TRATAMENTO DE IMAGEM NA TESE ===
				// =========================================================
			} elseif (isset($tese->tipo) && $tese->tipo === 'imagem') {

				// Extrai o caminho salvo no banco e converte para o caminho físico do servidor
				$caminhoFisicoImagem = public_path(ltrim($tese->conteudo, '/'));

				// Padroniza as barras (\ ou /) para o sistema operacional em uso
				$caminhoFisicoImagem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoFisicoImagem);

				// Verifica se o arquivo realmente existe na pasta antes de tentar inserir
				if (file_exists($caminhoFisicoImagem)) {
					$section->addTextBreak(1); // Espaço antes da imagem
					$section->addImage($caminhoFisicoImagem, [
						'width'     => 450, // Largura padrão (pode ajustar)
						'height'    => null, // Mantém a proporção
						'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
					]);
					$section->addTextBreak(1); // Espaço depois da imagem
				} else {
					// Aviso discreto no Word caso a imagem tenha sido apagada do disco
					$section->addText('[Erro: Imagem da tese não localizada no servidor]', ['color' => 'FF0000', 'italic' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
				}
				// =========================================================

			} else {
				if (isset($tese->conteudo)) {
					$paragrafos = explode("\n", $tese->conteudo);
				} else {
					dd($tese);
				}

				foreach ($paragrafos as $paragrafo) {
					$paragrafoLimpo = trim($paragrafo);
					if ($paragrafoLimpo !== '') {
						$estiloTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
						$section->addText("\t" . $paragrafoLimpo, $estiloTexto, [
							'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
							'lineHeight' => 1.5,
							'spaceAfter' => 120
						]);
					}
				}
			}
		}


essa é minha função de montar o docuemto: <?php

namespace App\Http\Controllers;



class WordxDossieController extends Controller
{

	public function criarWordx($idarquivodocx, $textojson, $teses, $dadosXmlArteria, $nomedapasta, $sintesefatica, $paragrafosConclusao)
	{

		$phpWord = new \PhpOffice\PhpWord\PhpWord();

		$dadosDoProcesso = json_encode($dadosXmlArteria) ? json_decode($dadosXmlArteria, true) : [];

		$numeroscpjud = $dadosDoProcesso['17591||'] ?? null; //5454 MA
		$valordacausa = $dadosDoProcesso['20836||'] ?? null; //20000
		$nome_do_autor  = $dadosDoProcesso['16107||'] ?? null; //MARIA MARTINS
		$numerocnj = $dadosDoProcesso['20378||'] ?? null; //01456-45-2026.4.01.4444

		$fusoSaoPaulo = new \DateTimeZone('America/Sao_Paulo');
		$agora = new \DateTime('now', $fusoSaoPaulo);
		$dataHoraFormatada = $agora->format('d/m/Y');

		$valor = 'R$ ' . number_format((float)$valordacausa, 2, ',', '.');


		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);

		$section = $phpWord->addSection([
			'marginTop' => 1701 //03cm
		]);

		// $caminhoArquivo = __DIR__ . '/../../public/automacao_dossie/' .  $nomedapasta . '/dossie_' . rand() . '.docx';

		$caminhoArquivo = public_path('automacao_dossie/' . $nomedapasta . '/dossie_' . rand() . '.docx');

		$diretorio = dirname($caminhoArquivo);
		if (!is_dir($diretorio)) {
			mkdir($diretorio, 0777, true); // Cria a pasta recursivamente se não existir
		}

		// $imagemfundo = __DIR__ . '/../../public/automacao_dossie/imageheader.jpg';
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
			'cellMargin'  => 100 // Margem interna para o texto não colar na borda
		]);
		$tabelaQuadro1->addRow();
		$celulaQuadro1 = $tabelaQuadro1->addCell(9000, ['valign' => 'top']);

		// LINHA 1: AUTOR
		$runAutor = $celulaQuadro1->addTextRun(['spaceAfter' => 360]); // spaceAfter dá o espaçamento entre as linhas igual à imagem
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

		// CRIANDO O SEGUNDO QUADRO (Para o restante das informações)

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

		$celulaQuadro2->addText("Trata-se de ação ajuizada em face da $empresa e a parte autora alega: ", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t(i) $alegacao ;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 60, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t(ii) Diante disso, ingressou com a presente demanda judicial;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 240, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

		$celulaQuadro2->addText("Nesse sentido, requer:", ['bold' => true, 'name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t(i) $pedido_1 ;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 60, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
		$celulaQuadro2->addText("\t(ii) $pedido_2 ;", ['name' => 'Tahoma', 'size' => 12, 'color' => '000000'], ['spaceAfter' => 120, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);

		$section->addTextBreak(1, ['size' => 15]);
		///////////////////////////////////////////////

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

			// =========================================================================
			// === PRÉ-VERIFICAÇÃO DE PADRÃO (FALLBACK) ===
			// =========================================================================
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

					// NOVA VARIÁVEL DE CONTROLE
					$ignorandoRelato = false;

					foreach ($linhasDoTexto as $linha) {
						$linhaLimpa = trim($linha);

						// REGRA DA CONCLUSÃO
						if (preg_match('/^[IVX]+\s*[–\-—\.]*\s*CONCLUS[ÃA]O/ui', $linhaLimpa)) {
							$encerrouDocumento = true;
							break;
						}

						// === REGRA 1: SUPRESSÃO DO BREVE RELATO ===
						// Se estivermos ignorando o relato, pulamos as linhas até achar o próximo tópico
						if ($ignorandoRelato) {
							// Verifica se encontrou o tópico III, IV, V, etc.
							if (preg_match('/^(III|IV|V|VI|VII|VIII|IX|X)\s*[–\-—\.]/ui', $linhaLimpa)) {
								$ignorandoRelato = false; // Achou o próximo tópico, para de ignorar
							} else {
								continue; // Pula as linhas de texto do relato
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
							// Verifica se iniciou o tópico II usando regex (cobre diferentes tipos de traços)
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
									$ignorandoRelato = true; // Ativa a supressão, NÃO imprime o título
								} else {
									// Se for outro título (ex: II - DOS FATOS), imprime normalmente
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

							// Verifica se esbarrou no Breve Relato de forma "solta" (sem tabela antes)
							if (preg_match('/^II\s*[–\-—\.]\s*BREVE RELATO DA INICIAL/ui', $linhaLimpa)) {
								$ignorandoRelato = true;
								continue;
							}

							// === NOVA REGRA: SUBSTITUI O NOME DO TÓPICO III ===
							if (preg_match('/^III\s*[–\-—\.]\s*DOS FATOS/ui', $linhaLimpa)) {
								$section->addText("INFORMAÇÕES TÉCNICAS - CONSIDERAÇÕES DA SEGURADORA", ['bold' => true, 'name' => 'Tahoma', 'size' => 11], $estiloParagrafoTopico);
								continue;
							}

							// Verifica se é um Título/Tópico normal (Ex: IV - DO DIREITO)
							if (preg_match('/^[IVX]+\s*[–-]/', $linhaLimpa)) {
								$section->addText($linhaLimpa, ['bold' => true, 'name' => 'Tahoma', 'size' => 11], $estiloParagrafoTopico);
							} else {
								$section->addText("\t" . $linhaLimpa, $estiloTexto, 'EstiloABNTGlobal');
							}
						}
					}

					// Garantia final da tabela (permanece igual)
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

					// Pega o caminho
					$caminhoFisicoImagem = public_path(ltrim($bloco['url_render'], '/'));

					// Padroniza as barras para o formato correto do seu Sistema Operacional (Windows)
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
			} // <- FECHA O FOREACH DOS BLOCOS

		} else {
			// <- AQUI É O ELSE CASO O JSON SEJA INVÁLIDO OU VAZIO
			$section->addText('Erro: JSON inválido ou vazio.', ['color' => 'FF0000']);
		}

		// =========================================================================
		// === RESTANTE DO DOCUMENTO (Ressalva, Dados do Processo, Teses) ===
		// =========================================================================

		$section->addTextBreak(1, ['name' => 'Tahoma', 'size' => 12], ['lineHeight' => 1.5]);

		$mensagemDefesa = "Ressalta-se que o presente dossiê constitui uma sugestão de defesa, elaborada com base nas informações disponíveis até o momento. Cabendo ao prestador a análise criteriosa do conteúdo, podendo acrescentar, ajustar ou suprimir quaisquer elementos que julgar pertinentes para melhor atender à sua estratégia de defesa.";

		///////////////////////////////////////////////        
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
		// === AQUI SÃO EXIBIDAS AS TESES ===
		// =========================================================

		$contador = 1;
		foreach ($teses as $tese) {
			if (isset($tese->tipo) && $tese->tipo === 'citacao') {
				$estiloCitacaoTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
				$estiloCitacaoParagrafo = [
					'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
					'indentation' => ['left' => 2268],
					'lineHeight'  => 1.0,
					'spaceBefore' => 240,
					'spaceAfter'  => 240
				];
				$section->addText($tese->conteudo, $estiloCitacaoTexto, $estiloCitacaoParagrafo);
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

				$section->addText($contador . " - " . $tese->conteudo, $estiloTituloCustomTexto, $estiloTituloCustomParagrafo);
				$section->addText("\xC2\xA0", ['name' => 'Tahoma', 'size' => 12], ['lineHeight' => 1.5]);
				$contador++;

				// =========================================================
				// === NOVO BLOCO: TRATAMENTO DE IMAGEM NA TESE ===
				// =========================================================
			} elseif (isset($tese->tipo) && $tese->tipo === 'imagem') {

				// Extrai o caminho salvo no banco e converte para o caminho físico do servidor
				$caminhoFisicoImagem = public_path(ltrim($tese->conteudo, '/'));

				// Padroniza as barras (\ ou /) para o sistema operacional em uso
				$caminhoFisicoImagem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoFisicoImagem);

				// Verifica se o arquivo realmente existe na pasta antes de tentar inserir
				if (file_exists($caminhoFisicoImagem)) {
					$section->addTextBreak(1); // Espaço antes da imagem
					$section->addImage($caminhoFisicoImagem, [
						'width'     => 450, // Largura padrão (pode ajustar)
						'height'    => null, // Mantém a proporção
						'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
					]);
					$section->addTextBreak(1); // Espaço depois da imagem
				} else {
					// Aviso discreto no Word caso a imagem tenha sido apagada do disco
					$section->addText('[Erro: Imagem da tese não localizada no servidor]', ['color' => 'FF0000', 'italic' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
				}
				// =========================================================

			} else {
				if (isset($tese->conteudo)) {
					$paragrafos = explode("\n", $tese->conteudo);
				} else {
					dd($tese);
				}

				foreach ($paragrafos as $paragrafo) {
					$paragrafoLimpo = trim($paragrafo);
					if ($paragrafoLimpo !== '') {
						$estiloTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
						$section->addText("\t" . $paragrafoLimpo, $estiloTexto, [
							'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
							'lineHeight' => 1.5,
							'spaceAfter' => 120
						]);
					}
				}
			}
		}

		// =========================================================================
		// === TÓPICO DA CONCLUSÃO ===
		// =========================================================================

		// Verifica se existem parágrafos de conclusão para imprimir
		if (!empty($paragrafosConclusao)) {

			$section->addTextBreak(1, ['size' => 15]);

			// Cria a tabela de título usando o mesmo padrão visual do seu sistema
			$tabelaConclusao = $section->addTable([
				'borderSize'  => 6,
				'borderColor' => '000000',
				'cellMargin'  => 50
			]);
			$tabelaConclusao->addRow();
			$celulaTituloConclusao = $tabelaConclusao->addCell(9000, ['valign' => 'center']);
			$celulaTituloConclusao->addText(" DA CONCLUSÃO:", ['bold' => true, 'name' => 'Tahoma', 'size' => 11, 'color' => '000000']);

			$section->addTextBreak(1, ['size' => 11], ['lineHeight' => 1.5]);

			// Estilos para o parágrafo (padrão justificado que você já usa)
			$estiloTextoConclusao = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
			$estiloParagrafoConclusao = [
				'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
				'lineHeight' => 1.5,
				'spaceAfter' => 120
			];

			// Percorre cada parágrafo de conclusão recebido do banco
			foreach ($paragrafosConclusao as $paragrafo) {
				// Quebra caso o parágrafo tenha múltiplas linhas/quebras de texto
				$linhasParagrafo = explode("\n", $paragrafo);

				foreach ($linhasParagrafo as $linha) {
					$linhaLimpa = trim($linha);

					if ($linhaLimpa !== '') {
						// Adiciona a tabulação (\t) para dar o espaçamento de primeira linha
						$section->addText("\t" . $linhaLimpa, $estiloTextoConclusao, $estiloParagrafoConclusao);
					}
				}
			}
		}

		// === 5. SALVAR ARQUIVO FINAL ===
		$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$objWriter->save($caminhoArquivo);

		return $caminhoArquivo;

		// dd($caminhoArquivo);
		// === 5. SALVAR ARQUIVO FINAL ===
		$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$objWriter->save($caminhoArquivo);

		return $caminhoArquivo;
	}
}

quando vou chamar a função eu chamo assim: 		$criararquivo = $CriarWordxDossie->criarWordx($idDocx, $jsontextosubsidio, $teseslistabanco, $jsonGerarDocumento, $idDoSubsidio, $sintesefatica, $paragrafoconclusao);

As teses são repassadas nesta variavel: 		$teseslistabanco = $bancotese->numeroTesesBanco($extrairTeses);

o texto recebido na função : public function criarWordx($idarquivodocx, $textojson, $teses, $dadosXmlArteria, $nomedapasta, $sintesefatica, $paragrafosConclusao)
	{
		dd($teses);


o dd é assim: Illuminate\Support\Collection {#2170 ▼
  #items: array:227 [▼
    0 => {#2167 ▼
      +"id": "1505"
      +"numero_tese": "3"
      +"tipo": "titulo"
      +"conteudo": "DOS CONSECTÁRIOS LEGAIS E DOS HONORÁRIOS ADVOCATÍCIOS EM CASO DE CONDENAÇÃO"
      +"created_at": "2026-07-24 15:14:43.523"
      +"updated_at": "2026-07-24 15:14:43.523"
    }
    1 => {#2160 ▼
      +"id": "1506"
      +"numero_tese": "3"
      +"tipo": "normal"
      +"conteudo": "Admitindo-se, apenas para argumentar, a hipótese de condenação desta Seguradora ao pagamento de qualquer indenização em favor do Autor, devem ser estabelecidos  ▶"
      +"created_at": "2026-07-24 15:14:43.800"
      +"updated_at": "2026-07-24 15:14:43.800"
    }
    2 => {#2190 ▼
      +"id": "1507"
      +"numero_tese": "3"
      +"tipo": "normal"
      +"conteudo": "Do termo inicial dos consectários sobre eventual indenização por danos morais"
      +"created_at": "2026-07-24 15:14:44.073"
      +"updated_at": "2026-07-24 15:14:44.073"
    }
    3 => {#2187 ▼
      +"id": "1508"
      +"numero_tese": "3"
      +"tipo": "normal"
      +"conteudo": "Quanto à eventual condenação ao pagamento de indenização por danos morais, nos termos do enunciado nº 362 da súmula do e. STJ e do artigo 407 do Código Civil, a ▶"
      +"created_at": "2026-07-24 15:14:44.347"
      +"updated_at": "2026-07-24 15:14:44.347"
    } ....

quero que veja se é uma tese prelimininar, prejudicial, ou de mérito e cçlassifique as teses em numeros romanos assim, nesta ordem: 

I. DAS PRELIMINARES
II. DAS PREJUDICIAIS
III. DO MÉRITO

de modo que as teses encontradas se encaixem nos subtópicos assim: 
III. DO MÉRITO
III.I 	DA REGULARIDADE DA CONTRATAÇÃO


meu model é assim: 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PalavrasTeses extends Model
{
    use HasFactory;

    // Forçando o Laravel a usar este nome de tabela
    protected $table = 'palavras_teses';

    // Os campos que podem ser gravados
    protected $fillable = [
        'nome',
        'field_id',
        'termos',
        'paragrafo_conclusao',
        'empresa',
        'tipo_tese',
    ];

    protected $casts = [
        'termos' => 'array',
    ];

    public static function dbMapeamentoteses()
    {
        $mapeamentoTeses = DB::table('palavras_teses')->get();
        return $mapeamentoTeses;
    }
}

Meu controller PalavrasTesesController é assim: 

    ///////////TESES//////////////////////

    public function indexTextos()
    {
        $textosAgrupados = DB::table('teses_formatadas')
            ->orderBy('numero_tese')
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('numero_tese');

        return view('dossie.editar_texto_teses', compact('textosAgrupados'));
    }

    public function storeTextos(Request $request)
    {
        $request->validate([
            'numero_tese' => 'required|integer',
            'textos'      => 'required|array',
            'textos.*.tipo' => 'required|string',
        ]);

        $numero_tese = $request->numero_tese;

        foreach ($request->textos as $index => $texto) {
            $conteudo = '';
            $tipo = $texto['tipo'] ?? 'normal';

            // 3. Se o bloco for uma IMAGEM
            if ($tipo === 'imagem') {
                // Verifica se o arquivo veio neste índice específico
                if ($request->hasFile("textos.{$index}.conteudo_imagem")) {
                    $image = $request->file("textos.{$index}.conteudo_imagem");

                    // Gera nome único: timestamp + hash único + extensão
                    $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $image->move(public_path('automacao_dossie/conteudo_teses'), $imageName);

                    $conteudo = 'automacao_dossie/conteudo_teses/' . $imageName;
                }
            }
            // 4. Se o bloco for um TEXTO (Título, Citação ou Normal)
            else {
                $conteudo = $texto['conteudo'] ?? '';
            }

            // 5. Salva no banco apenas se o conteúdo não estiver vazio
            if (!empty($conteudo)) {
                DB::table('teses_formatadas')->insert([
                    'numero_tese' => $numero_tese,
                    'tipo'        => $tipo,
                    'conteudo'    => $conteudo,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Tese completa salva com sucesso!');
    }

    public function updateLoteTextos(Request $request, $numero_tese)
    {
        // Pega todos os registros antigos dessa tese
        $antigos = DB::table('teses_formatadas')->where('numero_tese', $numero_tese)->get();

        // Pega os caminhos físicos das imagens antigas para sabermos o que limpar depois
        $imagensAntigas = $antigos->where('tipo', 'imagem')->pluck('conteudo')->toArray();
        $imagensMantidas = [];

        // Apaga os registros do banco de dados (Vamos recriá-los na ordem nova)
        DB::table('teses_formatadas')->where('numero_tese', $numero_tese)->delete();

        // Se o usuário não excluiu todos os blocos na tela
        if ($request->has('textos')) {
            foreach ($request->textos as $textoData) {
                $tipo = $textoData['tipo'] ?? 'normal';
                $conteudo = '';

                if ($tipo === 'imagem') {
                    // 1. Se ele enviou um ARQUIVO NOVO para substituir
                    if (isset($textoData['conteudo_imagem'])) {
                        $image = $textoData['conteudo_imagem'];
                        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('automacao_dossie/conteudo_teses'), $imageName);
                        $conteudo = 'automacao_dossie/conteudo_teses/' . $imageName;
                    }
                    // 2. Se ele não enviou arquivo novo, mas manteve a imagem que já existia
                    elseif (isset($textoData['caminho_imagem_atual'])) {
                        $conteudo = $textoData['caminho_imagem_atual'];
                        $imagensMantidas[] = $conteudo; // Protege essa imagem da exclusão
                    }
                } else {
                    // Se for texto normal/citação/título
                    $conteudo = $textoData['conteudo'] ?? '';
                }

                // Insere o registro recriando-o. O ID gerado será novo e sequencial!
                if (!empty($conteudo)) {
                    DB::table('teses_formatadas')->insert([
                        'numero_tese' => $numero_tese,
                        'tipo'        => $tipo,
                        'conteudo'    => $conteudo,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                }
            }
        }

        // Limpeza inteligente: Apaga do servidor apenas as imagens que o usuário decidiu excluir no formulário
        foreach ($imagensAntigas as $imgAntiga) {
            if (!in_array($imgAntiga, $imagensMantidas) && File::exists(public_path($imgAntiga))) {
                File::delete(public_path($imgAntiga));
            }
        }

        return redirect()->back()->with('success', 'Tese atualizada e reorganizada com sucesso!');
    }

    public function destroyTeseCompleta($numero_tese)
    {
        // Apaga os arquivos físicos das imagens primeiro
        $registros = DB::table('teses_formatadas')->where('numero_tese', $numero_tese)->get();
        foreach ($registros as $reg) {
            if ($reg->tipo === 'imagem' && File::exists(public_path($reg->conteudo))) {
                File::delete(public_path($reg->conteudo));
            }
        }

        // Exclui a tese toda do banco
        DB::table('teses_formatadas')->where('numero_tese', $numero_tese)->delete();

        return redirect()->back()->with('success', 'Tese excluída completamente!');
    }

onde tenho que alterar para o meu arquivo passe a exibir as teses organizadas? esse é meu trecho em no arquivo "WordxDossieController":  
// =========================================================
		// === AQUI SÃO EXIBIDAS AS TESES ===
		// =========================================================

		$contador = 1;
		foreach ($teses as $tese) {
			if (isset($tese->tipo) && $tese->tipo === 'citacao') {
				$estiloCitacaoTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
				$estiloCitacaoParagrafo = [
					'alignment'   => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
					'indentation' => ['left' => 2268],
					'lineHeight'  => 1.0,
					'spaceBefore' => 240,
					'spaceAfter'  => 240
				];
				$section->addText($tese->conteudo, $estiloCitacaoTexto, $estiloCitacaoParagrafo);
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

				$section->addText($contador . " - " . $tese->conteudo, $estiloTituloCustomTexto, $estiloTituloCustomParagrafo);
				$section->addText("\xC2\xA0", ['name' => 'Tahoma', 'size' => 12], ['lineHeight' => 1.5]);
				$contador++;

				// =========================================================
				// === NOVO BLOCO: TRATAMENTO DE IMAGEM NA TESE ===
				// =========================================================
			} elseif (isset($tese->tipo) && $tese->tipo === 'imagem') {

				// Extrai o caminho salvo no banco e converte para o caminho físico do servidor
				$caminhoFisicoImagem = public_path(ltrim($tese->conteudo, '/'));

				// Padroniza as barras (\ ou /) para o sistema operacional em uso
				$caminhoFisicoImagem = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoFisicoImagem);

				// Verifica se o arquivo realmente existe na pasta antes de tentar inserir
				if (file_exists($caminhoFisicoImagem)) {
					$section->addTextBreak(1); // Espaço antes da imagem
					$section->addImage($caminhoFisicoImagem, [
						'width'     => 450, // Largura padrão (pode ajustar)
						'height'    => null, // Mantém a proporção
						'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
					]);
					$section->addTextBreak(1); // Espaço depois da imagem
				} else {
					// Aviso discreto no Word caso a imagem tenha sido apagada do disco
					$section->addText('[Erro: Imagem da tese não localizada no servidor]', ['color' => 'FF0000', 'italic' => true], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
				}
				// =========================================================

			} else {
				if (isset($tese->conteudo)) {
					$paragrafos = explode("\n", $tese->conteudo);
				} else {
					dd($tese);
				}

				foreach ($paragrafos as $paragrafo) {
					$paragrafoLimpo = trim($paragrafo);
					if ($paragrafoLimpo !== '') {
						$estiloTexto = ['name' => 'Tahoma', 'size' => 12, 'color' => '1B2232'];
						$section->addText("\t" . $paragrafoLimpo, $estiloTexto, [
							'alignment'  => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
							'lineHeight' => 1.5,
							'spaceAfter' => 120
						]);
					}
				}
			}
		}

		Destaco que no campo "nome" está presente onumero da tese, ex: "[TESE-03] - DOS CONSECTÁRIOS LEGAIS E DOS HONORÁRIOS ADVOCATÍCIOS EM CASO DE CONDENAÇÃO". 
		ou seja na tabela "teses_formatadas" esse numero "03" corresponde ao valor da coluna "numero_tese": 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TeseFormatada extends Model
{
    use HasFactory;

    protected $table = 'teses_formatadas';

    protected $fillable = [
        'numero_tese',
        'tipo',
        'conteudo',
    ];


    public static function numeroTesesBanco($extrairTeses)
    {

        $tesesFormatadas = DB::table('teses_formatadas')
            ->whereIn('numero_tese', $extrairTeses)
            ->get();

        return $tesesFormatadas;
    }
}

		
			
