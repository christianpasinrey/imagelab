<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImageLab</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: #0a0a0f;
            color: #f8fafc;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Gradient overlay */
        .bg-overlay {
            position: fixed;
            inset: 0;
            z-index: -1;
            background: url('/img/gradient_wp.png') center/cover no-repeat;
            opacity: 0.08;
            pointer-events: none;
        }

        /* Header */
        header {
            position: sticky;
            top: 0;
            z-index: 100;
            padding: 16px 24px;
            background: rgba(10, 10, 15, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .header-inner {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text {
            font-size: 22px;
            font-weight: 700;
        }

        .btn-upload {
            padding: 10px 24px;
            border-radius: 12px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 20px rgba(139, 92, 246, 0.3);
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(139, 92, 246, 0.4);
        }

        /* Stepper Section */
        .stepper {
            max-width: 900px;
            margin: 0 auto;
            padding: 60px 24px 80px;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 40px;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUp 0.6s ease forwards;
        }

        .step:nth-child(1) { animation-delay: 0.1s; }
        .step:nth-child(2) { animation-delay: 0.3s; }

        .step:nth-child(even) {
            flex-direction: row-reverse;
        }

        .step-image {
            flex-shrink: 0;
            width: 280px;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
            transition: all 0.4s ease;
            border: 2px solid rgba(255,255,255,0.1);
        }

        .step:nth-child(odd) .step-image {
            transform: rotate(-3deg);
        }

        .step:nth-child(even) .step-image {
            transform: rotate(3deg);
        }

        .step-image:hover {
            transform: rotate(0deg) scale(1.05);
            border-color: rgba(139, 92, 246, 0.5);
            box-shadow: 0 30px 80px rgba(139, 92, 246, 0.2);
        }

        .step-image img {
            width: 100%;
            display: block;
        }

        /* Skeleton placeholder */
        .step-image.skeleton {
            background: linear-gradient(135deg, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0.06) 100%);
            aspect-ratio: 3/4;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .skeleton-icon {
            color: rgba(255,255,255,0.15);
        }

        .step-arrow {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
        }

        .curved-arrow {
            width: 120px;
            height: 100px;
        }

        .curved-arrow path {
            fill: none;
            stroke: url(#arrowGradient);
            stroke-width: 2;
            stroke-linecap: round;
        }

        .curved-arrow .arrow-head {
            fill: rgba(6, 182, 212, 0.6);
            stroke: none;
        }

        /* Flip for even steps */
        .step:nth-child(even) .curved-arrow {
            transform: scaleX(-1);
        }

        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }

        /* Divider */
        .divider {
            text-align: center;
            padding: 40px 24px 60px;
            opacity: 0;
            animation: fadeUp 0.6s ease 1.1s forwards;
        }

        .divider-line {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .divider-line::before,
        .divider-line::after {
            content: '';
            width: 100px;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }

        .divider h2 {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: rgba(255,255,255,0.4);
        }

        /* Gallery */
        .gallery-section {
            padding: 0 24px 80px;
            max-width: 1600px;
            margin: 0 auto;
        }

        /* Search & Tags */
        .filters {
            margin-bottom: 40px;
        }

        .search-box {
            display: flex;
            gap: 12px;
            max-width: 500px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            padding: 12px 20px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            border-color: rgba(139, 92, 246, 0.5);
            background: rgba(255,255,255,0.05);
        }

        .search-input::placeholder {
            color: rgba(255,255,255,0.3);
        }

        .search-btn {
            padding: 12px 24px;
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .search-btn:hover {
            background: rgba(255,255,255,0.1);
        }

        .tags-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag {
            padding: 8px 16px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 100px;
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .tag:hover {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
            color: #a78bfa;
        }

        .tag.active {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            border-color: transparent;
            color: #fff;
        }

        .tag.clear {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        /* Masonry */
        .masonry {
            columns: 4;
            column-gap: 20px;
        }

        @media (max-width: 1400px) { .masonry { columns: 3; } }
        @media (max-width: 1000px) { .masonry { columns: 2; } }
        @media (max-width: 600px) { .masonry { columns: 1; } }

        .masonry-item {
            break-inside: avoid;
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.5s ease;
        }

        .masonry-item.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .meme-card {
            display: block;
            border-radius: 16px;
            overflow: hidden;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }

        .meme-card:hover {
            transform: translateY(-6px);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 20px 50px rgba(0,0,0,0.3);
        }

        .meme-card img {
            width: 100%;
            display: block;
        }

        .meme-card-info {
            padding: 14px 16px;
        }

        .meme-card-title {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .meme-card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .meme-card-tags {
            display: flex;
            gap: 6px;
        }

        .meme-card-tag {
            padding: 3px 8px;
            background: rgba(255,255,255,0.06);
            border-radius: 4px;
            font-size: 11px;
            color: rgba(255,255,255,0.5);
        }

        .meme-card-views {
            font-size: 12px;
            color: rgba(255,255,255,0.3);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 24px;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 24px;
            border-radius: 20px;
            background: rgba(255,255,255,0.03);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .empty-state p {
            color: rgba(255,255,255,0.4);
            margin-bottom: 24px;
        }

        /* Pagination */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            padding: 48px 0;
        }

        .pagination-wrapper nav {
            display: flex;
            gap: 8px;
        }

        .pagination-wrapper a,
        .pagination-wrapper span {
            padding: 10px 16px;
            border-radius: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.2s ease;
        }

        .pagination-wrapper a:hover {
            background: rgba(139, 92, 246, 0.15);
            border-color: rgba(139, 92, 246, 0.3);
        }

        /* Responsive stepper */
        @media (max-width: 700px) {
            .step {
                flex-direction: column !important;
                gap: 20px;
            }
            .step-image {
                width: 220px;
            }
            .step-arrow {
                transform: rotate(90deg);
            }
            .arrow-line {
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <!-- Gradient Overlay -->
    <div class="bg-overlay"></div>

    <!-- Header -->
    <header>
        <div class="header-inner">
            <a href="/" class="logo">
                <div class="logo-icon">
                    <svg width="22" height="22" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <span class="logo-text">ImageLab</span>
            </a>
            <a href="/editor" class="btn-upload">
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Subir
            </a>
        </div>
    </header>

    <!-- SVG Gradient Definition -->
    <svg style="position:absolute;width:0;height:0;">
        <defs>
            <linearGradient id="arrowGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#8b5cf6;stop-opacity:0.6" />
                <stop offset="100%" style="stop-color:#06b6d4;stop-opacity:0.6" />
            </linearGradient>
        </defs>
    </svg>

    <!-- Stepper Section -->
    <section class="stepper">
        <!-- Meme 1 -->
        <div class="step">
            <div class="step-image">
                <img src="/img/meme_1.png" alt="Meme">
            </div>
            <div class="step-arrow">
                <svg class="curved-arrow" viewBox="0 0 120 100">
                    <path d="M10,10 Q10,90 100,90" />
                    <polygon class="arrow-head" points="95,82 105,90 95,98" />
                </svg>
            </div>
        </div>

        <!-- Meme 2 -->
        <div class="step">
            <div class="step-image">
                <img src="/img/meme_2.png" alt="Meme">
            </div>
            <div class="step-arrow">
                <svg class="curved-arrow" viewBox="0 0 120 100">
                    <path d="M10,10 Q10,90 100,90" />
                    <polygon class="arrow-head" points="95,82 105,90 95,98" />
                </svg>
            </div>
        </div>
    </section>

    <!-- Divider -->
    <div class="divider">
        <div class="divider-line"></div>
    </div>

    <!-- Gallery Section -->
    <section class="gallery-section">
        @if($popularTags->count() > 0 || request('q') || request('tag'))
        <div class="filters">
            <form action="/" method="GET" class="search-box">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar..." class="search-input">
                <button type="submit" class="search-btn">Buscar</button>
            </form>

            @if($popularTags->count() > 0)
            <div class="tags-list">
                @foreach($popularTags as $tag)
                    <a href="?tag={{ urlencode($tag) }}" class="tag {{ request('tag') === $tag ? 'active' : '' }}">{{ $tag }}</a>
                @endforeach
                @if(request('tag') || request('q'))
                    <a href="/" class="tag clear">Limpiar</a>
                @endif
            </div>
            @endif
        </div>
        @endif

        @if($images->count() > 0)
        <div class="masonry">
            @foreach($images as $index => $image)
            @php
                $media = $image->getFirstMedia('original');
                $thumb = $media?->getUrl('preview');
            @endphp
            @if($thumb)
            <div class="masonry-item" data-delay="{{ $index * 80 }}">
                <a href="/editor/{{ $image->id }}" class="meme-card">
                    <img src="{{ $thumb }}" alt="{{ $image->title }}" loading="lazy">
                    <div class="meme-card-info">
                        <h3 class="meme-card-title">{{ $image->title }}</h3>
                        <div class="meme-card-meta">
                            <div class="meme-card-tags">
                                @if($image->tags)
                                    @foreach(array_slice($image->tags, 0, 2) as $tag)
                                        <span class="meme-card-tag">{{ $tag }}</span>
                                    @endforeach
                                @endif
                            </div>
                            <span class="meme-card-views">
                                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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

        @if($images->hasPages())
        <div class="pagination-wrapper">
            {{ $images->withQueryString()->links() }}
        </div>
        @endif

        @else
        <div class="empty-state">
            <div class="empty-icon">
                <svg width="40" height="40" fill="none" stroke="rgba(255,255,255,0.2)" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            @if(request('q') || request('tag'))
                <p>Nada por aquí...</p>
                <a href="/" class="btn-upload">Ver todo</a>
            @else
                <p>El mural está vacío</p>
                <a href="/editor" class="btn-upload">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Subir el primero
                </a>
            @endif
        </div>
        @endif
    </section>

    <script>
        // Intersection Observer for masonry animations
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.dataset.delay || 0;
                    setTimeout(() => {
                        entry.target.classList.add('visible');
                    }, delay);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.masonry-item').forEach(item => {
            observer.observe(item);
        });
    </script>
</body>
</html>
