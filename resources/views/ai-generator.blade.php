@extends('layouts.app')

@section('title', 'SmartStudy - PPT Study AI')

@section('content')
<div class="p-8 space-y-6 max-w-7xl mx-auto" x-data="{
    activeTab: '{{ $activeMode }}',
    uploadModal: false,
    selectedTopic: '{{ $currentMaterial ? $currentMaterial->title : '' }}',
    selectedMode: '{{ $activeMode }}',
    isProcessing: false,
    processingStep: 'Membaca slide presentasi...',
    
    // Quiz State
    currentQuestion: 0,
    userAnswers: {},
    quizSubmitted: false,
    score: 0,
    totalQuizQuestions: {{ count($currentMaterial->data['quiz'] ?? []) }},
    correctAnswersCount: 0,
    selectAnswer(qIndex, optIndex, isCorrect) {
        if (this.userAnswers[qIndex] !== undefined) return;
        this.userAnswers[qIndex] = optIndex;
        if (isCorrect) {
            this.correctAnswersCount++;
        }
        const total = this.totalQuizQuestions > 0 ? this.totalQuizQuestions : 10;
        this.score = Math.round((this.correctAnswersCount / total) * 100);
    },
    resetQuiz() {
        this.currentQuestion = 0;
        this.userAnswers = {};
        this.quizSubmitted = false;
        this.correctAnswersCount = 0;
        this.score = 0;
    },

    // Flashcard State
    currentCard: 0,
    cardFlipped: false,
    masteredCards: [],
    flipCard() {
        this.cardFlipped = !this.cardFlipped;
    },
    toggleMastered(idx) {
        if (this.masteredCards.includes(idx)) {
            this.masteredCards = this.masteredCards.filter(i => i !== idx);
        } else {
            this.masteredCards.push(idx);
        }
    },

    // Copy Summary
    copySummaryText() {
        const el = document.getElementById('summary-content');
        if (el) {
            navigator.clipboard.writeText(el.innerText).then(() => {
                showToast('Rangkuman catatan berhasil disalin ke clipboard! 📋✨');
            });
        }
    }
}">

    <!-- Notifications -->
    @if(session('success'))
    <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between text-xs font-bold shadow-xs">
        <div class="flex items-center gap-2">
            <span>✨</span>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-black cursor-pointer">✕</button>
    </div>
    @endif

    @if(session('error') || (!empty($errors) && $errors->any()))
    <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold shadow-xs space-y-1">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-rose-900 font-black">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                <span>Gagal Mengunggah atau Memproses Materi:</span>
            </div>
            <button type="button" @click="$el.parentElement.remove()" class="text-rose-500 hover:text-rose-700 font-black cursor-pointer">✕</button>
        </div>
        @if(session('error'))
            <p>{{ session('error') }}</p>
        @endif
        @if(!empty($errors))
            @foreach($errors->all() as $err)
                <p>• {{ $err }}</p>
            @endforeach
        @endif
    </div>
    @endif

    <!-- ================= TOP HEADER BANNER (PINK-PURPLE AESTHETIC) ================= -->
    <div class="relative bg-gradient-to-r from-pink-50/95 via-purple-50/75 to-pink-50/85 border border-pink-200/80 rounded-3xl p-6 sm:p-7 flex flex-col md:flex-row md:items-center justify-between gap-6 overflow-hidden shadow-card">
        <!-- Ambient Glowing Background Elements -->
        <div class="absolute -top-12 -left-12 w-48 h-48 bg-pink-300/30 rounded-full blur-2xl pointer-events-none animate-pulse-glow"></div>
        <div class="absolute -bottom-10 right-1/4 w-60 h-36 bg-purple-300/30 rounded-full blur-2xl pointer-events-none animate-pulse-glow" style="animation-delay: 2s;"></div>
        <div class="absolute inset-0 w-1/3 h-full bg-gradient-to-r from-transparent via-white/50 to-transparent pointer-events-none animate-light-sweep"></div>

        <div class="relative z-10 max-w-xl">
            <!-- Active Badge -->
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/95 border border-purple-200/80 shadow-2xs backdrop-blur-xs text-[11px] font-bold text-purple-900 mb-2.5">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-gradient-to-r from-pink-500 to-purple-600"></span>
                </span>
                <span class="tracking-wide">AI Multi-Modal Study Converter</span>
                <span class="text-pink-500">✨</span>
            </div>

            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span>PPT to Study AI</span>
                <span class="text-2xl">🎓</span>
            </h1>

            <p class="text-xs sm:text-sm font-semibold text-slate-600 mt-1 leading-relaxed">
                Upload slide presentasi (.pptx / .pdf) dan AI akan mengekstraknya menjadi 
                <span class="text-purple-700 font-bold">Kuis Interaktif</span>, 
                <span class="text-pink-600 font-bold">Flashcard 3D</span>, atau 
                <span class="text-fuchsia-600 font-bold">Rangkuman Catatan</span>.
            </p>
        </div>

        <!-- Quick Upload Button -->
        <div class="relative z-20 flex items-center gap-3">
            <button @click="uploadModal = true"
                    class="bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white rounded-2xl px-5 py-3 text-xs font-black flex items-center gap-2 shadow-glow transition hover:scale-[1.02] cursor-pointer">
                <i data-lucide="upload-cloud" class="w-4 h-4"></i>
                <span>Upload PPT Baru</span>
                <span>✨</span>
            </button>
        </div>
    </div>

    <!-- ================= CURRENT PRESENTATION INFO & 4 MODE TABS ================= -->
    <div class="bg-white border border-slate-200/80 rounded-3xl p-5 sm:p-6 shadow-card space-y-5">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <!-- Active Presentation Meta -->
            <div class="flex items-center gap-3.5 min-w-0">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-purple-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                    <i data-lucide="presentation" class="w-6 h-6"></i>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600 uppercase tracking-wider">
                            Slide Terpilih:
                        </span>
                        <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 border border-purple-200 text-[10px] font-bold">
                            {{ $currentMaterial->slides_count ?? 15 }} Slides
                        </span>
                        <span class="text-[11px] text-slate-400 font-medium">({{ $currentMaterial->file_size ?? '3.5 MB' }})</span>
                    </div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 truncate">
                        {{ $currentMaterial->title ?? 'Materi Presentasi Kuliah' }}
                    </h2>
                </div>
            </div>

            <!-- Choose PPT Dropdown / Switcher & Delete Button -->
            <div class="flex items-center gap-2 text-xs flex-wrap sm:flex-nowrap">
                <span class="font-bold text-slate-500 hidden sm:inline">Ganti Slide:</span>
                <select onchange="window.location.href = '{{ route('ai-generator') }}?id=' + this.value + '&mode=' + '{{ $activeMode }}'"
                        class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-bold text-slate-700 focus:outline-none focus:ring-2 focus:ring-purple-400 cursor-pointer max-w-[260px] truncate">
                    @foreach($materials as $mat)
                    <option value="{{ $mat->id }}" {{ $currentMaterial && $currentMaterial->id === $mat->id ? 'selected' : '' }}>
                        {{ $mat->title }}
                    </option>
                    @endforeach
                </select>

                @if($currentMaterial)
                <form action="{{ route('ai-generator.destroy', $currentMaterial->id) }}" method="POST"
                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus slide \'{{ addslashes($currentMaterial->title) }}\' ini dari database? File yang dihapus tidak dapat dipulihkan.')" 
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            class="px-3 py-2 rounded-xl text-rose-600 hover:text-white hover:bg-rose-500 border border-rose-200 hover:border-rose-500 bg-rose-50/70 font-bold text-xs flex items-center gap-1.5 transition shadow-2xs cursor-pointer"
                            title="Hapus PPT ini yang sudah tidak digunakan">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">Hapus PPT</span>
                    </button>
                </form>
                @endif
            </div>
        </div>

        <!-- 3 OUTPUT MODE SELECTOR TABS -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- 1. Mode Quiz -->
            <a href="{{ route('ai-generator', ['id' => $currentMaterial->id ?? 1, 'mode' => 'quiz']) }}"
               class="p-4 rounded-2xl border text-left transition-all duration-200 group flex items-center gap-3.5 {{ $activeMode === 'quiz' ? 'bg-gradient-to-r from-pink-50 to-purple-50 border-purple-400 shadow-sm ring-2 ring-purple-100' : 'bg-slate-50/70 border-slate-200/80 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition {{ $activeMode === 'quiz' ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'bg-white text-slate-500 border border-slate-200' }}">
                    <i data-lucide="help-circle" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-black {{ $activeMode === 'quiz' ? 'text-purple-950' : 'text-slate-800' }}">1. Kuis Interaktif</p>
                    <p class="text-[10px] text-slate-500 font-semibold">Latihan soal ujian</p>
                </div>
            </a>

            <!-- 2. Mode Flashcards -->
            <a href="{{ route('ai-generator', ['id' => $currentMaterial->id ?? 1, 'mode' => 'flashcards']) }}"
               class="p-4 rounded-2xl border text-left transition-all duration-200 group flex items-center gap-3.5 {{ $activeMode === 'flashcards' ? 'bg-gradient-to-r from-pink-50 to-purple-50 border-purple-400 shadow-sm ring-2 ring-purple-100' : 'bg-slate-50/70 border-slate-200/80 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition {{ $activeMode === 'flashcards' ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'bg-white text-slate-500 border border-slate-200' }}">
                    <i data-lucide="layers" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-black {{ $activeMode === 'flashcards' ? 'text-purple-950' : 'text-slate-800' }}">2. Flashcard 3D</p>
                    <p class="text-[10px] text-slate-500 font-semibold">Active recall konsep</p>
                </div>
            </a>

            <!-- 3. Mode Rangkuman Catatan -->
            <a href="{{ route('ai-generator', ['id' => $currentMaterial->id ?? 1, 'mode' => 'summary']) }}"
               class="p-4 rounded-2xl border text-left transition-all duration-200 group flex items-center gap-3.5 {{ $activeMode === 'summary' ? 'bg-gradient-to-r from-pink-50 to-purple-50 border-purple-400 shadow-sm ring-2 ring-purple-100' : 'bg-slate-50/70 border-slate-200/80 hover:bg-slate-50 hover:border-slate-300' }}">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition {{ $activeMode === 'summary' ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'bg-white text-slate-500 border border-slate-200' }}">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                </div>
                <div>
                    <p class="text-xs font-black {{ $activeMode === 'summary' ? 'text-purple-950' : 'text-slate-800' }}">3. Rangkuman Catatan</p>
                    <p class="text-[10px] text-slate-500 font-semibold">Cheat sheet & mindmap</p>
                </div>
            </a>
        </div>
    </div>

    <!-- ================= MODE 1: KUIS INTERAKTIF ================= -->
    @if($activeMode === 'quiz')
    @php
        $quizData = $currentMaterial->data['quiz'] ?? [];
        $totalQuestions = count($quizData);
    @endphp
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-7 shadow-card space-y-6">
        <!-- Quiz Header & Score Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span class="p-1 rounded-lg bg-pink-50 text-pink-600">
                        <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                    </span>
                    <span>Kuis Evaluasi Pemahaman Slide</span>
                </h3>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Pilih jawaban yang paling tepat. AI akan langsung memberikan evaluasi dan penjelasan komprehensif.</p>
            </div>

            <!-- Score & Quiz Actions -->
            <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                <div class="bg-gradient-to-r from-pink-50 to-purple-50 border border-purple-200 px-4 py-2 rounded-2xl flex items-center gap-2 shadow-xs">
                    <span class="text-xs font-bold text-slate-600">Skor Anda:</span>
                    <span class="text-base font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600" x-text="score + ' / 100'"></span>
                    <span class="text-[11px] font-bold text-purple-700 bg-purple-100/90 px-2 py-0.5 rounded-lg" x-show="totalQuizQuestions > 0" x-text="'(' + correctAnswersCount + '/' + totalQuizQuestions + ' Benar)'"></span>
                </div>

                @if($currentMaterial)
                <!-- Refresh / Generate New Quiz Set -->
                <form action="{{ route('ai-generator.refresh-quiz', $currentMaterial->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" 
                            class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:opacity-95 text-white text-xs font-bold flex items-center gap-1.5 shadow-xs transition hover:scale-[1.02] cursor-pointer"
                            title="Generate atau acak variasi set soal kuis baru dari slide ini">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span>Refresh Soal Baru</span>
                    </button>
                </form>
                @endif

                <button type="button" @click="resetQuiz()" 
                        class="p-2 rounded-xl text-slate-400 hover:text-purple-600 hover:bg-purple-50 border border-slate-200 transition shadow-2xs cursor-pointer" 
                        title="Ulangi Jawaban Kuis">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>
            </div>
        </div>

        <!-- Questions List -->
        <div class="space-y-6">
            @forelse($quizData as $qIdx => $q)
            <div class="p-5 sm:p-6 rounded-2xl border transition-all"
                 :class="userAnswers[{{ $qIdx }}] !== undefined ? 'bg-slate-50/70 border-slate-200' : 'bg-white border-slate-200 hover:border-purple-300 shadow-xs'">
                
                <!-- Question Title -->
                <div class="flex items-start gap-3">
                    <span class="w-7 h-7 rounded-xl bg-purple-100 text-purple-800 font-black text-xs flex items-center justify-center shrink-0 mt-0.5">
                        {{ $qIdx + 1 }}
                    </span>
                    <div class="space-y-3 flex-1">
                        <p class="text-sm font-black text-slate-900 leading-snug">
                            {{ $q['question'] }}
                        </p>

                        <!-- 4 Options -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 pt-1">
                            @foreach($q['options'] as $optIdx => $optText)
                            <button type="button"
                                    @click="selectAnswer({{ $qIdx }}, {{ $optIdx }}, {{ $optIdx === $q['correct'] ? 'true' : 'false' }})"
                                    class="p-3.5 rounded-xl border text-left text-xs font-bold transition flex items-center justify-between gap-2 cursor-pointer"
                                    :class="
                                        userAnswers[{{ $qIdx }}] === undefined
                                            ? 'bg-slate-50 hover:bg-purple-50 hover:border-purple-300 text-slate-700 border-slate-200'
                                            : ({{ $optIdx }} === {{ $q['correct'] }}
                                                ? 'bg-emerald-50 text-emerald-800 border-emerald-300 ring-2 ring-emerald-100'
                                                : (userAnswers[{{ $qIdx }}] === {{ $optIdx }}
                                                    ? 'bg-rose-50 text-rose-800 border-rose-300'
                                                    : 'bg-slate-50/50 text-slate-400 border-slate-200 opacity-60'))
                                    ">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-5 h-5 rounded-lg text-[10px] font-black flex items-center justify-center shrink-0"
                                          :class="
                                            userAnswers[{{ $qIdx }}] === undefined
                                                ? 'bg-white text-slate-600 border border-slate-200'
                                                : ({{ $optIdx }} === {{ $q['correct'] }}
                                                    ? 'bg-emerald-600 text-white'
                                                    : (userAnswers[{{ $qIdx }}] === {{ $optIdx }}
                                                        ? 'bg-rose-600 text-white'
                                                        : 'bg-white text-slate-400'))
                                          ">
                                        {{ chr(65 + $optIdx) }}
                                    </span>
                                    <span class="truncate">{{ $optText }}</span>
                                </div>
                                <template x-if="userAnswers[{{ $qIdx }}] !== undefined && {{ $optIdx }} === {{ $q['correct'] }}">
                                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                                </template>
                            </button>
                            @endforeach
                        </div>

                        <!-- Explanation Box (Appears after selecting answer) -->
                        <div x-show="userAnswers[{{ $qIdx }}] !== undefined"
                             x-transition
                             class="mt-3 p-3.5 rounded-xl bg-purple-50/70 border border-purple-200/80 text-xs space-y-1">
                            <p class="font-black text-purple-900 flex items-center gap-1.5">
                                <span>💡 Penjelasan AI:</span>
                            </p>
                            <p class="text-slate-700 font-medium leading-relaxed">
                                {{ $q['explanation'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="p-8 text-center text-slate-400 text-xs">
                Tidak ada data kuis untuk materi ini.
            </div>
            @endforelse
        </div>
    </div>
    @endif

    <!-- ================= MODE 2: FLASHCARD 3D ================= -->
    @if($activeMode === 'flashcards')
    @php
        $cardsData = $currentMaterial->data['flashcards'] ?? [];
        $totalCards = count($cardsData);
    @endphp
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-7 shadow-card space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span class="p-1 rounded-lg bg-purple-50 text-purple-600">
                        <i data-lucide="layers" class="w-4 h-4"></i>
                    </span>
                    <span>Kartu Flashcard Digital (Active Recall)</span>
                </h3>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Klik kartu untuk membalik dan melihat jawaban/penjelasan konsep penting.</p>
            </div>

            <!-- Mastered Counter -->
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-600">Dikuasai:</span>
                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-black"
                      x-text="masteredCards.length + ' / {{ $totalCards }}'"></span>
            </div>
        </div>

        <!-- 3D Flip Flashcard Display Area -->
        <div class="max-w-xl mx-auto my-4">
            @foreach($cardsData as $cIdx => $c)
            <div x-show="currentCard === {{ $cIdx }}" class="space-y-4">
                <!-- Interactive 3D Card Container -->
                <div @click="flipCard()" 
                     class="min-h-[260px] sm:min-h-[290px] w-full rounded-3xl p-7 text-center flex flex-col justify-between cursor-pointer transition-all duration-300 transform select-none shadow-card hover:shadow-xl border"
                     :class="cardFlipped 
                        ? 'bg-gradient-to-br from-purple-50 via-pink-50 to-white border-purple-300 scale-[1.01]' 
                        : 'bg-gradient-to-br from-white via-slate-50/50 to-purple-50/30 border-slate-200 hover:border-purple-300'">
                    
                    <!-- Top Bar of Card -->
                    <div class="flex items-center justify-between text-xs">
                        <span class="px-2.5 py-0.5 rounded-md font-black uppercase tracking-wider text-[10px] bg-white border border-slate-200 text-purple-700 shadow-2xs">
                            {{ $c['badge'] ?? 'Konsep' }}
                        </span>
                        <span class="text-[11px] font-bold text-slate-400">
                            Kartu {{ $cIdx + 1 }} dari {{ $totalCards }}
                        </span>
                    </div>

                    <!-- Center Body -->
                    <div class="my-auto py-6">
                        <template x-if="!cardFlipped">
                            <div>
                                <p class="text-[11px] font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600 uppercase tracking-widest mb-2">Pertanyaan / Istilah:</p>
                                <h4 class="text-xl sm:text-2xl font-black text-slate-900 leading-snug">
                                    {{ $c['front'] }}
                                </h4>
                            </div>
                        </template>

                        <template x-if="cardFlipped">
                            <div>
                                <p class="text-[11px] font-black text-purple-700 uppercase tracking-widest mb-2">Definisi / Penjelasan:</p>
                                <p class="text-sm sm:text-base font-bold text-slate-800 leading-relaxed max-w-md mx-auto">
                                    {{ $c['back'] }}
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- Bottom Hint -->
                    <div class="text-[11px] font-bold text-slate-400 flex items-center justify-center gap-1.5">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5 text-purple-500"></i>
                        <span x-text="cardFlipped ? 'Klik untuk membalik ke pertanyaan' : 'Klik kartu untuk membalik melihat jawaban'"></span>
                    </div>
                </div>

                <!-- Bottom Controls (Next, Prev, Mastered) -->
                <div class="flex items-center justify-between gap-3 pt-2">
                    <button type="button" 
                            @click="cardFlipped = false; currentCard = Math.max(0, currentCard - 1)"
                            :disabled="currentCard === 0"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 text-xs font-bold text-slate-700 flex items-center gap-1.5 transition">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        <span>Sebelumnya</span>
                    </button>

                    <button type="button"
                            @click="toggleMastered({{ $cIdx }})"
                            class="px-4 py-2.5 rounded-xl border text-xs font-black transition flex items-center gap-1.5"
                            :class="masteredCards.includes({{ $cIdx }}) 
                                ? 'bg-emerald-50 text-emerald-700 border-emerald-300' 
                                : 'bg-white text-slate-600 border-slate-200 hover:border-emerald-300'">
                        <i data-lucide="star" class="w-4 h-4" :class="masteredCards.includes({{ $cIdx }}) ? 'fill-emerald-500 text-emerald-500' : ''"></i>
                        <span x-text="masteredCards.includes({{ $cIdx }}) ? 'Sudah Paham' : 'Tandai Paham'"></span>
                    </button>

                    <button type="button" 
                            @click="cardFlipped = false; currentCard = Math.min({{ $totalCards - 1 }}, currentCard + 1)"
                            :disabled="currentCard === {{ $totalCards - 1 }}"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 text-xs font-bold text-slate-700 flex items-center gap-1.5 transition">
                        <span>Selanjutnya</span>
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- ================= MODE 3: RANGKUMAN CATATAN (SMART SUMMARY) ================= -->
    @if($activeMode === 'summary')
    @php
        $sumData = $currentMaterial->data['summary'] ?? [];
        $takeaways = $sumData['key_takeaways'] ?? [];
        $cheatSheet = $sumData['cheat_sheet'] ?? [];
        $examTips = $sumData['exam_tips'] ?? [];
    @endphp
    <div class="bg-white border border-slate-200/80 rounded-3xl p-6 sm:p-7 shadow-card space-y-6">
        <!-- Summary Action Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
                    <span class="p-1 rounded-lg bg-fuchsia-50 text-fuchsia-600">
                        <i data-lucide="file-text" class="w-4 h-4"></i>
                    </span>
                    <span>Rangkuman Eksekutif & Cheat Sheet Ujian</span>
                </h3>
                <p class="text-xs font-semibold text-slate-500 mt-0.5">Catatan cerdas terstruktur yang siap dibaca kilat atau dicetak sebelum ujian.</p>
            </div>

            <!-- Export Buttons -->
            <div class="flex items-center gap-2.5">
                <button type="button" @click="copySummaryText()" 
                        class="px-4 py-2 rounded-xl border border-purple-200 text-purple-700 bg-white hover:bg-purple-50 text-xs font-bold flex items-center gap-1.5 transition shadow-2xs">
                    <i data-lucide="copy" class="w-3.5 h-3.5 text-purple-500"></i>
                    <span>Salin Teks</span>
                </button>
            </div>
        </div>

        <!-- Printable Content Container -->
        <div id="summary-content" class="space-y-6">
            <!-- 1. Executive Summary Box -->
            <div class="bg-gradient-to-r from-pink-50/80 via-purple-50/60 to-pink-50/40 border border-purple-200/80 rounded-2xl p-5 sm:p-6 space-y-2">
                <span class="text-[10px] font-black text-purple-900 uppercase tracking-wider block">Executive Summary</span>
                <p class="text-xs sm:text-sm text-slate-800 font-semibold leading-relaxed">
                    {{ $sumData['executive_summary'] ?? 'Ringkasan materi presentasi perkuliahan...' }}
                </p>
            </div>

            <!-- 2. Poin-Poin Kunci & Mindmap Konsep -->
            <div class="space-y-3">
                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
                    <i data-lucide="check-square" class="w-4 h-4 text-purple-600"></i>
                    <span>Poin-Poin Kunci Yang Wajib Dikuasai</span>
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($takeaways as $tIdx => $t)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex items-start gap-3">
                        <span class="w-6 h-6 rounded-lg bg-purple-100 text-purple-700 font-black text-xs flex items-center justify-center shrink-0 mt-0.5">
                            {{ $tIdx + 1 }}
                        </span>
                        <p class="text-xs text-slate-700 font-semibold leading-relaxed">
                            {{ $t }}
                        </p>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- 3. Tabel Cheat Sheet Lengkap -->
            @if(count($cheatSheet) > 0)
            <div class="space-y-3 pt-2">
                <h4 class="text-xs font-black text-slate-900 uppercase tracking-wide flex items-center gap-2">
                    <i data-lucide="table" class="w-4 h-4 text-pink-600"></i>
                    <span>Tabel Referensi Cepat (Cheat Sheet)</span>
                </h4>

                <div class="overflow-x-auto rounded-2xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-black uppercase text-[10px]">
                            <tr>
                                <th class="py-3 px-4">Komponen / Topik</th>
                                <th class="py-3 px-4">Kategori / Unit</th>
                                <th class="py-3 px-4">Spesifikasi / Karakteristik</th>
                                <th class="py-3 px-4">Relevansi & Penerapan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @foreach($cheatSheet as $row)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3 px-4 font-bold text-slate-900">{{ $row['layer'] }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 font-bold text-[11px] border border-purple-200/70">
                                        {{ $row['unit'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-600 font-mono text-[11px]">{{ $row['protocols'] }}</td>
                                <td class="py-3 px-4 text-slate-500">{{ $row['hardware'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- 4. Spotlight Soal Ujian -->
            @if(count($examTips) > 0)
            <div class="bg-amber-50/70 border border-amber-200 rounded-2xl p-5 space-y-2">
                <div class="flex items-center gap-2 text-amber-800 text-xs font-black uppercase tracking-wider">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                    <span>Spotlight Prediksi Soal Ujian & Jebakan Konsep:</span>
                </div>
                <div class="space-y-1.5 pl-6 text-xs text-amber-950 font-semibold list-disc">
                    @foreach($examTips as $tip)
                    <p>• {{ $tip }}</p>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- ================= MODAL UPLOAD PPT & GENERATE ================= -->
    <div x-cloak x-show="uploadModal" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl border border-slate-100 overflow-hidden"
             @click.away="if (!isProcessing) uploadModal = false">
            
            <form action="{{ route('ai-generator.process') }}" method="POST" enctype="multipart/form-data"
                  @submit="isProcessing = true">
                @csrf

                <!-- Modal Header -->
                <div class="px-7 py-5 bg-gradient-to-r from-pink-50/90 via-purple-50/70 to-white border-b border-purple-100 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-xl bg-gradient-to-tr from-pink-500 to-purple-600 text-white text-xs shadow-xs">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </span>
                            <h3 class="text-lg font-black text-slate-900">Upload Slide & Generate AI Study</h3>
                        </div>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">Unggah file PPT/PDF dan pilih mode output pembelajaran</p>
                    </div>
                    <button type="button" @click="uploadModal = false" :disabled="isProcessing" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-white transition shadow-xs">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-7 space-y-5">
                    <!-- Dropzone File Upload -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider">File Presentasi Kuliah (.pptx, .ppt, .pdf) *</label>
                        <div class="relative border-2 border-dashed border-purple-300 hover:border-purple-500 bg-purple-50/30 hover:bg-purple-50/60 rounded-2xl p-6 text-center transition cursor-pointer group">
                            <input type="file" 
                                   name="presentation_file" 
                                   accept=".ppt,.pptx,.pdf,.doc,.docx" 
                                   class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                                   @change="if ($el.files[0]) selectedTopic = $el.files[0].name">
                            <div class="space-y-2 pointer-events-none">
                                <div class="w-12 h-12 rounded-2xl bg-white border border-purple-200 text-purple-600 flex items-center justify-center mx-auto shadow-xs group-hover:scale-110 transition-transform">
                                    <i data-lucide="upload-cloud" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-800" x-text="selectedTopic || 'Klik atau tarik file PPT ke sini'"></p>
                                    <p class="text-[11px] text-slate-400 font-medium mt-0.5">Mendukung format PowerPoint (.pptx) & PDF kuliah hingga 50MB</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Or Pick From Preloaded Academic Topics -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Judul Slide / Topik Pembelajaran:</label>
                        <input type="text" 
                               name="title" 
                               x-model="selectedTopic" 
                               class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition mb-3"
                               placeholder="Contoh: Kecerdasan Buatan & Machine Learning">

                        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-2">Atau Pilih Sampel Slide Siap Pakai:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs font-bold">
                            <button type="button" 
                                    @click="selectedTopic = 'Kecerdasan Buatan: Dasar Machine Learning & Deep Learning'"
                                    class="p-2.5 rounded-xl border text-left flex items-center gap-2 transition cursor-pointer"
                                    :class="selectedTopic && selectedTopic.includes('Machine Learning') ? 'bg-purple-50 text-purple-900 border-purple-300' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                                <span>🤖</span>
                                <span class="truncate">Dasar Machine Learning</span>
                            </button>
                            <button type="button" 
                                    @click="selectedTopic = 'Struktur Data & Algoritma: Graph & Tree Traversal'"
                                    class="p-2.5 rounded-xl border text-left flex items-center gap-2 transition cursor-pointer"
                                    :class="selectedTopic && selectedTopic.includes('Struktur Data') ? 'bg-purple-50 text-purple-900 border-purple-300' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'">
                                <span>🌲</span>
                                <span class="truncate">Graph & Tree Traversal</span>
                            </button>
                        </div>
                    </div>

                    <!-- Target Generation Mode (3 Options) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider">Pilih Bentuk Output Pembelajaran *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5 text-xs">
                            <label class="p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition"
                                   :class="selectedMode === 'quiz' ? 'bg-gradient-to-r from-pink-50 to-purple-50 border-purple-400 text-purple-900 font-bold' : 'bg-slate-50 text-slate-600 border-slate-200'">
                                <input type="radio" name="mode" value="quiz" x-model="selectedMode" class="text-purple-600 focus:ring-purple-400">
                                <div>
                                    <p class="font-black">1. Kuis Interaktif</p>
                                    <p class="text-[10px] text-slate-400">Pilihan ganda + evaluasi</p>
                                </div>
                            </label>

                            <label class="p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition"
                                   :class="selectedMode === 'flashcards' ? 'bg-gradient-to-r from-pink-50 to-purple-50 border-purple-400 text-purple-900 font-bold' : 'bg-slate-50 text-slate-600 border-slate-200'">
                                <input type="radio" name="mode" value="flashcards" x-model="selectedMode" class="text-purple-600 focus:ring-purple-400">
                                <div>
                                    <p class="font-black">2. Flashcard 3D</p>
                                    <p class="text-[10px] text-slate-400">Kartu bolak-balik konsep</p>
                                </div>
                            </label>

                            <label class="p-3 rounded-xl border flex items-center gap-2.5 cursor-pointer transition"
                                   :class="selectedMode === 'summary' ? 'bg-gradient-to-r from-pink-50 to-purple-50 border-purple-400 text-purple-900 font-bold' : 'bg-slate-50 text-slate-600 border-slate-200'">
                                <input type="radio" name="mode" value="summary" x-model="selectedMode" class="text-purple-600 focus:ring-purple-400">
                                <div>
                                    <p class="font-black">3. Rangkuman</p>
                                    <p class="text-[10px] text-slate-400">Cheat sheet & mindmap</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- AI Processing Simulator Indicator -->
                    <div x-cloak x-show="isProcessing" style="display: none;" class="p-4 rounded-2xl bg-purple-50 border border-purple-200 text-center space-y-2">
                        <div class="flex items-center justify-center gap-2 text-purple-700 text-xs font-black">
                            <i data-lucide="loader" class="w-4 h-4 animate-spin"></i>
                            <span>AI sedang membedah slide & menyusun materi...</span>
                        </div>
                        <div class="w-full bg-purple-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-gradient-to-r from-pink-500 to-purple-600 h-full animate-pulse"></div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-7 py-4.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="uploadModal = false" :disabled="isProcessing"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="isProcessing"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-black text-xs shadow-glow transition hover:scale-[1.02] flex items-center gap-2 cursor-pointer disabled:opacity-60">
                        <span>Ekstrak & Generate Materi</span>
                        <span>✨</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

