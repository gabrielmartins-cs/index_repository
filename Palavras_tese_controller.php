<?php

namespace App\Http\Controllers;

use App\Models\PalavrasTeses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PalavrasTesesController extends Controller
{
    public function index()
    {
        $teses = DB::table('palavras_teses')->get();
        return view('dossie.editar_teses', compact('teses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string',
            'termos' => 'required|string',
            'paragrafo_conclusao' => 'nullable|string',
            'empresa' => 'required|string',
            'tipo_tese' => 'required|string',
        ]);

        $termosArray = array_map('trim', explode(',', $request->termos));

        DB::table('palavras_teses')->insert([
            'nome' => $request->nome,
            'field_id' => '',
            'termos' => json_encode($termosArray, JSON_UNESCAPED_UNICODE),
            'paragrafo_conclusao' => $request->paragrafo_conclusao,
            'empresa' => $request->empresa,
            'tipo_tese' => $request->tipo_tese,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Tese criada com sucesso!');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required|string',
            'termos' => 'required|string',
            'paragrafo_conclusao' => 'nullable|string',
            'empresa' => 'required|string',
            'tipo_tese' => 'required|string',
        ]);

        $termosArray = array_map('trim', explode(',', $request->termos));

        DB::table('palavras_teses')->where('id', $id)->update([
            'nome' => $request->nome,
            'field_id' => '',
            'termos' => json_encode($termosArray, JSON_UNESCAPED_UNICODE),
            'paragrafo_conclusao' => $request->paragrafo_conclusao,
            'empresa' => $request->empresa,
            'tipo_tese' => $request->tipo_tese,
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Tese atualizada com sucesso!');
    }

    public function destroy($id)
    {
        DB::table('palavras_teses')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Tese excluída com sucesso!');
    }

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
}
