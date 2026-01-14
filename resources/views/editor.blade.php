<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ImageLab - Editor de Imágenes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-editor-bg text-editor-text min-h-screen">
    <div x-data="imageEditor()" x-init="init()" class="h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-editor-surface border-b border-editor-border px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h1 class="text-xl font-semibold">ImageLab</h1>
                <span class="text-editor-text-muted text-sm" x-show="currentImage" x-text="currentImage?.name"></span>
            </div>
            <div class="flex items-center gap-2">
                <button @click="showUploadModal = true"
                    class="px-4 py-2 bg-editor-accent hover:bg-editor-accent-hover rounded-lg text-sm font-medium transition-colors">
                    Subir Imagen
                </button>
                <button @click="showExportModal = true" x-show="currentImage"
                    class="px-4 py-2 bg-editor-surface-hover hover:bg-editor-border rounded-lg text-sm font-medium transition-colors border border-editor-border">
                    Exportar
                </button>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Sidebar - Gallery -->
            <aside class="w-48 bg-editor-surface border-r border-editor-border flex flex-col">
                <div class="p-3 border-b border-editor-border">
                    <h2 class="text-sm font-medium text-editor-text-muted">Galería</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-2 space-y-2">
                    <template x-for="image in images" :key="image.id">
                        <div @click="selectImage(image)"
                            :class="{'ring-2 ring-editor-accent': currentImage?.id === image.id}"
                            class="cursor-pointer rounded-lg overflow-hidden bg-editor-bg hover:ring-2 hover:ring-editor-border transition-all group relative">
                            <img :src="image.thumb || image.url" :alt="image.name"
                                class="w-full h-24 object-cover">
                            <button @click.stop="deleteImage(image.id)"
                                class="absolute top-1 right-1 p-1 bg-red-500/80 rounded opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <div x-show="images.length === 0" class="text-center py-8 text-editor-text-muted text-sm">
                        No hay imágenes.<br>Sube una para empezar.
                    </div>
                </div>
            </aside>

            <!-- Canvas Area -->
            <main class="flex-1 flex flex-col bg-editor-bg">
                <!-- Canvas Container -->
                <div class="flex-1 flex items-center justify-center p-4 overflow-hidden relative" id="canvas-container">
                    <!-- Empty state -->
                    <div x-show="!currentImage" class="text-center">
                        <div class="w-24 h-24 mx-auto mb-4 rounded-full bg-editor-surface flex items-center justify-center">
                            <svg class="w-12 h-12 text-editor-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-editor-text-muted">Selecciona o sube una imagen para comenzar</p>
                    </div>

                    <!-- Editor canvas -->
                    <div x-show="currentImage" class="relative max-w-full max-h-full" :style="`transform: scale(${zoom/100})`">
                        <!-- Original image for comparison -->
                        <img x-show="showComparison && currentImage" :src="currentImage?.url"
                            class="absolute inset-0 w-full h-full object-contain"
                            :style="`clip-path: inset(0 ${100 - comparisonPosition}% 0 0)`">

                        <!-- Canvas for edited preview -->
                        <canvas x-ref="canvas" class="max-w-full max-h-full"></canvas>

                        <!-- Comparison slider -->
                        <div x-show="showComparison"
                            class="comparison-slider"
                            :style="`left: ${comparisonPosition}%`"
                            @mousedown="startComparisonDrag($event)">
                        </div>

                        <!-- Cropper container -->
                        <div x-show="cropMode" x-ref="cropperContainer" class="absolute inset-0">
                            <img x-ref="cropperImage" :src="currentImage?.url" class="max-w-full max-h-full">
                        </div>
                    </div>
                </div>

                <!-- Bottom Toolbar -->
                <div x-show="currentImage" class="bg-editor-surface border-t border-editor-border px-4 py-2 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <!-- Comparison Toggle -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="showComparison" class="sr-only">
                            <div :class="showComparison ? 'bg-editor-accent' : 'bg-editor-border'"
                                class="w-10 h-5 rounded-full relative transition-colors">
                                <div :class="showComparison ? 'translate-x-5' : 'translate-x-0'"
                                    class="absolute top-0.5 left-0.5 w-4 h-4 bg-white rounded-full transition-transform"></div>
                            </div>
                            <span class="text-sm">Comparar</span>
                        </label>

                        <!-- Zoom -->
                        <div class="flex items-center gap-2">
                            <button @click="zoom = Math.max(25, zoom - 25)" class="p-1 hover:bg-editor-surface-hover rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                                </svg>
                            </button>
                            <span class="text-sm w-12 text-center" x-text="zoom + '%'"></span>
                            <button @click="zoom = Math.min(200, zoom + 25)" class="p-1 hover:bg-editor-surface-hover rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Transform buttons -->
                        <button @click="toggleCropMode()" :class="cropMode ? 'bg-editor-accent' : 'bg-editor-surface-hover'"
                            class="p-2 rounded-lg hover:bg-editor-border transition-colors" title="Recortar">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                            </svg>
                        </button>
                        <button @click="rotate(-90)" class="p-2 bg-editor-surface-hover rounded-lg hover:bg-editor-border transition-colors" title="Rotar izquierda">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>
                        <button @click="rotate(90)" class="p-2 bg-editor-surface-hover rounded-lg hover:bg-editor-border transition-colors" title="Rotar derecha">
                            <svg class="w-5 h-5 transform scale-x-[-1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>
                        <button @click="flip('h')" class="p-2 bg-editor-surface-hover rounded-lg hover:bg-editor-border transition-colors" title="Voltear horizontal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h12M4 7v10"/>
                            </svg>
                        </button>
                        <button @click="flip('v')" class="p-2 bg-editor-surface-hover rounded-lg hover:bg-editor-border transition-colors" title="Voltear vertical">
                            <svg class="w-5 h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h12M8 17h12M4 7v10"/>
                            </svg>
                        </button>

                        <div class="w-px h-6 bg-editor-border mx-2"></div>

                        <button @click="resetAdjustments()" class="p-2 bg-editor-surface-hover rounded-lg hover:bg-editor-border transition-colors" title="Resetear">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                        <button @click="saveVersion()" class="px-3 py-2 bg-editor-accent hover:bg-editor-accent-hover rounded-lg text-sm font-medium transition-colors">
                            Guardar versión
                        </button>
                    </div>
                </div>
            </main>

            <!-- Right Panel - Adjustments -->
            <aside x-show="currentImage" class="w-72 bg-editor-surface border-l border-editor-border flex flex-col overflow-hidden">
                <!-- Tabs -->
                <div class="flex border-b border-editor-border">
                    <button @click="activeTab = 'adjustments'"
                        :class="activeTab === 'adjustments' ? 'border-b-2 border-editor-accent text-editor-text' : 'text-editor-text-muted'"
                        class="flex-1 py-3 text-sm font-medium transition-colors">
                        Ajustes
                    </button>
                    <button @click="activeTab = 'filters'"
                        :class="activeTab === 'filters' ? 'border-b-2 border-editor-accent text-editor-text' : 'text-editor-text-muted'"
                        class="flex-1 py-3 text-sm font-medium transition-colors">
                        Filtros
                    </button>
                    <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'border-b-2 border-editor-accent text-editor-text' : 'text-editor-text-muted'"
                        class="flex-1 py-3 text-sm font-medium transition-colors">
                        Historial
                    </button>
                </div>

                <!-- Tab Content -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Adjustments Tab -->
                    <div x-show="activeTab === 'adjustments'" class="p-4 space-y-5">
                        <template x-for="(config, key) in adjustmentConfigs" :key="key">
                            <div>
                                <div class="flex justify-between mb-2">
                                    <label class="text-sm font-medium" x-text="config.label"></label>
                                    <span class="text-sm text-editor-text-muted" x-text="adjustments[key]"></span>
                                </div>
                                <input type="range"
                                    :min="config.min"
                                    :max="config.max"
                                    x-model.number="adjustments[key]"
                                    @input="debounceApply()"
                                    class="w-full">
                            </div>
                        </template>
                    </div>

                    <!-- Filters Tab -->
                    <div x-show="activeTab === 'filters'" class="p-4">
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="filter in filters" :key="filter.id">
                                <button @click="applyFilter(filter.id)"
                                    :class="adjustments.filter === filter.id ? 'ring-2 ring-editor-accent' : ''"
                                    class="rounded-lg overflow-hidden bg-editor-bg hover:ring-2 hover:ring-editor-border transition-all">
                                    <div class="aspect-square bg-editor-surface-hover flex items-center justify-center">
                                        <span class="text-2xl" x-text="filter.icon"></span>
                                    </div>
                                    <p class="text-xs text-center py-1" x-text="filter.name"></p>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div x-show="activeTab === 'history'" class="p-4">
                        <div class="space-y-2">
                            <template x-for="version in history" :key="version.id">
                                <div @click="loadVersion(version)"
                                    class="p-3 bg-editor-bg rounded-lg cursor-pointer hover:bg-editor-surface-hover transition-colors">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium">Versión <span x-text="version.version"></span></span>
                                        <span class="text-xs text-editor-text-muted" x-text="version.created_at"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="history.length === 0" class="text-center py-4 text-editor-text-muted text-sm">
                                Sin historial aún
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Upload Modal -->
        <div x-show="showUploadModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70">
            <div @click.outside="showUploadModal = false"
                class="bg-editor-surface rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h2 class="text-lg font-semibold mb-4">Subir Imagen</h2>
                <div @dragover.prevent="dragOver = true" @dragleave.prevent="dragOver = false"
                    @drop.prevent="handleDrop($event)"
                    :class="dragOver ? 'border-editor-accent bg-editor-accent/10' : 'border-editor-border'"
                    class="border-2 border-dashed rounded-xl p-8 text-center transition-colors">
                    <input type="file" accept="image/*" @change="handleFileSelect($event)" class="hidden" x-ref="fileInput">
                    <svg class="w-12 h-12 mx-auto mb-4 text-editor-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-editor-text-muted mb-2">Arrastra una imagen aquí o</p>
                    <button @click="$refs.fileInput.click()"
                        class="px-4 py-2 bg-editor-accent hover:bg-editor-accent-hover rounded-lg text-sm font-medium transition-colors">
                        Seleccionar archivo
                    </button>
                </div>
                <div x-show="uploading" class="mt-4">
                    <div class="h-2 bg-editor-bg rounded-full overflow-hidden">
                        <div class="h-full bg-editor-accent transition-all" :style="`width: ${uploadProgress}%`"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Modal -->
        <div x-show="showExportModal" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70">
            <div @click.outside="showExportModal = false"
                class="bg-editor-surface rounded-xl p-6 w-full max-w-md shadow-2xl">
                <h2 class="text-lg font-semibold mb-4">Exportar Imagen</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Formato</label>
                        <div class="flex gap-2">
                            <template x-for="fmt in ['jpg', 'png', 'webp']" :key="fmt">
                                <button @click="exportFormat = fmt"
                                    :class="exportFormat === fmt ? 'bg-editor-accent' : 'bg-editor-bg'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors uppercase">
                                    <span x-text="fmt"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between mb-2">
                            <label class="text-sm font-medium">Calidad</label>
                            <span class="text-sm text-editor-text-muted" x-text="exportQuality + '%'"></span>
                        </div>
                        <input type="range" min="10" max="100" x-model.number="exportQuality" class="w-full">
                    </div>

                    <button @click="downloadImage()"
                        class="w-full py-3 bg-editor-accent hover:bg-editor-accent-hover rounded-lg font-medium transition-colors">
                        Descargar
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div x-show="loading" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-editor-surface rounded-xl p-6 flex items-center gap-3">
                <svg class="animate-spin w-6 h-6 text-editor-accent" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Procesando...</span>
            </div>
        </div>
    </div>

    <script>
        function imageEditor() {
            return {
                // State
                images: @json($images),
                currentImage: null,
                history: [],
                activeTab: 'adjustments',
                loading: false,

                // Modals
                showUploadModal: false,
                showExportModal: false,
                uploading: false,
                uploadProgress: 0,
                dragOver: false,

                // Canvas
                canvas: null,
                ctx: null,
                originalImageData: null,
                zoom: 100,

                // Comparison
                showComparison: false,
                comparisonPosition: 50,
                isDraggingComparison: false,

                // Crop
                cropMode: false,
                cropper: null,

                // Export
                exportFormat: 'jpg',
                exportQuality: 90,

                // Debounce
                applyTimeout: null,

                // Adjustments
                adjustments: {
                    brightness: 0,
                    contrast: 0,
                    saturation: 0,
                    exposure: 0,
                    temperature: 0,
                    shadows: 0,
                    highlights: 0,
                    vibrance: 0,
                    rotation: 0,
                    flipH: false,
                    flipV: false,
                    filter: null,
                    crop: null,
                },

                adjustmentConfigs: {
                    brightness: { label: 'Brillo', min: -100, max: 100 },
                    contrast: { label: 'Contraste', min: -100, max: 100 },
                    saturation: { label: 'Saturación', min: -100, max: 100 },
                    exposure: { label: 'Exposición', min: -100, max: 100 },
                    temperature: { label: 'Temperatura', min: -100, max: 100 },
                    shadows: { label: 'Sombras', min: -100, max: 100 },
                    highlights: { label: 'Iluminaciones', min: -100, max: 100 },
                    vibrance: { label: 'Intensidad', min: -100, max: 100 },
                },

                filters: [
                    { id: null, name: 'Original', icon: '🎨' },
                    { id: 'bw', name: 'B&N', icon: '⚫' },
                    { id: 'sepia', name: 'Sepia', icon: '🟤' },
                    { id: 'vintage', name: 'Vintage', icon: '📷' },
                    { id: 'cool', name: 'Frío', icon: '❄️' },
                    { id: 'warm', name: 'Cálido', icon: '🔥' },
                ],

                init() {
                    this.canvas = this.$refs.canvas;
                    this.ctx = this.canvas?.getContext('2d');

                    // Comparison drag events
                    document.addEventListener('mousemove', (e) => this.handleComparisonDrag(e));
                    document.addEventListener('mouseup', () => this.isDraggingComparison = false);
                },

                async selectImage(image) {
                    this.currentImage = image;
                    this.resetAdjustments();

                    // Wait for DOM update then load image
                    this.$nextTick(async () => {
                        this.canvas = this.$refs.canvas;
                        this.ctx = this.canvas?.getContext('2d');
                        if (this.canvas && this.ctx) {
                            await this.loadImageToCanvas(image.url);
                        }
                    });

                    await this.fetchHistory();
                },

                async loadImageToCanvas(url) {
                    return new Promise((resolve, reject) => {
                        const img = new Image();
                        img.crossOrigin = 'anonymous';
                        img.onload = () => {
                            if (!this.canvas || !this.ctx) {
                                reject('Canvas not ready');
                                return;
                            }
                            this.canvas.width = img.width;
                            this.canvas.height = img.height;
                            this.ctx.drawImage(img, 0, 0);
                            this.originalImageData = this.ctx.getImageData(0, 0, img.width, img.height);
                            resolve();
                        };
                        img.onerror = () => reject('Failed to load image');
                        img.src = url;
                    });
                },

                async fetchHistory() {
                    if (!this.currentImage) return;
                    try {
                        const res = await fetch(`/images/${this.currentImage.id}`);
                        const data = await res.json();
                        this.history = data.history || [];
                    } catch (e) {
                        console.error('Error fetching history:', e);
                    }
                },

                debounceApply() {
                    clearTimeout(this.applyTimeout);
                    this.applyTimeout = setTimeout(() => {
                        requestAnimationFrame(() => this.applyAdjustments());
                    }, 150);
                },

                applyAdjustments() {
                    if (!this.originalImageData || this.isProcessing) return;
                    this.isProcessing = true;

                    const imageData = new ImageData(
                        new Uint8ClampedArray(this.originalImageData.data),
                        this.originalImageData.width,
                        this.originalImageData.height
                    );

                    const data = imageData.data;
                    const len = data.length;
                    const { brightness, contrast, saturation, exposure, temperature, shadows, highlights, vibrance } = this.adjustments;

                    // Pre-calculate factors
                    const brightnessFactor = brightness * 2.55;
                    const contrastFactor = (259 * (contrast + 255)) / (255 * (259 - contrast));
                    const satFactor = 1 + (saturation / 100);
                    const gamma = exposure !== 0 ? 1 / (1 + (exposure / 100)) : 1;
                    const tempR = temperature * 0.5;
                    const tempB = -temperature * 0.5;
                    const vibAmt = vibrance / 100;

                    for (let i = 0; i < data.length; i += 4) {
                        let r = data[i];
                        let g = data[i + 1];
                        let b = data[i + 2];

                        // Brightness
                        r += brightness * 2.55;
                        g += brightness * 2.55;
                        b += brightness * 2.55;

                        // Contrast
                        const factor = (259 * (contrast + 255)) / (255 * (259 - contrast));
                        r = factor * (r - 128) + 128;
                        g = factor * (g - 128) + 128;
                        b = factor * (b - 128) + 128;

                        // Exposure (gamma)
                        if (exposure !== 0) {
                            const gamma = 1 + (exposure / 100);
                            r = 255 * Math.pow(r / 255, 1 / gamma);
                            g = 255 * Math.pow(g / 255, 1 / gamma);
                            b = 255 * Math.pow(b / 255, 1 / gamma);
                        }

                        // Temperature
                        if (temperature !== 0) {
                            r += temperature * 0.5;
                            b -= temperature * 0.5;
                        }

                        // Saturation
                        if (saturation !== 0) {
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            const sat = 1 + (saturation / 100);
                            r = gray + sat * (r - gray);
                            g = gray + sat * (g - gray);
                            b = gray + sat * (b - gray);
                        }

                        // Shadows/Highlights
                        const luminance = (r + g + b) / 3;
                        if (luminance < 128 && shadows !== 0) {
                            const shadowFactor = 1 + (shadows / 100) * (1 - luminance / 128);
                            r *= shadowFactor;
                            g *= shadowFactor;
                            b *= shadowFactor;
                        }
                        if (luminance > 128 && highlights !== 0) {
                            const highlightFactor = 1 + (highlights / 100) * ((luminance - 128) / 128);
                            r *= highlightFactor;
                            g *= highlightFactor;
                            b *= highlightFactor;
                        }

                        // Vibrance
                        if (vibrance !== 0) {
                            const max = Math.max(r, g, b);
                            const avg = (r + g + b) / 3;
                            const amt = ((Math.abs(max - avg) * 2 / 255) * vibrance) / 100;
                            r += (max - r) * amt;
                            g += (max - g) * amt;
                            b += (max - b) * amt;
                        }

                        // Apply filter
                        if (this.adjustments.filter === 'bw') {
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            r = g = b = gray;
                        } else if (this.adjustments.filter === 'sepia') {
                            const tr = 0.393 * r + 0.769 * g + 0.189 * b;
                            const tg = 0.349 * r + 0.686 * g + 0.168 * b;
                            const tb = 0.272 * r + 0.534 * g + 0.131 * b;
                            r = tr; g = tg; b = tb;
                        } else if (this.adjustments.filter === 'vintage') {
                            r = r * 0.9 + 30;
                            g = g * 0.85 + 20;
                            b = b * 0.7;
                        } else if (this.adjustments.filter === 'cool') {
                            r *= 0.9;
                            b *= 1.1;
                        } else if (this.adjustments.filter === 'warm') {
                            r *= 1.1;
                            b *= 0.9;
                        }

                        data[i] = Math.min(255, Math.max(0, r));
                        data[i + 1] = Math.min(255, Math.max(0, g));
                        data[i + 2] = Math.min(255, Math.max(0, b));
                    }

                    this.ctx.putImageData(imageData, 0, 0);
                },

                applyFilter(filterId) {
                    this.adjustments.filter = filterId;
                    this.applyAdjustments();
                },

                rotate(degrees) {
                    this.adjustments.rotation = (this.adjustments.rotation + degrees) % 360;

                    const canvas = this.canvas;
                    const ctx = this.ctx;
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

                    if (Math.abs(degrees) === 90) {
                        const newWidth = canvas.height;
                        const newHeight = canvas.width;

                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = newWidth;
                        tempCanvas.height = newHeight;
                        const tempCtx = tempCanvas.getContext('2d');

                        tempCtx.translate(newWidth / 2, newHeight / 2);
                        tempCtx.rotate(degrees * Math.PI / 180);
                        tempCtx.drawImage(canvas, -canvas.width / 2, -canvas.height / 2);

                        canvas.width = newWidth;
                        canvas.height = newHeight;
                        ctx.drawImage(tempCanvas, 0, 0);

                        this.originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    }
                },

                flip(direction) {
                    const canvas = this.canvas;
                    const ctx = this.ctx;

                    const tempCanvas = document.createElement('canvas');
                    tempCanvas.width = canvas.width;
                    tempCanvas.height = canvas.height;
                    const tempCtx = tempCanvas.getContext('2d');
                    tempCtx.drawImage(canvas, 0, 0);

                    ctx.save();
                    if (direction === 'h') {
                        this.adjustments.flipH = !this.adjustments.flipH;
                        ctx.translate(canvas.width, 0);
                        ctx.scale(-1, 1);
                    } else {
                        this.adjustments.flipV = !this.adjustments.flipV;
                        ctx.translate(0, canvas.height);
                        ctx.scale(1, -1);
                    }
                    ctx.drawImage(tempCanvas, 0, 0);
                    ctx.restore();

                    this.originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                },

                toggleCropMode() {
                    this.cropMode = !this.cropMode;

                    if (this.cropMode) {
                        this.$nextTick(() => {
                            const cropperImage = this.$refs.cropperImage;
                            if (cropperImage && window.Cropper) {
                                this.cropper = new Cropper(cropperImage, {
                                    viewMode: 1,
                                    dragMode: 'crop',
                                    autoCropArea: 0.8,
                                    responsive: true,
                                });
                            }
                        });
                    } else if (this.cropper) {
                        const cropData = this.cropper.getData();
                        if (cropData.width > 0 && cropData.height > 0) {
                            this.adjustments.crop = {
                                x: Math.round(cropData.x),
                                y: Math.round(cropData.y),
                                width: Math.round(cropData.width),
                                height: Math.round(cropData.height),
                            };
                            this.applyCrop();
                        }
                        this.cropper.destroy();
                        this.cropper = null;
                    }
                },

                applyCrop() {
                    const crop = this.adjustments.crop;
                    if (!crop) return;

                    const imageData = this.ctx.getImageData(crop.x, crop.y, crop.width, crop.height);
                    this.canvas.width = crop.width;
                    this.canvas.height = crop.height;
                    this.ctx.putImageData(imageData, 0, 0);
                    this.originalImageData = this.ctx.getImageData(0, 0, crop.width, crop.height);
                },

                resetAdjustments() {
                    this.adjustments = {
                        brightness: 0,
                        contrast: 0,
                        saturation: 0,
                        exposure: 0,
                        temperature: 0,
                        shadows: 0,
                        highlights: 0,
                        vibrance: 0,
                        rotation: 0,
                        flipH: false,
                        flipV: false,
                        filter: null,
                        crop: null,
                    };
                    if (this.currentImage) {
                        this.loadImageToCanvas(this.currentImage.url);
                    }
                },

                async saveVersion() {
                    if (!this.currentImage) return;
                    this.loading = true;

                    try {
                        const res = await fetch(`/images/${this.currentImage.id}/process`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                adjustments: this.adjustments,
                                save_version: true,
                            }),
                        });

                        if (res.ok) {
                            await this.fetchHistory();
                        }
                    } catch (e) {
                        console.error('Error saving version:', e);
                    }

                    this.loading = false;
                },

                async loadVersion(version) {
                    this.adjustments = { ...this.adjustments, ...version.adjustments };
                    this.applyAdjustments();
                },

                // Comparison
                startComparisonDrag(e) {
                    this.isDraggingComparison = true;
                },

                handleComparisonDrag(e) {
                    if (!this.isDraggingComparison) return;
                    const container = document.getElementById('canvas-container');
                    const rect = container.getBoundingClientRect();
                    this.comparisonPosition = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                },

                // File handling
                async handleFileSelect(e) {
                    const file = e.target.files[0];
                    if (file) await this.uploadFile(file);
                },

                async handleDrop(e) {
                    this.dragOver = false;
                    const file = e.dataTransfer.files[0];
                    if (file && file.type.startsWith('image/')) {
                        await this.uploadFile(file);
                    }
                },

                async uploadFile(file) {
                    this.uploading = true;
                    this.uploadProgress = 0;

                    const formData = new FormData();
                    formData.append('image', file);

                    try {
                        const res = await fetch('/images', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: formData,
                        });

                        if (res.ok) {
                            const data = await res.json();
                            this.images.unshift(data.image);
                            this.selectImage(data.image);
                            this.showUploadModal = false;
                        }
                    } catch (e) {
                        console.error('Upload error:', e);
                    }

                    this.uploading = false;
                },

                async deleteImage(id) {
                    if (!confirm('¿Eliminar esta imagen?')) return;

                    try {
                        await fetch(`/images/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });

                        this.images = this.images.filter(img => img.id !== id);
                        if (this.currentImage?.id === id) {
                            this.currentImage = null;
                        }
                    } catch (e) {
                        console.error('Delete error:', e);
                    }
                },

                async downloadImage() {
                    if (!this.currentImage) return;
                    this.loading = true;

                    try {
                        const res = await fetch(`/images/${this.currentImage.id}/download`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                format: this.exportFormat,
                                quality: this.exportQuality,
                                adjustments: this.adjustments,
                            }),
                        });

                        if (res.ok) {
                            const blob = await res.blob();
                            const url = URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = `${this.currentImage.name}_edited.${this.exportFormat}`;
                            a.click();
                            URL.revokeObjectURL(url);
                        }
                    } catch (e) {
                        console.error('Download error:', e);
                    }

                    this.loading = false;
                    this.showExportModal = false;
                },
            };
        }
    </script>
</body>
</html>
