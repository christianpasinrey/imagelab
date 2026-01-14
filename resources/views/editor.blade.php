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
                    <div x-show="currentImage" class="relative select-none" :style="`transform: scale(${zoom/100})`">
                        <!-- Canvas for edited preview -->
                        <canvas x-ref="canvas" class="max-w-full max-h-[70vh] block select-none" :class="{'opacity-50': isProcessing}"></canvas>

                        <!-- Original image for comparison (overlaid on right side) -->
                        <template x-if="showComparison && comparisonSrc">
                            <div class="absolute top-0 right-0 overflow-hidden pointer-events-none h-full select-none"
                                :style="`width: ${100 - comparisonPosition}%`">
                                <img :src="comparisonSrc"
                                    class="absolute top-0 right-0 max-w-none h-full select-none"
                                    draggable="false">
                            </div>
                        </template>

                        <!-- Processing indicator -->
                        <div x-show="isProcessing" class="absolute inset-0 flex items-center justify-center pointer-events-none">
                            <div class="bg-editor-surface/80 rounded-full p-3">
                                <svg class="animate-spin w-6 h-6 text-editor-accent" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>

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

                        <!-- Undo/Redo -->
                        <button @click="undo()" :disabled="undoStack.length === 0"
                            :class="undoStack.length === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-editor-border'"
                            class="p-2 bg-editor-surface-hover rounded-lg transition-colors" title="Deshacer (Ctrl+Z)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>
                        <button @click="redo()" :disabled="redoStack.length === 0"
                            :class="redoStack.length === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-editor-border'"
                            class="p-2 bg-editor-surface-hover rounded-lg transition-colors" title="Rehacer (Ctrl+Y)">
                            <svg class="w-5 h-5 transform scale-x-[-1]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>

                        <div class="w-px h-6 bg-editor-border mx-2"></div>

                        <button @click="resetAdjustments()" class="p-2 bg-editor-surface-hover rounded-lg hover:bg-editor-border transition-colors" title="Resetear (Delete)">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                        <button @click="saveVersion()" class="px-3 py-2 bg-editor-accent hover:bg-editor-accent-hover rounded-lg text-sm font-medium transition-colors" title="Guardar versión (Ctrl+S)">
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
                        class="flex-1 py-2 text-xs font-medium transition-colors">
                        Ajustes
                    </button>
                    <button @click="activeTab = 'filters'"
                        :class="activeTab === 'filters' ? 'border-b-2 border-editor-accent text-editor-text' : 'text-editor-text-muted'"
                        class="flex-1 py-2 text-xs font-medium transition-colors">
                        Filtros
                    </button>
                    <button @click="activeTab = 'text'"
                        :class="activeTab === 'text' ? 'border-b-2 border-editor-accent text-editor-text' : 'text-editor-text-muted'"
                        class="flex-1 py-2 text-xs font-medium transition-colors">
                        Texto
                    </button>
                    <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'border-b-2 border-editor-accent text-editor-text' : 'text-editor-text-muted'"
                        class="flex-1 py-2 text-xs font-medium transition-colors">
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

                    <!-- Text Tab -->
                    <div x-show="activeTab === 'text'" class="p-4 space-y-4">
                        <!-- Text Input -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Texto</label>
                            <input type="text" x-model="textOverlay.content"
                                @input="renderTextPreview()"
                                placeholder="Escribe tu texto..."
                                class="w-full bg-editor-bg border border-editor-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-editor-accent">
                        </div>

                        <!-- Font -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Fuente</label>
                            <select x-model="textOverlay.font" @change="renderTextPreview()"
                                class="w-full bg-editor-bg border border-editor-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-editor-accent">
                                <option value="Arial">Arial</option>
                                <option value="Helvetica">Helvetica</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Times New Roman">Times New Roman</option>
                                <option value="Courier New">Courier New</option>
                                <option value="Verdana">Verdana</option>
                                <option value="Impact">Impact</option>
                            </select>
                        </div>

                        <!-- Size -->
                        <div>
                            <div class="flex justify-between mb-2">
                                <label class="text-sm font-medium">Tamaño</label>
                                <span class="text-sm text-editor-text-muted" x-text="textOverlay.size + 'px'"></span>
                            </div>
                            <input type="range" min="12" max="200" x-model.number="textOverlay.size"
                                @input="renderTextPreview()" class="w-full">
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Color</label>
                            <div class="flex gap-2">
                                <input type="color" x-model="textOverlay.color" @input="renderTextPreview()"
                                    class="w-10 h-10 rounded cursor-pointer border-0">
                                <input type="text" x-model="textOverlay.color" @input="renderTextPreview()"
                                    class="flex-1 bg-editor-bg border border-editor-border rounded-lg px-3 py-2 text-sm">
                            </div>
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Posición</label>
                            <div class="grid grid-cols-3 gap-1">
                                <template x-for="pos in textPositions" :key="pos.id">
                                    <button @click="textOverlay.position = pos.id; renderTextPreview()"
                                        :class="textOverlay.position === pos.id ? 'bg-editor-accent' : 'bg-editor-bg hover:bg-editor-surface-hover'"
                                        class="p-2 rounded text-xs transition-colors" x-text="pos.label">
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- Opacity -->
                        <div>
                            <div class="flex justify-between mb-2">
                                <label class="text-sm font-medium">Opacidad</label>
                                <span class="text-sm text-editor-text-muted" x-text="textOverlay.opacity + '%'"></span>
                            </div>
                            <input type="range" min="10" max="100" x-model.number="textOverlay.opacity"
                                @input="renderTextPreview()" class="w-full">
                        </div>

                        <!-- Blend Mode -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Modo de fusión</label>
                            <select x-model="textOverlay.blendMode" @change="renderTextPreview()"
                                class="w-full bg-editor-bg border border-editor-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-editor-accent">
                                <template x-for="mode in blendModes" :key="mode.id">
                                    <option :value="mode.id" x-text="mode.name"></option>
                                </template>
                            </select>
                        </div>

                        <!-- Style Options -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Estilo</label>
                            <div class="flex gap-2">
                                <button @click="textOverlay.bold = !textOverlay.bold; renderTextPreview()"
                                    :class="textOverlay.bold ? 'bg-editor-accent' : 'bg-editor-bg'"
                                    class="flex-1 py-2 rounded-lg text-sm font-bold transition-colors">B</button>
                                <button @click="textOverlay.italic = !textOverlay.italic; renderTextPreview()"
                                    :class="textOverlay.italic ? 'bg-editor-accent' : 'bg-editor-bg'"
                                    class="flex-1 py-2 rounded-lg text-sm italic transition-colors">I</button>
                                <button @click="textOverlay.shadow = !textOverlay.shadow; renderTextPreview()"
                                    :class="textOverlay.shadow ? 'bg-editor-accent' : 'bg-editor-bg'"
                                    class="flex-1 py-2 rounded-lg text-sm transition-colors">Sombra</button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-2">
                            <button @click="applyTextToCanvas()"
                                :disabled="!textOverlay.content"
                                :class="textOverlay.content ? 'bg-editor-accent hover:bg-editor-accent-hover' : 'bg-editor-border cursor-not-allowed'"
                                class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors">
                                Aplicar Texto
                            </button>
                            <button @click="clearTextPreview()"
                                class="px-4 py-2 bg-editor-bg hover:bg-editor-surface-hover rounded-lg text-sm transition-colors">
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <!-- History Tab -->
                    <div x-show="activeTab === 'history'" class="p-4">
                        <div class="space-y-3">
                            <template x-for="version in history" :key="version.id">
                                <div @click="loadVersion(version)"
                                    class="bg-editor-bg rounded-lg cursor-pointer hover:bg-editor-surface-hover transition-colors overflow-hidden group">
                                    <!-- Thumbnail -->
                                    <div class="relative aspect-video bg-editor-surface">
                                        <img x-show="version.thumbnail" :src="version.thumbnail"
                                            class="w-full h-full object-cover">
                                        <div x-show="!version.thumbnail" class="w-full h-full flex items-center justify-center text-editor-text-muted">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <!-- Overlay on hover -->
                                        <div class="absolute inset-0 bg-editor-accent/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="text-xs font-medium bg-editor-surface/90 px-2 py-1 rounded">Cargar</span>
                                        </div>
                                    </div>
                                    <!-- Info -->
                                    <div class="p-2 flex justify-between items-center">
                                        <span class="text-sm font-medium">v<span x-text="version.version"></span></span>
                                        <span class="text-xs text-editor-text-muted" x-text="version.created_at"></span>
                                    </div>
                                </div>
                            </template>
                            <div x-show="history.length === 0" class="text-center py-8 text-editor-text-muted text-sm">
                                <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Sin historial aún<br>
                                <span class="text-xs">Guarda versiones para verlas aquí</span>
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

        <!-- Toast Notifications -->
        <div class="fixed bottom-4 right-4 z-50 space-y-2">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.visible"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    :class="{
                        'bg-green-600': toast.type === 'success',
                        'bg-red-600': toast.type === 'error',
                        'bg-editor-accent': toast.type === 'info'
                    }"
                    class="px-4 py-3 rounded-lg shadow-lg flex items-center gap-3 text-white min-w-[250px]">
                    <!-- Icon -->
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                    <span class="text-sm font-medium" x-text="toast.message"></span>
                </div>
            </template>
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

                // Toasts
                toasts: [],
                toastId: 0,

                // Canvas
                canvas: null,
                ctx: null,
                originalImageData: null,
                comparisonSrc: null,
                canvasWidth: 0,
                canvasHeight: 0,
                zoom: 100,
                isProcessing: false,

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

                // Undo/Redo
                undoStack: [],
                redoStack: [],
                maxUndoSteps: 20,

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
                    vignette: 0,
                    sharpness: 0,
                    grain: 0,
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
                    vignette: { label: 'Viñeta', min: 0, max: 100 },
                    sharpness: { label: 'Nitidez', min: 0, max: 100 },
                    grain: { label: 'Grano', min: 0, max: 100 },
                },

                filters: [
                    { id: null, name: 'Original', icon: '🎨' },
                    { id: 'bw', name: 'B&N', icon: '⚫' },
                    { id: 'sepia', name: 'Sepia', icon: '🟤' },
                    { id: 'vintage', name: 'Vintage', icon: '📷' },
                    { id: 'cool', name: 'Frío', icon: '❄️' },
                    { id: 'warm', name: 'Cálido', icon: '🔥' },
                    { id: 'clarendon', name: 'Clarendon', icon: '🌊' },
                    { id: 'gingham', name: 'Gingham', icon: '🌸' },
                    { id: 'moon', name: 'Moon', icon: '🌙' },
                    { id: 'lark', name: 'Lark', icon: '🐦' },
                    { id: 'reyes', name: 'Reyes', icon: '☀️' },
                    { id: 'juno', name: 'Juno', icon: '🔶' },
                    { id: 'hdr', name: 'HDR', icon: '🌈' },
                    { id: 'dramatic', name: 'Dramático', icon: '🎭' },
                    { id: 'fade', name: 'Fade', icon: '🌫️' },
                    { id: 'cinema', name: 'Cinema', icon: '🎬' },
                    { id: 'noir', name: 'Noir', icon: '🖤' },
                    { id: 'nashville', name: 'Nashville', icon: '🎸' },
                    { id: 'valencia', name: 'Valencia', icon: '🍊' },
                    { id: 'xpro', name: 'X-Pro', icon: '✨' },
                ],

                // Text overlay
                textOverlay: {
                    content: '',
                    font: 'Arial',
                    size: 48,
                    color: '#ffffff',
                    opacity: 100,
                    position: 'center',
                    bold: false,
                    italic: false,
                    shadow: true,
                    blendMode: 'normal',
                },

                blendModes: [
                    { id: 'normal', name: 'Normal' },
                    { id: 'multiply', name: 'Multiplicar' },
                    { id: 'screen', name: 'Trama' },
                    { id: 'overlay', name: 'Superponer' },
                    { id: 'color-burn', name: 'Subexp. color' },
                    { id: 'color-dodge', name: 'Sobreexp. color' },
                    { id: 'soft-light', name: 'Luz suave' },
                    { id: 'hard-light', name: 'Luz fuerte' },
                ],

                textPositions: [
                    { id: 'top-left', label: '↖' },
                    { id: 'top-center', label: '↑' },
                    { id: 'top-right', label: '↗' },
                    { id: 'center-left', label: '←' },
                    { id: 'center', label: '●' },
                    { id: 'center-right', label: '→' },
                    { id: 'bottom-left', label: '↙' },
                    { id: 'bottom-center', label: '↓' },
                    { id: 'bottom-right', label: '↘' },
                ],

                textPreviewActive: false,

                init() {
                    this.canvas = this.$refs.canvas;
                    this.ctx = this.canvas?.getContext('2d');

                    // Comparison drag events
                    document.addEventListener('mousemove', (e) => this.handleComparisonDrag(e));
                    document.addEventListener('mouseup', () => this.stopComparisonDrag());

                    // Keyboard shortcuts
                    document.addEventListener('keydown', (e) => this.handleKeyboard(e));
                },

                handleKeyboard(e) {
                    if (!this.currentImage) return;

                    // Ignore shortcuts when typing in input fields
                    const tagName = e.target.tagName.toLowerCase();
                    if (tagName === 'input' || tagName === 'textarea' || tagName === 'select') {
                        return;
                    }

                    // Ctrl/Cmd combinations
                    if (e.ctrlKey || e.metaKey) {
                        switch(e.key.toLowerCase()) {
                            case 'z':
                                e.preventDefault();
                                if (e.shiftKey) this.redo();
                                else this.undo();
                                break;
                            case 'y':
                                e.preventDefault();
                                this.redo();
                                break;
                            case 's':
                                e.preventDefault();
                                this.saveVersion();
                                break;
                            case 'e':
                                e.preventDefault();
                                this.showExportModal = true;
                                break;
                            case '0':
                                e.preventDefault();
                                this.zoom = 100;
                                break;
                        }
                        return;
                    }

                    // Single key shortcuts
                    switch(e.key.toLowerCase()) {
                        case 'r':
                            this.rotate(90);
                            break;
                        case 'h':
                            this.flip('h');
                            break;
                        case 'v':
                            this.flip('v');
                            break;
                        case 'c':
                            this.toggleCropMode();
                            break;
                        case 'o':
                            this.showComparison = !this.showComparison;
                            break;
                        case 'escape':
                            if (this.cropMode) this.toggleCropMode();
                            break;
                        case '+':
                        case '=':
                            this.zoom = Math.min(200, this.zoom + 25);
                            break;
                        case '-':
                            this.zoom = Math.max(25, this.zoom - 25);
                            break;
                        case 'backspace':
                        case 'delete':
                            if (!e.target.matches('input')) this.resetAdjustments();
                            break;
                    }
                },

                saveToUndoStack() {
                    this.undoStack.push(JSON.stringify(this.adjustments));
                    if (this.undoStack.length > this.maxUndoSteps) {
                        this.undoStack.shift();
                    }
                    this.redoStack = [];
                },

                undo() {
                    if (this.undoStack.length === 0) return;
                    this.redoStack.push(JSON.stringify(this.adjustments));
                    this.adjustments = JSON.parse(this.undoStack.pop());
                    this.applyAdjustments();
                },

                redo() {
                    if (this.redoStack.length === 0) return;
                    this.undoStack.push(JSON.stringify(this.adjustments));
                    this.adjustments = JSON.parse(this.redoStack.pop());
                    this.applyAdjustments();
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
                            this.canvasWidth = img.width;
                            this.canvasHeight = img.height;
                            this.ctx.drawImage(img, 0, 0);
                            this.originalImageData = this.ctx.getImageData(0, 0, img.width, img.height);
                            this.comparisonSrc = this.canvas.toDataURL('image/jpeg', 0.9);
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

                debounceApply(saveUndo = true) {
                    this.isProcessing = true;
                    clearTimeout(this.applyTimeout);
                    this.applyTimeout = setTimeout(() => {
                        if (saveUndo) this.saveToUndoStack();
                        requestAnimationFrame(() => this.applyAdjustments());
                    }, 100);
                },

                applyAdjustments() {
                    if (!this.originalImageData) {
                        this.isProcessing = false;
                        return;
                    }

                    const imageData = new ImageData(
                        new Uint8ClampedArray(this.originalImageData.data),
                        this.originalImageData.width,
                        this.originalImageData.height
                    );

                    const data = imageData.data;
                    const len = data.length;
                    const width = imageData.width;
                    const height = imageData.height;
                    const { brightness, contrast, saturation, exposure, temperature, shadows, highlights, vibrance, vignette, grain } = this.adjustments;

                    // Pre-calculate factors
                    const brightnessFactor = brightness * 2.55;
                    const contrastFactor = (259 * (contrast + 255)) / (255 * (259 - contrast));
                    const satFactor = 1 + (saturation / 100);
                    const gamma = exposure !== 0 ? 1 / (1 + (exposure / 100)) : 1;
                    const tempR = temperature * 0.5;
                    const tempB = -temperature * 0.5;
                    const vibAmt = vibrance / 100;
                    const grainAmt = grain * 2.55;

                    // Vignette pre-calculations
                    const centerX = width / 2;
                    const centerY = height / 2;
                    const maxDist = Math.sqrt(centerX * centerX + centerY * centerY);
                    const vignetteStrength = vignette / 100;

                    const filter = this.adjustments.filter;

                    for (let i = 0; i < len; i += 4) {
                        let r = data[i] + brightnessFactor;
                        let g = data[i + 1] + brightnessFactor;
                        let b = data[i + 2] + brightnessFactor;

                        // Contrast
                        r = contrastFactor * (r - 128) + 128;
                        g = contrastFactor * (g - 128) + 128;
                        b = contrastFactor * (b - 128) + 128;

                        // Temperature
                        r += tempR;
                        b += tempB;

                        // Saturation
                        if (saturation !== 0) {
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            r = gray + satFactor * (r - gray);
                            g = gray + satFactor * (g - gray);
                            b = gray + satFactor * (b - gray);
                        }

                        // Exposure
                        if (exposure !== 0) {
                            r = 255 * Math.pow(Math.max(0, r) / 255, gamma);
                            g = 255 * Math.pow(Math.max(0, g) / 255, gamma);
                            b = 255 * Math.pow(Math.max(0, b) / 255, gamma);
                        }

                        // Shadows & Highlights
                        if (shadows !== 0 || highlights !== 0) {
                            const lum = (r + g + b) / 3;
                            if (lum < 128 && shadows !== 0) {
                                const sf = 1 + (shadows / 100) * (1 - lum / 128);
                                r *= sf; g *= sf; b *= sf;
                            }
                            if (lum >= 128 && highlights !== 0) {
                                const hf = 1 + (highlights / 100) * ((lum - 128) / 128);
                                r *= hf; g *= hf; b *= hf;
                            }
                        }

                        // Vibrance
                        if (vibrance !== 0) {
                            const maxC = Math.max(r, g, b);
                            const avg = (r + g + b) / 3;
                            const amt = ((Math.abs(maxC - avg) * 2 / 255) * vibAmt);
                            r += (maxC - r) * amt;
                            g += (maxC - g) * amt;
                            b += (maxC - b) * amt;
                        }

                        // Filter
                        if (filter === 'bw') {
                            r = g = b = 0.2989 * r + 0.587 * g + 0.114 * b;
                        } else if (filter === 'sepia') {
                            const tr = 0.393 * r + 0.769 * g + 0.189 * b;
                            const tg = 0.349 * r + 0.686 * g + 0.168 * b;
                            const tb = 0.272 * r + 0.534 * g + 0.131 * b;
                            r = tr; g = tg; b = tb;
                        } else if (filter === 'vintage') {
                            r = r * 0.9 + 30; g = g * 0.85 + 20; b = b * 0.7;
                        } else if (filter === 'cool') {
                            r *= 0.9; b *= 1.1;
                        } else if (filter === 'warm') {
                            r *= 1.1; b *= 0.9;
                        } else if (filter === 'clarendon') {
                            // High contrast, saturated
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            r = gray + 1.3 * (r - gray); g = gray + 1.3 * (g - gray); b = gray + 1.3 * (b - gray);
                            r *= 1.1; b *= 1.1;
                        } else if (filter === 'gingham') {
                            // Soft, slightly washed
                            r = r * 0.9 + 25; g = g * 0.95 + 15; b = b * 0.95 + 20;
                        } else if (filter === 'moon') {
                            // Desaturated, cool
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            r = gray + 0.3 * (r - gray); g = gray + 0.3 * (g - gray); b = gray + 0.4 * (b - gray);
                        } else if (filter === 'lark') {
                            // Bright, desaturated slightly
                            r *= 1.1; g *= 1.05; b *= 0.95;
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            r = gray + 0.85 * (r - gray); g = gray + 0.85 * (g - gray); b = gray + 0.85 * (b - gray);
                        } else if (filter === 'reyes') {
                            // Dusty, vintage warmth
                            r = r * 0.85 + 40; g = g * 0.9 + 25; b = b * 0.85 + 10;
                        } else if (filter === 'juno') {
                            // Warm highlights, cool shadows
                            const lum = (r + g + b) / 3;
                            if (lum > 128) { r *= 1.1; g *= 1.05; }
                            else { b *= 1.1; }
                        } else if (filter === 'hdr') {
                            // HDR effect: boost shadows and highlights, increase saturation
                            const lum = (r + g + b) / 3;
                            const factor = lum < 128 ? 1.3 : 0.85;
                            r = 128 + (r - 128) * 1.4;
                            g = 128 + (g - 128) * 1.4;
                            b = 128 + (b - 128) * 1.4;
                            const gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            r = gray + 1.3 * (r - gray);
                            g = gray + 1.3 * (g - gray);
                            b = gray + 1.3 * (b - gray);
                        } else if (filter === 'dramatic') {
                            // Dark, high contrast
                            r = 128 + (r - 128) * 1.5;
                            g = 128 + (g - 128) * 1.5;
                            b = 128 + (b - 128) * 1.5;
                            r *= 0.9; g *= 0.85; b *= 0.85;
                        } else if (filter === 'fade') {
                            // Washed out, low contrast
                            r = r * 0.8 + 40;
                            g = g * 0.8 + 40;
                            b = b * 0.85 + 35;
                        } else if (filter === 'cinema') {
                            // Film look: teal shadows, orange highlights
                            const lum = (r + g + b) / 3;
                            if (lum < 128) {
                                r *= 0.9; g *= 1.05; b *= 1.15;
                            } else {
                                r *= 1.1; g *= 1.0; b *= 0.85;
                            }
                            r = 128 + (r - 128) * 1.1;
                            g = 128 + (g - 128) * 1.05;
                            b = 128 + (b - 128) * 1.05;
                        } else if (filter === 'noir') {
                            // High contrast black and white
                            let gray = 0.2989 * r + 0.587 * g + 0.114 * b;
                            gray = 128 + (gray - 128) * 1.6;
                            r = g = b = gray;
                        } else if (filter === 'nashville') {
                            // Warm, pinkish vintage
                            r = r * 1.1 + 20;
                            g = g * 0.95 + 10;
                            b = b * 0.8 + 30;
                        } else if (filter === 'valencia') {
                            // Golden warmth
                            r = r * 1.08 + 10;
                            g = g * 1.02 + 5;
                            b = b * 0.85;
                        } else if (filter === 'xpro') {
                            // Cross-processed: high contrast, shifted colors
                            r = 128 + (r - 128) * 1.4;
                            g = 128 + (g - 128) * 1.2;
                            b = 128 + (b - 128) * 1.3;
                            r *= 1.1; b *= 0.9;
                            g = g * 0.95 + 10;
                        }

                        // Grain (noise)
                        if (grain > 0) {
                            const noise = (Math.random() - 0.5) * grainAmt;
                            r += noise; g += noise; b += noise;
                        }

                        // Vignette (darken edges)
                        if (vignette > 0) {
                            const pixelIndex = i / 4;
                            const x = pixelIndex % width;
                            const y = Math.floor(pixelIndex / width);
                            const dx = x - centerX;
                            const dy = y - centerY;
                            const dist = Math.sqrt(dx * dx + dy * dy) / maxDist;
                            const vignetteFactor = 1 - (dist * dist * vignetteStrength);
                            r *= vignetteFactor; g *= vignetteFactor; b *= vignetteFactor;
                        }

                        data[i] = r < 0 ? 0 : r > 255 ? 255 : r;
                        data[i + 1] = g < 0 ? 0 : g > 255 ? 255 : g;
                        data[i + 2] = b < 0 ? 0 : b > 255 ? 255 : b;
                    }

                    this.ctx.putImageData(imageData, 0, 0);

                    // Sharpness (using canvas filter for performance)
                    if (this.adjustments.sharpness > 0) {
                        const sharpAmt = this.adjustments.sharpness / 100;
                        this.ctx.globalAlpha = sharpAmt * 0.5;
                        this.ctx.globalCompositeOperation = 'hard-light';
                        this.ctx.drawImage(this.canvas, 0, 0);
                        this.ctx.globalAlpha = 1;
                        this.ctx.globalCompositeOperation = 'source-over';
                    }

                    this.isProcessing = false;
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
                        this.canvasWidth = newWidth;
                        this.canvasHeight = newHeight;
                        ctx.drawImage(tempCanvas, 0, 0);

                        this.originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        this.comparisonSrc = canvas.toDataURL('image/jpeg', 0.9);
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
                    this.comparisonSrc = canvas.toDataURL('image/jpeg', 0.9);
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
                    this.canvasWidth = crop.width;
                    this.canvasHeight = crop.height;
                    this.ctx.putImageData(imageData, 0, 0);
                    this.originalImageData = this.ctx.getImageData(0, 0, crop.width, crop.height);
                    this.comparisonSrc = this.canvas.toDataURL('image/jpeg', 0.9);
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
                        // Generate thumbnail from current canvas state
                        const thumbnail = this.generateThumbnail();

                        const res = await fetch(`/images/${this.currentImage.id}/process`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({
                                adjustments: this.adjustments,
                                save_version: true,
                                thumbnail: thumbnail,
                            }),
                        });

                        if (res.ok) {
                            await this.fetchHistory();
                            this.showToast('Versión guardada correctamente', 'success');
                        } else {
                            this.showToast('Error al guardar la versión', 'error');
                        }
                    } catch (e) {
                        console.error('Error saving version:', e);
                        this.showToast('Error al guardar la versión', 'error');
                    }

                    this.loading = false;
                },

                generateThumbnail() {
                    if (!this.canvas) return null;

                    // Create a smaller canvas for the thumbnail
                    const thumbCanvas = document.createElement('canvas');
                    const maxSize = 200;
                    const ratio = Math.min(maxSize / this.canvas.width, maxSize / this.canvas.height);
                    thumbCanvas.width = Math.round(this.canvas.width * ratio);
                    thumbCanvas.height = Math.round(this.canvas.height * ratio);

                    const thumbCtx = thumbCanvas.getContext('2d');
                    thumbCtx.drawImage(this.canvas, 0, 0, thumbCanvas.width, thumbCanvas.height);

                    return thumbCanvas.toDataURL('image/jpeg', 0.7);
                },

                async loadVersion(version) {
                    this.adjustments = { ...this.adjustments, ...version.adjustments };
                    this.applyAdjustments();
                },

                // Comparison
                startComparisonDrag(e) {
                    e.preventDefault();
                    this.isDraggingComparison = true;
                    document.body.style.userSelect = 'none';
                },

                handleComparisonDrag(e) {
                    if (!this.isDraggingComparison) return;
                    e.preventDefault();
                    const container = document.getElementById('canvas-container');
                    const rect = container.getBoundingClientRect();
                    this.comparisonPosition = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                },

                stopComparisonDrag() {
                    this.isDraggingComparison = false;
                    document.body.style.userSelect = '';
                },

                // Text overlay
                renderTextPreview() {
                    if (!this.canvas || !this.ctx || !this.textOverlay.content) {
                        if (this.textPreviewActive) {
                            this.applyAdjustments(); // Restore canvas without text
                            this.textPreviewActive = false;
                        }
                        return;
                    }

                    // First restore the canvas to current adjustments state
                    this.applyAdjustments();

                    // Then draw text on top
                    this.drawTextOnCanvas(false);
                    this.textPreviewActive = true;
                },

                drawTextOnCanvas(permanent = false) {
                    const ctx = this.ctx;
                    const text = this.textOverlay;
                    const canvas = this.canvas;

                    if (!text.content) return;

                    // Save current state
                    ctx.save();

                    // Apply blend mode and opacity
                    ctx.globalCompositeOperation = text.blendMode;
                    ctx.globalAlpha = text.opacity / 100;

                    // Build font string
                    let fontStyle = '';
                    if (text.italic) fontStyle += 'italic ';
                    if (text.bold) fontStyle += 'bold ';
                    fontStyle += `${text.size}px "${text.font}"`;

                    ctx.font = fontStyle;
                    ctx.fillStyle = text.color;
                    ctx.textBaseline = 'middle';

                    // Calculate position
                    const padding = 20;
                    const metrics = ctx.measureText(text.content);
                    const textWidth = metrics.width;
                    const textHeight = text.size;

                    let x, y;
                    const pos = text.position;

                    // Horizontal
                    if (pos.includes('left')) {
                        ctx.textAlign = 'left';
                        x = padding;
                    } else if (pos.includes('right')) {
                        ctx.textAlign = 'right';
                        x = canvas.width - padding;
                    } else {
                        ctx.textAlign = 'center';
                        x = canvas.width / 2;
                    }

                    // Vertical
                    if (pos.includes('top')) {
                        y = padding + textHeight / 2;
                    } else if (pos.includes('bottom')) {
                        y = canvas.height - padding - textHeight / 2;
                    } else {
                        y = canvas.height / 2;
                    }

                    // Shadow (only if blend mode is normal for best effect)
                    if (text.shadow && text.blendMode === 'normal') {
                        ctx.shadowColor = 'rgba(0, 0, 0, 0.7)';
                        ctx.shadowBlur = 4;
                        ctx.shadowOffsetX = 2;
                        ctx.shadowOffsetY = 2;
                    }

                    // Draw text
                    ctx.fillText(text.content, x, y);

                    // Restore state (resets blend mode, opacity, shadow, etc.)
                    ctx.restore();

                    if (permanent) {
                        // Update originalImageData to include the text
                        this.originalImageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                        this.comparisonSrc = canvas.toDataURL('image/jpeg', 0.9);
                    }
                },

                applyTextToCanvas() {
                    if (!this.textOverlay.content) return;

                    // Apply adjustments first, then draw text permanently
                    this.applyAdjustments();
                    this.drawTextOnCanvas(true);

                    // Clear the text input
                    this.textOverlay.content = '';
                    this.textPreviewActive = false;
                    this.showToast('Texto aplicado a la imagen', 'success');
                },

                clearTextPreview() {
                    this.textOverlay.content = '';
                    this.textPreviewActive = false;
                    this.applyAdjustments(); // Restore canvas without text
                },

                // Toast notifications
                showToast(message, type = 'info', duration = 3000) {
                    const id = ++this.toastId;
                    const toast = { id, message, type, visible: true };
                    this.toasts.push(toast);

                    setTimeout(() => {
                        const index = this.toasts.findIndex(t => t.id === id);
                        if (index !== -1) {
                            this.toasts[index].visible = false;
                            setTimeout(() => {
                                this.toasts = this.toasts.filter(t => t.id !== id);
                            }, 300);
                        }
                    }, duration);
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
                            this.showToast('Imagen subida correctamente', 'success');
                        } else {
                            this.showToast('Error al subir la imagen', 'error');
                        }
                    } catch (e) {
                        console.error('Upload error:', e);
                        this.showToast('Error al subir la imagen', 'error');
                    }

                    this.uploading = false;
                },

                async deleteImage(id) {
                    if (!confirm('¿Eliminar esta imagen?')) return;

                    try {
                        const res = await fetch(`/images/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                        });

                        if (res.ok) {
                            this.images = this.images.filter(img => img.id !== id);
                            if (this.currentImage?.id === id) {
                                this.currentImage = null;
                            }
                            this.showToast('Imagen eliminada', 'success');
                        }
                    } catch (e) {
                        console.error('Delete error:', e);
                        this.showToast('Error al eliminar la imagen', 'error');
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
