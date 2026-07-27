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
