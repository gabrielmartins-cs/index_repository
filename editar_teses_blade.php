@extends('layout.layout') {{-- Mantive o seu layout padrão --}}

@section("content")
@include('layout.header', ['title' => 'Editar palavras chave das teses ','subtitle' => 'Editar Teses' ])

<div class="container-fluid">
    <!-- Mensagens de Sucesso -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <!-- Formulário de Cadastro (STORE) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Nova Palavra Tese</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('palavras-teses.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label>Nome da Tese</label>
                        <input type="text" name="nome" class="form-control" required placeholder="Ex: [TESE-01] - ...">
                    </div>

                    <!-- Novo campo Empresa -->
                    <div class="col-md-2 mb-3">
                        <label>Empresa</label>
                        <select name="empresa" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="CVP">CVP</option>
                            <option value="CSH">CSH</option>
                        </select>
                    </div>

                    <!-- Novo campo Tipo Tese -->
                    <div class="col-md-3 mb-3">
                        <label>Tipo de Tese</label>
                        <select name="tipo_tese" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="PRELIMINARES">PRELIMINARES</option>
                            <option value="PREJUDICIAIS">PREJUDICIAIS</option>
                            <option value="MÉRITO">MÉRITO</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary btn-block">Adicionar</button>
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Termos (Separados por vírgula)</label>
                        <input type="text" name="termos" class="form-control" required placeholder="termo 1, termo 2, termo 3">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label>Parágrafo Conclusão</label>
                        <textarea name="paragrafo_conclusao" class="form-control" rows="2" placeholder="Digite o parágrafo de conclusão..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Listagem (INDEX) -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Lista de Palavras Teses</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Empresa</th>
                            <th>Tipo</th>
                            <th>Termos</th>
                            <th>Parágrafo Conclusão</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($teses as $tese)
                        @php
                        $termosArray = json_decode($tese->termos, true);
                        $termosString = is_array($termosArray) ? implode(', ', $termosArray) : $tese->termos;
                        @endphp
                        <tr>
                            <td>{{ $tese->id }}</td>
                            <td>{{ $tese->nome }}</td>
                            <td>{{ $tese->empresa }}</td>
                            <td>{{ $tese->tipo_tese }}</td>
                            <td>{{ $termosString }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($tese->paragrafo_conclusao, 50) }}</td>
                            <td>
                                <!-- Botão Editar -->
                                <button class="btn btn-sm btn-warning" data-toggle="modal" data-target="#editModal{{ $tese->id }}">
                                    <i class="fas fa-edit"></i>
                                </button>

                                <!-- Formulário Deletar -->
                                <form action="{{ route('palavras-teses.destroy', $tese->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Tem certeza que deseja excluir?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Modal de Edição -->
                        <div class="modal fade" id="editModal{{ $tese->id }}" tabindex="-1" role="dialog" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <form action="{{ route('palavras-teses.update', $tese->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')

                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Tese #{{ $tese->id }}</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Nome</label>
                                                        <input type="text" name="nome" class="form-control" value="{{ $tese->nome }}" required>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Empresa</label>
                                                        <select name="empresa" class="form-control" required>
                                                            <option value="CVP" {{ $tese->empresa == 'CVP' ? 'selected' : '' }}>CVP</option>
                                                            <option value="CSH" {{ $tese->empresa == 'CSH' ? 'selected' : '' }}>CSH</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>Tipo de Tese</label>
                                                        <select name="tipo_tese" class="form-control" required>
                                                            <option value="PRELIMINARES" {{ $tese->tipo_tese == 'PRELIMINARES' ? 'selected' : '' }}>PRELIMINARES</option>
                                                            <option value="PREJUDICIAIS" {{ $tese->tipo_tese == 'PREJUDICIAIS' ? 'selected' : '' }}>PREJUDICIAIS</option>
                                                            <option value="MÉRITO" {{ $tese->tipo_tese == 'MÉRITO' ? 'selected' : '' }}>MÉRITO</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="form-group">
                                                <label>Termos</label>
                                                <input type="text" name="termos" class="form-control" value="{{ $termosString }}" required>
                                            </div>

                                            <div class="form-group">
                                                <label>Parágrafo Conclusão</label>
                                                <textarea name="paragrafo_conclusao" class="form-control" rows="4">{{ $tese->paragrafo_conclusao }}</textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-success">Salvar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
