função index: 

  public function index()
    {
        $teses = DB::table('palavras_teses')->orderBy('id', 'asc')->get();
        
        $auditorias = [];
        if (Auth::check()) {
            $auditorias = AuditoriaAutDossie::where('id_usuario_editor', Auth::user()->id)
                ->where('acao_edicao', 'LIKE', 'PALAVRAS:%')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        }

        return view('dossie.editar_teses', compact('teses', 'auditorias'));
    }
CreateAuditoriaAutDossieTableCreateAuditoriaAutDossieTableCreateAuditoriaAutDossieTable

atualizar essas duas linhas da migration

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditoriaAutDossieTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditoria_aut_dossie', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('id_usuario_editor');
            $table->foreign('id_usuario_editor')->references('id')->on('users');
            $table->text('acao_edicao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('auditoria_aut_dossie');
    }
}
