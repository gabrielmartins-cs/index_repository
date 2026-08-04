<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use App\Models\AuditoriaAutDossie;

class TeseFormatada extends Model
{
    use HasFactory;

    protected $table = 'teses_formatadas';

    protected $fillable = ['numero_tese', 'tipo', 'conteudo'];

    public static function obterAgrupados()
    {
        return self::orderBy('numero_tese', 'asc')->orderBy('id', 'asc')->get()->groupBy('numero_tese');
    }

    public static function salvarLote($numero_tese, array $textosData, array $arquivos, $usuario)
    {
        foreach ($textosData as $index => $texto) {
            $conteudo = self::processarConteudo($texto, $arquivos[$index] ?? null);

            if (!empty($conteudo)) {
                self::create([
                    'numero_tese' => $numero_tese,
                    'tipo'        => $texto['tipo'] ?? 'normal',
                    'conteudo'    => $conteudo,
                ]);
            }
        }

        if ($usuario) {
            $primeiroTexto = current($textosData);
            $titulo = $primeiroTexto['conteudo'] ?? 'Sem título';
            AuditoriaAutDossie::create([
                'nome_usuario_editor' => "{$usuario->id} - {$usuario->name}",
                'acao_edicao' => "[ID: {$usuario->id}] {$usuario->name} CRIOU a montagem da Tese Nº {$numero_tese} | Título: {$titulo}"
            ]);
        }
    }

    public static function atualizarLote($numero_tese, array $textosData, array $arquivos, $usuario)
    {
        $antigos = self::where('numero_tese', $numero_tese)->get();
        $imagensAntigas = $antigos->where('tipo', 'imagem')->pluck('conteudo')->toArray();
        $imagensMantidas = [];

        self::where('numero_tese', $numero_tese)->delete();

        $novoTitulo = null; // Variável para capturar o título atualizado

        foreach ($textosData as $index => $textoData) {
            $conteudo = self::processarConteudo($textoData, $arquivos[$index] ?? null);

            if (($textoData['tipo'] ?? '') === 'imagem' && isset($textoData['caminho_imagem_atual']) && empty($arquivos[$index])) {
                $conteudo = $textoData['caminho_imagem_atual'];
                $imagensMantidas[] = $conteudo;
            }

            if (!empty($conteudo)) {
                // Se o texto atual for do tipo 'titulo', nós o guardamos limpo (sem tags HTML)
                if (($textoData['tipo'] ?? '') === 'titulo') {
                    $novoTitulo = strip_tags($conteudo);
                }

                self::create([
                    'numero_tese' => $numero_tese,
                    'tipo'        => $textoData['tipo'] ?? 'normal',
                    'conteudo'    => $conteudo,
                ]);
            }
        }

        foreach ($imagensAntigas as $imgAntiga) {
            if (!in_array($imgAntiga, $imagensMantidas) && File::exists(public_path($imgAntiga))) {
                File::delete(public_path($imgAntiga));
            }
        }

        // =========================================================================
        // SINCRONIZAÇÃO COM A TABELA palavras_teses
        // =========================================================================
        if ($novoTitulo) {
            $palavraTese = \App\Models\PalavrasTeses::where('numero_tese', $numero_tese)->first();

            if ($palavraTese) {
                // str_pad coloca o '0' na frente de números de 1 a 9 (ex: 04, 09, 29)
                $numeroFormatado = str_pad($numero_tese, 2, '0', STR_PAD_LEFT);
                $novoNome = "[TESE-{$numeroFormatado}] - " . trim($novoTitulo);

                $palavraTese->update(['nome' => $novoNome]);
            }
        }

        // No método atualizarLote (Auditoria):
        if ($usuario) {
            // Usamos o $novoTitulo se existir. Se não, fallback para o primeiro texto.
            $tituloAuditoria = $novoTitulo ? $novoTitulo : (current($textosData)['conteudo'] ?? 'Sem título');

            \App\Models\AuditoriaAutDossie::create([
                'nome_usuario_editor' => "{$usuario->id} - {$usuario->name}",
                'acao_edicao' => "[ID: {$usuario->id}] {$usuario->name} EDITOU a Tese Nº {$numero_tese} | Título: {$tituloAuditoria}"
            ]);
        }
    }

    public static function excluirLote($numero_tese, $usuario)
    {
        $registros = self::where('numero_tese', $numero_tese)->get();
        // Aqui você já pegou o título!
        $titulo = $registros->where('tipo', 'titulo')->first()?->conteudo ?? 'Sem título';

        foreach ($registros as $reg) {
            if ($reg->tipo === 'imagem' && File::exists(public_path($reg->conteudo))) {
                File::delete(public_path($reg->conteudo));
            }
        }

        self::where('numero_tese', $numero_tese)->delete();

        if ($usuario) {
            AuditoriaAutDossie::create([
                'nome_usuario_editor' => "{$usuario->id} - {$usuario->name}",
                'acao_edicao' => "[ID: {$usuario->id}] {$usuario->name} EXCLUIU a Tese Nº {$numero_tese} | Título: {$titulo}"
            ]);
        }
    }

    private static function processarConteudo(array $textoData, $arquivo)
    {
        if (($textoData['tipo'] ?? 'normal') === 'imagem' && isset($arquivo['conteudo_imagem'])) {
            $image = $arquivo['conteudo_imagem'];
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('automacao_dossie/conteudo_teses'), $imageName);
            return 'automacao_dossie/conteudo_teses/' . $imageName;
        }

        return $textoData['conteudo'] ?? '';
    }
}
