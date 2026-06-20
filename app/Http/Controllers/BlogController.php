<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Site;

/**
 * Exibicao publica do micro-blog. Nao depende de tenant ativo nem
 * de autenticacao: a leitura e aberta.
 */
class BlogController extends Controller
{
    public function show(Site $site)
    {
        abort_unless($site->publicado, 404);

        $posts = $site->posts()
            ->where('publicado', true)
            ->orderByDesc('publicado_em')
            ->get();

        return view('blog.show', compact('site', 'posts'));
    }

    public function post(Site $site, Post $post)
    {
        abort_unless($site->publicado && $post->publicado && $post->site_id === $site->id, 404);

        return view('blog.post', compact('site', 'post'));
    }
}
