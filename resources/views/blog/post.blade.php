@extends('layouts.blog')

@section('titulo', $post->titulo . ' — ' . $site->titulo)

@section('content')
    <div class="mb-4">
        <a href="{{ route('blog.show', $site) }}" class="text-decoration-none">&larr; {{ $site->titulo }}</a>
    </div>

    <article>
        <h1>{{ $post->titulo }}</h1>
        <small class="text-muted">{{ $post->publicado_em?->format('d/m/Y') }}</small>
        <div class="mt-3" style="white-space: pre-wrap;">{{ $post->conteudo }}</div>
    </article>
@endsection
