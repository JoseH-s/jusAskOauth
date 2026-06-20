<?php

namespace App\Livewire\Site;

use App\Models\Post;
use App\Models\Site;
use App\Services\TenantManager;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GerenciarSite extends Component
{
    public ?Site $site = null;

    public string $titulo = '';
    public string $slug = '';
    public string $descricao = '';
    public bool $publicado = false;

    public ?int $postId = null;
    public bool $mostrarPostForm = false;
    public string $postTitulo = '';
    public string $postSlug = '';
    public string $postConteudo = '';
    public bool $postPublicado = false;

    public function mount()
    {
        if (! app(TenantManager::class)->check()) {
            return redirect()->route('home');
        }

        $this->site = Site::first();

        if ($this->site) {
            $this->titulo = $this->site->titulo;
            $this->slug = $this->site->slug;
            $this->descricao = (string) $this->site->descricao;
            $this->publicado = $this->site->publicado;
        }
    }

    protected function rulesSite(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:255'],
            'slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('sites', 'slug')->ignore($this->site?->id),
            ],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'publicado' => ['boolean'],
        ];
    }

    protected function rulesPost(): array
    {
        return [
            'postTitulo' => ['required', 'string', 'max:255'],
            'postSlug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('posts', 'slug')->where('site_id', $this->site?->id)->ignore($this->postId),
            ],
            'postConteudo' => ['required', 'string'],
            'postPublicado' => ['boolean'],
        ];
    }

    public function updatedTitulo(string $value): void
    {
        if (! $this->site) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedPostTitulo(string $value): void
    {
        if (! $this->postId) {
            $this->postSlug = Str::slug($value);
        }
    }

    public function salvarSite(): void
    {
        $dados = $this->validate($this->rulesSite());

        $atributos = [
            'titulo' => $dados['titulo'],
            'slug' => $dados['slug'],
            'descricao' => $dados['descricao'] ?? null,
            'publicado' => $dados['publicado'],
        ];

        if ($this->site) {
            $this->site->update($atributos);
        } else {
            $this->site = Site::create($atributos);
        }

        session()->flash('status', 'Site salvo.');
    }

    public function novoPost(): void
    {
        $this->resetPostForm();
        $this->mostrarPostForm = true;
    }

    public function editarPost(int $id): void
    {
        $post = Post::where('site_id', $this->site->id)->findOrFail($id);

        $this->postId = $post->id;
        $this->postTitulo = $post->titulo;
        $this->postSlug = $post->slug;
        $this->postConteudo = $post->conteudo;
        $this->postPublicado = $post->publicado;
        $this->mostrarPostForm = true;
    }

    public function salvarPost(): void
    {
        abort_unless($this->site, 400, 'Crie o site antes de publicar posts.');

        $this->validate($this->rulesPost());

        $dados = [
            'site_id' => $this->site->id,
            'titulo' => $this->postTitulo,
            'slug' => $this->postSlug,
            'conteudo' => $this->postConteudo,
            'publicado' => $this->postPublicado,
            'publicado_em' => $this->postPublicado ? now() : null,
        ];

        if ($this->postId) {
            $post = Post::where('site_id', $this->site->id)->findOrFail($this->postId);
            // Preserva a data de publicacao original se ja estava publicado.
            if ($this->postPublicado && $post->publicado_em) {
                $dados['publicado_em'] = $post->publicado_em;
            }
            $post->update($dados);
            session()->flash('status', 'Post atualizado.');
        } else {
            Post::create($dados);
            session()->flash('status', 'Post criado.');
        }

        $this->resetPostForm();
        $this->mostrarPostForm = false;
    }

    public function excluirPost(int $id): void
    {
        Post::where('site_id', $this->site->id)->findOrFail($id)->delete();
        session()->flash('status', 'Post removido.');
    }

    public function cancelarPost(): void
    {
        $this->resetPostForm();
        $this->mostrarPostForm = false;
    }

    protected function resetPostForm(): void
    {
        $this->reset(['postId', 'postTitulo', 'postSlug', 'postConteudo', 'postPublicado']);
        $this->resetValidation();
    }

    public function render()
    {
        $posts = $this->site
            ? Post::where('site_id', $this->site->id)->orderByDesc('created_at')->get()
            : collect();

        return view('livewire.site.gerenciar-site', compact('posts'))
            ->extends('layouts.app');
    }
}
