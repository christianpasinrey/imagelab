<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImageLab - Galería de Imágenes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #09090b;
            color: #fafafa;
            min-height: 100vh;
        }
        .masonry {
            columns: 4;
            column-gap: 16px;
        }
        @media (max-width: 1280px) { .masonry { columns: 3; } }
        @media (max-width: 900px) { .masonry { columns: 2; } }
        @media (max-width: 540px) { .masonry { columns: 1; } }
        .masonry-item {
            break-inside: avoid;
            margin-bottom: 16px;
        }
        .image-card {
            background: #18181b;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .image-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .image-card img {
            width: 100%;
            display: block;
        }
        .tag {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(255,255,255,0.06);
            border-radius: 20px;
            font-size: 11px;
            color: #a1a1aa;
            transition: all 0.15s ease;
        }
        .tag:hover {
            background: rgba(255,255,255,0.12);
            color: #fafafa;
        }
        .tag.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
            color: white;
            cursor: pointer;
        }
        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }
        .search-input {
            background: #18181b;
            border: 1px solid #27272a;
            border-radius: 8px;
            padding: 10px 16px;
            font-size: 14px;
            color: #fafafa;
            width: 100%;
            transition: border-color 0.15s ease;
        }
        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .search-input::placeholder {
            color: #52525b;
        }
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #71717a;
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header style="position: sticky; top: 0; z-index: 50; background: rgba(9,9,11,0.8); backdrop-filter: blur(12px); border-bottom: 1px solid #27272a;">
        <div style="max-width: 1400px; margin: 0 auto; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 24px;">
            <!-- Logo -->
            <a href="/" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                    <svg width="18" height="18" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span style="font-size: 18px; font-weight: 600; letter-spacing: -0.02em;">ImageLab</span>
            </a>

            <!-- Search -->
            <form action="/" method="GET" style="flex: 1; max-width: 400px;">
                <div style="position: relative;">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar imágenes..." class="search-input" style="padding-left: 40px;">
                    <svg style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #52525b;" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </form>

            <!-- Actions -->
            <a href="/editor" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Subir imagen
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main style="max-width: 1400px; margin: 0 auto; padding: 32px 24px;">
        @if($popularTags->count() > 0)
        <!-- Tags -->
        <div style="margin-bottom: 32px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
            <span style="font-size: 12px; color: #52525b; margin-right: 8px;">Tags populares:</span>
            @foreach($popularTags as $tag)
                <a href="?tag={{ urlencode($tag) }}" class="tag {{ request('tag') === $tag ? 'active' : '' }}">
                    {{ $tag }}
                </a>
            @endforeach
            @if(request('tag') || request('q'))
                <a href="/" class="tag" style="background: #dc2626;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: inline; vertical-align: middle; margin-right: 4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Limpiar
                </a>
            @endif
        </div>
        @endif

        @if($images->count() > 0)
        <!-- Gallery Grid -->
        <div class="masonry">
            @foreach($images as $image)
            @php
                $media = $image->getFirstMedia('original');
                $thumb = $media?->getUrl('preview');
            @endphp
            @if($thumb)
            <div class="masonry-item">
                <a href="/editor/{{ $image->id }}" class="image-card" style="display: block; text-decoration: none; color: inherit;">
                    <img src="{{ $thumb }}" alt="{{ $image->title }}" loading="lazy">
                    <div style="padding: 12px 14px;">
                        <h3 style="font-size: 14px; font-weight: 500; margin: 0 0 6px 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $image->title }}
                        </h3>
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @if($image->tags)
                                    @foreach(array_slice($image->tags, 0, 2) as $tag)
                                        <span class="tag" style="font-size: 10px; padding: 2px 8px;">{{ $tag }}</span>
                                    @endforeach
                                @endif
                            </div>
                            <span style="font-size: 11px; color: #52525b; display: flex; align-items: center; gap: 4px;">
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ $image->views_count }}
                            </span>
                        </div>
                    </div>
                </a>
            </div>
            @endif
            @endforeach
        </div>

        <!-- Pagination -->
        @if($images->hasPages())
        <div style="margin-top: 48px; display: flex; justify-content: center;">
            {{ $images->withQueryString()->links() }}
        </div>
        @endif

        @else
        <!-- Empty State -->
        <div class="empty-state">
            <div style="width: 80px; height: 80px; margin: 0 auto 24px; border-radius: 20px; background: #18181b; display: flex; align-items: center; justify-content: center;">
                <svg width="40" height="40" fill="none" stroke="#52525b" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            @if(request('q') || request('tag'))
                <h2 style="font-size: 20px; font-weight: 500; margin-bottom: 8px; color: #fafafa;">No se encontraron resultados</h2>
                <p style="font-size: 14px; margin-bottom: 24px;">Intenta con otros términos de búsqueda</p>
                <a href="/" class="btn-primary" style="text-decoration: none;">Ver todas las imágenes</a>
            @else
                <h2 style="font-size: 20px; font-weight: 500; margin-bottom: 8px; color: #fafafa;">Aún no hay imágenes</h2>
                <p style="font-size: 14px; margin-bottom: 24px;">Sé el primero en subir una imagen</p>
                <a href="/editor" class="btn-primary" style="text-decoration: none;">Subir primera imagen</a>
            @endif
        </div>
        @endif
    </main>

    <!-- Footer -->
    <footer style="border-top: 1px solid #18181b; padding: 24px; text-align: center;">
        <p style="font-size: 12px; color: #52525b;">
            <span class="gradient-text" style="font-weight: 500;">ImageLab</span> — Editor y galería de imágenes
        </p>
    </footer>
</body>
</html>
