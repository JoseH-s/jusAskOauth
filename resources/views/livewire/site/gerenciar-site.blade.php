<div class="container">
    <h1 class="h3 mb-3">Meu site</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Configurações do site (micro-blog)</div>
        <div class="card-body">
            <form wire:submit="salvarSite">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Título *</label>
                        <input type="text" class="form-control @error('titulo') is-invalid @enderror" wire:model.live.debounce.400ms="titulo">
                        @error('titulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Slug (endereço) *</label>
                        <input type="text" class="form-control @error('slug') is-invalid @enderror" wire:model="slug">
                        @error('slug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($site)
                            <small class="text-muted">
                                URL pública: <a href="{{ route('blog.show', $site) }}" target="_blank">{{ route('blog.show', $site) }}</a>
                            </small>
                        @endif
                    </div>
                    <div class="col-12">
                        <label class="form-label">Descrição</label>
                        <textarea class="form-control" rows="2" wire:model="descricao"></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="publicado" wire:model="publicado">
                            <label class="form-check-label" for="publicado">Site publicado (visível ao público)</label>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">{{ $site ? 'Salvar alterações' : 'Criar site' }}</button>
                </div>
            </form>
        </div>
    </div>

    @if ($site)
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Posts</h2>
            @unless ($mostrarPostForm)
                <button class="btn btn-primary" wire:click="novoPost">Novo post</button>
            @endunless
        </div>

        @if ($mostrarPostForm)
            <div class="card mb-4">
                <div class="card-header">{{ $postId ? 'Editar post' : 'Novo post' }}</div>
                <div class="card-body">
                    <form wire:submit="salvarPost">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Título *</label>
                                <input type="text" class="form-control @error('postTitulo') is-invalid @enderror" wire:model.live.debounce.400ms="postTitulo">
                                @error('postTitulo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Slug *</label>
                                <input type="text" class="form-control @error('postSlug') is-invalid @enderror" wire:model="postSlug">
                                @error('postSlug') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Conteúdo *</label>
                                <textarea class="form-control @error('postConteudo') is-invalid @enderror" rows="6" wire:model="postConteudo"></textarea>
                                @error('postConteudo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="postPublicado" wire:model="postPublicado">
                                    <label class="form-check-label" for="postPublicado">Publicar este post</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">Salvar</button>
                            <button type="button" class="btn btn-outline-secondary" wire:click="cancelarPost">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Slug</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($posts as $post)
                            <tr wire:key="post-{{ $post->id }}">
                                <td>{{ $post->titulo }}</td>
                                <td><code>{{ $post->slug }}</code></td>
                                <td>
                                    @if ($post->publicado)
                                        <span class="badge bg-success">Publicado</span>
                                    @else
                                        <span class="badge bg-secondary">Rascunho</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="editarPost({{ $post->id }})">Editar</button>
                                    <button class="btn btn-sm btn-outline-danger"
                                            wire:click="excluirPost({{ $post->id }})"
                                            wire:confirm="Remover este post?">Excluir</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum post ainda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <p class="text-muted">Crie o site acima para começar a publicar posts.</p>
    @endif
</div>
