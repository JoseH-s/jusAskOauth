@extends('layouts.blog')

@section('titulo', $site->titulo)

@section('content')
    <header class="mb-5">
        <h1>{{ $site->titulo }}</h1>
        @if ($site->descricao)
            <p class="text-muted">{{ $site->descricao }}</p>
        @endif
    </header>

    @forelse ($posts as $post)
        <article class="mb-4 pb-3 border-bottom">
            <h2 class="h4">
                <a href="{{ route('blog.post', [$site, $post]) }}" class="text-decoration-none">{{ $post->titulo }}</a>
            </h2>
            <small class="text-muted">{{ $post->publicado_em?->format('d/m/Y') }}</small>
            <p class="mt-2">{{ \Illuminate\Support\Str::limit(strip_tags($post->conteudo), 220) }}</p>
            <a href="{{ route('blog.post', [$site, $post]) }}">Ler mais &rarr;</a>
        </article>
    @empty
        <p class="text-muted">Nenhuma publicação ainda.</p>
    @endforelse
@endsection
