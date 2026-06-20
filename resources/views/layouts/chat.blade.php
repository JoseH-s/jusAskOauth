<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Assistente Jurídico</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
    <style>
        * { box-sizing: border-box; }
        body, html { height: 100%; margin: 0; background: #e5ddd5; font-family: 'Nunito', sans-serif; }

        #chat-wrapper {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,.15);
        }

        #chat-header {
            background: #075e54;
            color: #fff;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }
        #chat-header .avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #25d366;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        #chat-header h1 { margin: 0; font-size: 1rem; font-weight: 700; }
        #chat-header p  { margin: 0; font-size: .78rem; opacity: .85; }

        #chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #e5ddd5;
        }

        .bubble-row {
            display: flex;
            margin-bottom: 2px;
        }
        .bubble-row.user  { justify-content: flex-end; }
        .bubble-row.model { justify-content: flex-start; }

        .bubble {
            max-width: 75%;
            padding: 8px 12px 4px;
            border-radius: 8px;
            font-size: .9rem;
            line-height: 1.5;
            word-wrap: break-word;
            white-space: pre-wrap;
        }
        .bubble.user  { background: #dcf8c6; color: #111; border-bottom-right-radius: 2px; }
        .bubble.model { background: #fff; color: #111; border-bottom-left-radius: 2px; box-shadow: 0 1px 1px rgba(0,0,0,.1); }

        .bubble-time {
            font-size: .68rem;
            color: #999;
            text-align: right;
            margin-top: 2px;
        }

        /* Typing dots */
        .typing-dots { display: flex; gap: 4px; align-items: center; padding: 4px 2px; }
        .typing-dot  { width: 8px; height: 8px; background: #888; border-radius: 50%; animation: blink 1.3s infinite; }
        .typing-dot:nth-child(2) { animation-delay: .2s; }
        .typing-dot:nth-child(3) { animation-delay: .4s; }
        @keyframes blink { 0%,80%,100% { transform: translateY(0); opacity: .25 } 40% { transform: translateY(-4px); opacity: 1 } }

        /* Indicador "Assistente está digitando" — logo acima da barra de input */
        #typing-indicator {
            display: none;                 /* alternado pelo wire:loading */
            align-items: center;
            padding: 6px 16px 10px;
            background: #e5ddd5;           /* combina com a área de mensagens */
            flex-shrink: 0;
            animation: typing-slide-in .25s ease;
        }
        #typing-indicator .ti-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #25d366;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
            margin-right: 8px;
        }
        #typing-indicator .ti-bubble {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border-radius: 8px;
            border-bottom-left-radius: 2px;
            box-shadow: 0 1px 1px rgba(0,0,0,.1);
            padding: 8px 14px;
        }
        #typing-indicator .ti-label {
            font-size: .8rem;
            color: #888;
            font-style: italic;
        }
        @keyframes typing-slide-in {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        #chat-input-bar {
            display: flex;
            align-items: flex-end;
            gap: 8px;
            padding: 10px 16px;
            background: #f0f0f0;
            border-top: 1px solid #ddd;
            flex-shrink: 0;
        }
        #chat-input-bar textarea {
            flex: 1;
            border: none;
            border-radius: 20px;
            padding: 10px 16px;
            resize: none;
            outline: none;
            font-size: .9rem;
            line-height: 1.4;
            max-height: 120px;
            overflow-y: auto;
            background: #fff;
            font-family: inherit;
        }
        #chat-input-bar button {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #075e54;
            border: none;
            color: #fff;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background .2s;
        }
        #chat-input-bar button:disabled { background: #bbb; cursor: not-allowed; }
        #chat-input-bar button:not(:disabled):hover { background: #128c7e; }

        /* Markdown inside model bubbles */
        .bubble.model .md { font-size: .9rem; line-height: 1.55; }
        .bubble.model .md > *:first-child { margin-top: 0; }
        .bubble.model .md > *:last-child  { margin-bottom: 0; }

        .bubble.model .md p      { margin: 0 0 .5em; }
        .bubble.model .md h1,
        .bubble.model .md h2,
        .bubble.model .md h3,
        .bubble.model .md h4     { margin: .6em 0 .3em; font-weight: 700; line-height: 1.25; }
        .bubble.model .md h1     { font-size: 1.1em; }
        .bubble.model .md h2     { font-size: 1em; }
        .bubble.model .md h3,
        .bubble.model .md h4     { font-size: .95em; }

        .bubble.model .md ul,
        .bubble.model .md ol     { margin: .3em 0 .5em 1.4em; padding: 0; }
        .bubble.model .md li     { margin-bottom: .2em; }
        .bubble.model .md li > p { margin: 0; }

        .bubble.model .md strong { font-weight: 700; }
        .bubble.model .md em     { font-style: italic; }

        .bubble.model .md code {
            font-family: 'Courier New', monospace;
            font-size: .82em;
            background: rgba(0,0,0,.07);
            border-radius: 3px;
            padding: 1px 5px;
        }
        .bubble.model .md pre {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 6px;
            padding: 10px 14px;
            overflow-x: auto;
            margin: .5em 0;
            font-size: .82em;
            line-height: 1.45;
        }
        .bubble.model .md pre code {
            background: none;
            padding: 0;
            color: inherit;
            font-size: 1em;
        }
        .bubble.model .md blockquote {
            border-left: 3px solid #aaa;
            margin: .4em 0;
            padding: .2em .8em;
            color: #555;
        }
        .bubble.model .md hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: .6em 0;
        }
        .bubble.model .md a {
            color: #075e54;
            text-decoration: underline;
        }
        .bubble.model .md table {
            border-collapse: collapse;
            width: 100%;
            font-size: .85em;
            margin: .4em 0;
        }
        .bubble.model .md th,
        .bubble.model .md td {
            border: 1px solid #ddd;
            padding: 4px 8px;
            text-align: left;
        }
        .bubble.model .md th { background: #f5f5f5; font-weight: 700; }
    </style>
</head>
<body>
    @yield('content')
    @livewireScripts
    <script>
        function scrollChat() {
            const el = document.getElementById('chat-messages');
            if (el) requestAnimationFrame(() => { el.scrollTop = el.scrollHeight; });
        }

        window.addEventListener('scroll-to-bottom', scrollChat);
        document.addEventListener('livewire:updated', scrollChat);
        document.addEventListener('DOMContentLoaded', scrollChat);

        // Enter envia, Shift+Enter quebra linha
        document.addEventListener('livewire:initialized', () => {
            document.addEventListener('keydown', function (e) {
                const ta = document.querySelector('#chat-input-bar textarea');
                if (e.target !== ta) return;
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    const comp = document.querySelector('[wire\\:id]');
                    if (comp) {
                        Livewire.find(comp.getAttribute('wire:id')).call('enviar');
                    }
                }
            });
        });
    </script>
</body>
</html>
