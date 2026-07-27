public function analisarTeses($texto)
	{
		$MapeamentoTeses = new PalavrasTeses(); // model do banco 
		$textoMinusculo = mb_strtolower($texto, 'UTF-8');

		$mapeamentoTeses = $MapeamentoTeses->dbMapeamentoteses();

		$resultado = [];

		// 1. MAPEAMENTO PADRÃO PELO BANCO DE DADOS
		foreach ($mapeamentoTeses as $tese) {
			$encontrou = false;
			$termos = json_decode($tese->termos, true);

			if (is_array($termos)) {
				foreach ($termos as $palavra) {
					if (str_contains($textoMinusculo, mb_strtolower($palavra, 'UTF-8'))) {
						$encontrou = true;
						break;
					}
				}
			}

			$resultado[$tese->nome] = [
				'status'   => $encontrou,
				'field_id' => $tese->field_id
			];
		}

		// ========================================================================
		// 2. CONVERSÃO E RETORNO DOS DADOS
		// ========================================================================
		$tesesAtivas = [];

		foreach ($resultado as $chaveTese => $dadosTese) {
			if (isset($dadosTese['status']) && $dadosTese['status'] === true) {
				if (preg_match('/\[TESE-(\d+)\]/', $chaveTese, $matches)) {
					// O (int) já garante que o valor seja salvo como um número inteiro puro
					$tesesAtivas[] = (int)$matches[1];
				}
			}
		}

		return $tesesAtivas;
	}
