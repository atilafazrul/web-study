@extends('layouts.app')

@section('title', 'SmartStudy - Dashboard')

@section('content')
<div class="p-8 space-y-6 max-w-7xl mx-auto"
     x-data="{
        openNotifications: false,
        pomodoroOpen: false,
        pomodoroMode: 'focus',
        pomodoroTime: 25 * 60,
        pomodoroInitialTime: 25 * 60,
        pomodoroTimer: null,
        pomodoroRunning: false,
        ambientSoundActive: false,
        selectedFocusTask: '{{ $aiAnalysis['task'] ? addslashes($aiAnalysis['task']->title) : 'OOP Java Assignment' }}',
        completedSessions: {{ $stats['completed'] }},
        userXp: {{ 200 + ($stats['completed'] * 50) }},
        userLevel: {{ max(1, (int)((200 + ($stats['completed'] * 50)) / 100)) }},
        get currentDayIdx() {
            const day = new Date().getDay();
            return day === 0 ? 6 : day - 1;
        },
        get userStreak() {
            return this.currentDayIdx + 1;
        },
        quoteIdx: 0,
        quotes: [
            { text: 'Konsistensi 25 menit setiap hari mengalahkan belajar 10 jam semalam sebelum ujian. Kamu pasti bisa!', author: 'SmartStudy AI Wisdom 💡' },
            { text: 'Setiap subtask yang kamu selesaikan adalah investasi nyata untuk masa depanmu. Tetap fokus!', author: 'Academic Momentum 🚀' },
            { text: 'Kesulitan saat belajar adalah tanda bahwa otakmu sedang berkembang. Jangan menyerah sekarang!', author: 'Neuroscience Insight 🧠' },
            { text: 'Rasa lelah itu sementara, tapi rasa bangga saat meraih nilai A+ itu selamanya!', author: 'Study Motivation ✨' },
            { text: 'Fokus pada satu hal dalam satu waktu. Mulai sesi Pomodoro dan buktikan kemampuanmu!', author: 'Productivity Mindset ⏱️' }
        ],
        openPomodoro() {
            this.pomodoroOpen = true;
            if (!this.pomodoroRunning) {
                this.startPomodoro();
            }
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },
        closePomodoro() {
            this.pomodoroOpen = false;
        },
        startPomodoro() {
            if (this.pomodoroRunning) return;
            this.pomodoroRunning = true;
            this.pomodoroTimer = setInterval(() => {
                if (this.pomodoroTime > 0) {
                    this.pomodoroTime--;
                } else {
                    this.finishPomodoro();
                }
            }, 1000);
        },
        pausePomodoro() {
            this.pomodoroRunning = false;
            if (this.pomodoroTimer) clearInterval(this.pomodoroTimer);
        },
        resetPomodoro() {
            this.pausePomodoro();
            this.pomodoroTime = this.pomodoroInitialTime;
        },
        setPomodoroMode(mode) {
            this.pausePomodoro();
            this.pomodoroMode = mode;
            if (mode === 'focus') {
                this.pomodoroTime = 25 * 60;
                this.pomodoroInitialTime = 25 * 60;
            } else if (mode === 'short') {
                this.pomodoroTime = 5 * 60;
                this.pomodoroInitialTime = 5 * 60;
            } else if (mode === 'long') {
                this.pomodoroTime = 15 * 60;
                this.pomodoroInitialTime = 15 * 60;
            }
        },
        finishPomodoro() {
            this.pausePomodoro();
            this.completedSessions++;
            this.userXp += 50;
            if (window.playVictoryChime) window.playVictoryChime();
            if (window.celebrateConfetti) window.celebrateConfetti();
            if (window.showToast) {
                window.showToast('🎉 Luar biasa! 1 Sesi fokus selesai (+50 XP). Otakmu semakin tajam!');
            }
            this.setPomodoroMode('short');
        },
        toggleAmbientSound() {
            this.ambientSoundActive = !this.ambientSoundActive;
            if (window.toggleAmbientFocusSound) {
                window.toggleAmbientFocusSound(this.ambientSoundActive);
            }
        },
        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            return (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
        },
        get progressPercent() {
            return Math.round(((this.pomodoroInitialTime - this.pomodoroTime) / this.pomodoroInitialTime) * 100);
        },
        get timeGreeting() {
            const h = new Date().getHours();
            if (h >= 4 && h < 11) return 'Good morning';
            if (h >= 11 && h < 15) return 'Good afternoon';
            if (h >= 15 && h < 18) return 'Good afternoon';
            return 'Good evening';
        },
        get timeSubtitle() {
            const h = new Date().getHours();
            if (h >= 4 && h < 11) return 'Selamat pagi! Awali hari dengan fokus & rencana belajar cerdas.';
            if (h >= 11 && h < 15) return 'Selamat siang! Tetap berenergi menuntaskan target hari ini.';
            if (h >= 15 && h < 18) return 'Selamat sore! Waktu tepat untuk review materi & tugas.';
            return 'Selamat malam! Waktu tenang untuk fokus & belajar optimal.';
        },
        nextQuote() {
            this.quoteIdx = (this.quoteIdx + 1) % this.quotes.length;
            if (window.celebrateConfetti) window.celebrateConfetti();
        }
     }">

    <!-- ================= TOP HEADER BANNER ================= -->
    <div class="relative z-30 bg-gradient-to-r from-pink-50/95 via-purple-50/75 to-pink-50/85 border border-pink-200/80 rounded-3xl p-6 sm:p-7 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-card">
        
        <!-- Ambient Glowing Background Elements (Clipped inside inner container) -->
        <div class="absolute inset-0 overflow-hidden rounded-3xl pointer-events-none">
            <div class="absolute -top-12 -left-12 w-48 h-48 bg-pink-300/30 rounded-full blur-2xl animate-pulse-glow"></div>
            <div class="absolute -bottom-10 right-1/4 w-60 h-36 bg-purple-300/30 rounded-full blur-2xl animate-pulse-glow" style="animation-delay: 2s;"></div>
            <div class="absolute inset-0 w-1/3 h-full bg-gradient-to-r from-transparent via-white/50 to-transparent animate-light-sweep"></div>
        </div>

        <!-- LEFT: Greetings & Assistant Status -->
        <div class="relative z-10 max-w-xl">
            <!-- Active AI Badge (Click to open chat & ask today's schedule) -->
            <button type="button" 
                    @click="window.openAiChat('Ada jadwal apa hari ini?')"
                    class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-white/95 hover:bg-white border border-purple-200/80 hover:border-purple-300 shadow-2xs hover:shadow-soft backdrop-blur-xs text-[11px] font-bold text-purple-900 mb-2.5 transition-all hover:scale-105 active:scale-95 cursor-pointer group">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-gradient-to-r from-pink-500 to-purple-600"></span>
                </span>
                <span class="tracking-wide">Smart Academic Assistant Active</span>
                <span class="text-pink-500 group-hover:rotate-12 transition-transform">✨</span>
                <span class="hidden sm:inline-block text-[10px] px-2 py-0.5 rounded-full bg-pink-50 text-pink-600 border border-pink-200/60 font-black">Tanya Jadwal</span>
            </button>

            <!-- Main Heading with Dynamic Time Greeting -->
            <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                <span x-text="timeGreeting + ', {{ $currentUser->name ?? 'Student' }}!'">{{ $timeGreeting }}, {{ $currentUser->name ?? 'Student' }}!</span>
            </h1>

            <!-- Subtitle & Quick Status Pill -->
            <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 mt-2 text-xs font-semibold text-slate-500">
                <span class="flex items-center gap-1.5 text-slate-600 font-bold">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-pink-500 animate-pulse"></i>
                    <span x-text="timeSubtitle">{{ $timeSubtitle }}</span>
                </span>
                <span class="hidden sm:inline-block w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg bg-pink-100/70 border border-pink-200/70 text-purple-900 text-[11px] font-bold shadow-2xs">
                    <i data-lucide="calendar-clock" class="w-3.5 h-3.5 text-purple-600"></i>
                    <span>{{ $stats['due_this_week'] }} tugas pekan ini</span>
                </span>
            </div>

            <!-- Quick Stats in Header -->
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white/90 border border-purple-200/80 shadow-2xs text-xs font-black text-slate-800">
                    <span class="text-base animate-bounce">🔥</span>
                    <span class="text-orange-600" x-text="userStreak + ' Hari Streak'">{{ $userStreak }} Hari Streak</span>
                </div>
            </div>
        </div>

        <!-- CENTER: Clean Animated Academic Illustration (Full 3D Open Book with Layers & Outlines) -->
        <div class="hidden lg:flex items-center justify-center relative z-10 shrink-0 pointer-events-none select-none">
            <div class="relative w-48 h-26 flex items-center justify-center">
                <!-- Floating Soft Pink-Purple Aura -->
                <div class="absolute inset-0 bg-gradient-to-tr from-pink-200/50 to-purple-200/50 rounded-full blur-xl animate-pulse-glow"></div>
                
                <svg viewBox="0 0 170 100" class="w-full h-full drop-shadow-md overflow-visible">
                    <defs>
                        <!-- Book Cover Gradient (Pink to Purple) -->
                        <linearGradient id="bookCoverGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#A855F7" />
                            <stop offset="50%" stop-color="#C026D3" />
                            <stop offset="100%" stop-color="#EC4899" />
                        </linearGradient>
                        <!-- Left & Right Page Gradients (Soft White/Pastel Tints) -->
                        <linearGradient id="pageLeftGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FDF2F8" />
                            <stop offset="100%" stop-color="#FFFFFF" />
                        </linearGradient>
                        <linearGradient id="pageRightGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#FFFFFF" />
                            <stop offset="100%" stop-color="#FAF5FF" />
                        </linearGradient>
                        <!-- Bookmark Ribbon Gradient -->
                        <linearGradient id="ribbonGrad" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" stop-color="#EC4899" />
                            <stop offset="100%" stop-color="#BE185D" />
                        </linearGradient>
                    </defs>

                    <!-- Background Sparkles / Stars -->
                    <path class="animate-star-1" d="M18 24 L20 28 L24 30 L20 32 L18 36 L16 32 L12 30 L16 28 Z" fill="#EC4899"/>
                    <path class="animate-star-2" d="M152 16 L154 20 L158 21 L154 22 L152 26 L150 22 L146 21 L150 20 Z" fill="#F59E0B"/>
                    <circle cx="26" cy="62" r="2" fill="#A855F7" opacity="0.75"/>
                    <circle cx="146" cy="54" r="1.8" fill="#EC4899" opacity="0.8"/>

                    <!-- Complete Floating Open Book -->
                    <g class="animate-float">
                        <!-- Soft Cast Shadow Under Book -->
                        <ellipse cx="85" cy="84" rx="46" ry="7" fill="#701A75" opacity="0.14"/>

                        <!-- 1. Hard Cover / Binding Layer (Complete Base with clear outlines) -->
                        <path d="M28 66 C48 59 72 62 85 68 C98 62 122 59 142 66 L142 41 C122 34 98 37 85 43 C72 37 48 34 28 41 Z" 
                              fill="url(#bookCoverGrad)" stroke="#701A75" stroke-width="1.8" stroke-linejoin="round"/>
                        
                        <!-- 2. Stacked Pages Thickness (Shows page layers along bottom & sides) -->
                        <path d="M29 64 C48 58 72 61 84 66 L84 63 C72 58 48 55 29 61 Z" fill="#E9D5FF" stroke="#C084FC" stroke-width="0.8"/>
                        <path d="M86 66 C98 61 122 58 141 64 L141 61 C122 55 98 58 86 63 Z" fill="#FCE7F3" stroke="#F472B6" stroke-width="0.8"/>

                        <!-- 3. Left Page (Clear border, curved top, soft gradient fill) -->
                        <path d="M30 39 C50 32 72 35 84 40 L84 63 C72 58 50 55 30 62 Z" 
                              fill="url(#pageLeftGrad)" stroke="#D8B4FE" stroke-width="1.5" stroke-linejoin="round"/>
                        <!-- Text lines on left page -->
                        <line x1="38" y1="45" x2="74" y2="43" stroke="#C084FC" stroke-width="2" stroke-linecap="round"/>
                        <line x1="38" y1="50" x2="68" y2="48" stroke="#E879F9" stroke-width="2" stroke-linecap="round"/>
                        <line x1="38" y1="55" x2="64" y2="53" stroke="#D8B4FE" stroke-width="2" stroke-linecap="round"/>

                        <!-- 4. Right Page (Clear border, curved top, soft gradient fill) -->
                        <path d="M86 40 C98 35 120 32 140 39 L140 62 C120 55 98 58 86 63 Z" 
                              fill="url(#pageRightGrad)" stroke="#F472B6" stroke-width="1.5" stroke-linejoin="round"/>
                        <!-- Text lines on right page -->
                        <line x1="96" y1="43" x2="132" y2="45" stroke="#F472B6" stroke-width="2" stroke-linecap="round"/>
                        <line x1="96" y1="48" x2="126" y2="50" stroke="#FB7185" stroke-width="2" stroke-linecap="round"/>
                        <line x1="96" y1="53" x2="120" y2="55" stroke="#FBCFE8" stroke-width="2" stroke-linecap="round"/>

                        <!-- 5. Center Spine Groove & Book Crease -->
                        <path d="M84 40 C85 41 85 62 85 64" stroke="#9333EA" stroke-width="2" stroke-linecap="round"/>
                        <line x1="85" y1="40" x2="85" y2="64" stroke="#701A75" stroke-width="1"/>

                        <!-- 6. Pink Silk Bookmark Ribbon (Dangling from top spine down past bottom) -->
                        <path d="M83 39 Q85 35 87 39 L87 72 L85 68 L83 72 Z" fill="url(#ribbonGrad)" stroke="#9F1239" stroke-width="0.8"/>
                    </g>

                    <!-- Floating Mini Graduation Cap (Golden Tassel) -->
                    <g class="animate-float-reverse">
                        <polygon points="120,20 135,14 150,20 135,26" fill="#1E1B4B" stroke="#312E81" stroke-width="0.8"/>
                        <polygon points="128,24 128,28 142,28 142,24" fill="#312E81"/>
                        <!-- Golden Tassel hanging down -->
                        <line x1="135" y1="20" x2="146" y2="26" stroke="#F59E0B" stroke-width="1.4"/>
                        <circle cx="146" cy="27" r="1.6" fill="#F59E0B"/>
                    </g>
                </svg>
            </div>
        </div>

        <!-- RIGHT: Interactive Actions (Bell Dropdown & Profile Pill) -->
        <div class="relative z-20 flex items-center gap-3 shrink-0">
            <!-- Notification Bell with Alpine Dropdown -->
            <div class="relative">
                <button @click="openNotifications = !openNotifications"
                        type="button"
                        class="relative w-11 h-11 rounded-2xl bg-white border border-purple-200/80 flex items-center justify-center text-slate-600 hover:text-purple-600 hover:border-pink-300 hover:shadow-md transition-all shadow-xs group cursor-pointer"
                        title="Notifikasi Akademik">
                    <i data-lucide="bell" class="w-5 h-5 bell-icon transition-transform"></i>
                    
                    <!-- Notification Ping Dot -->
                    <span class="absolute top-2.5 right-2.5 flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-pink-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-pink-500 ring-2 ring-white"></span>
                    </span>
                </button>

                <!-- Floating Notification Dropdown -->
                <div x-show="openNotifications"
                     x-cloak
                     style="display: none;"
                     @click.outside="openNotifications = false"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                     class="absolute right-0 mt-3 w-80 sm:w-88 bg-white border border-purple-100 rounded-2xl shadow-xl p-4 z-50 space-y-3">
                    
                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-purple-900">Notifikasi Belajar</span>
                            <span class="px-2 py-0.5 rounded-full bg-pink-50 text-pink-700 text-[10px] font-bold border border-pink-200">3 Baru</span>
                        </div>
                        <button @click="openNotifications = false" class="text-slate-400 hover:text-slate-600 text-xs">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>

                    <div class="space-y-2 text-xs">
                        <!-- Alert 1 -->
                        <div class="p-2.5 rounded-xl bg-pink-50/70 border border-pink-200/70 flex items-start gap-2.5">
                            <div class="p-1 rounded-lg bg-pink-100 text-pink-700 shrink-0 mt-0.5">
                                <i data-lucide="alert-circle" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800">Deadline Segera Tiba!</p>
                                <p class="text-[11px] text-slate-600">OOP Java Assignment dijadwalkan besok.</p>
                            </div>
                        </div>

                        <!-- Alert 2 -->
                        <div class="p-2.5 rounded-xl bg-purple-50/70 border border-purple-200/70 flex items-start gap-2.5">
                            <div class="p-1 rounded-lg bg-purple-100 text-purple-700 shrink-0 mt-0.5">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800">AI Scheduler Diperbarui</p>
                                <p class="text-[11px] text-slate-600">Jadwal belajar malam ini telah diatur pukul 19:00.</p>
                            </div>
                        </div>

                        <!-- Alert 3 -->
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 flex items-start gap-2.5">
                            <div class="p-1 rounded-lg bg-emerald-100 text-emerald-700 shrink-0 mt-0.5">
                                <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-800">Pencapaian Pekan Ini</p>
                                <p class="text-[11px] text-slate-600">3 tugas berhasil diselesaikan tepat waktu! 🎉</p>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('tasks') }}" class="block text-center text-[11px] font-bold text-purple-600 hover:text-pink-600 pt-1 transition">
                        Lihat Semua Tugas &rarr;
                    </a>
                </div>
            </div>

            <!-- User Avatar & Profile Pill -->
            <a href="{{ route('settings') }}" title="Ke Pengaturan Profil" 
               class="flex items-center gap-2.5 p-1 pr-3.5 rounded-2xl bg-white border border-purple-200/80 hover:border-pink-300 hover:shadow-md transition-all shadow-xs group">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-pink-500 via-fuchsia-500 to-purple-600 text-white font-black flex items-center justify-center text-sm shadow-sm ring-2 ring-pink-200/70 group-hover:scale-105 transition-transform uppercase">
                    {{ strtoupper(substr($currentUser->name ?? 'S', 0, 1)) }}
                </div>
                <div class="hidden sm:block text-left">
                    <p class="text-xs font-black text-slate-800 leading-tight group-hover:text-purple-700 transition">{{ $currentUser->name ?? 'Student' }}</p>
                    <p class="text-[10px] font-semibold text-pink-600 leading-none">Informatics</p>
                </div>
            </a>
        </div>
    </div>

    <!-- ================= GAMIFIED STUDY MOMENTUM & MOTIVATION STRIP ================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 relative z-10">
        
        <!-- 1. Daily Study Streak -->
        <div class="bg-gradient-to-br from-amber-50/90 via-orange-50/60 to-pink-50/80 border border-orange-200/90 rounded-3xl p-5 shadow-card relative overflow-hidden flex flex-col justify-between hover:shadow-soft hover:border-orange-300 transition-all">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-orange-300/20 rounded-full blur-xl pointer-events-none"></div>
            
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-orange-200/60">
                    <div class="flex items-center gap-2">
                        <span class="text-xl animate-bounce">🔥</span>
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wide">Daily Study Streak</h3>
                    </div>
                    <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-gradient-to-r from-orange-500 to-pink-500 text-white shadow-2xs animate-pulse">
                        ON FIRE!
                    </span>
                </div>

                <div class="mt-3.5 flex items-baseline gap-2">
                    <span class="text-3xl font-black text-slate-900 tracking-tight" x-text="userStreak">{{ $userStreak }}</span>
                    <span class="text-sm font-black text-orange-600">Hari Berturut-turut</span>
                </div>
                <p class="text-[11px] text-slate-600 font-semibold mt-1">
                    Konsistensi luar biasa! Jangan biarkan apimu padam hari ini.
                </p>
            </div>

            <!-- 7-Day Streak Dots -->
            <div class="mt-4 pt-3 border-t border-orange-200/60 flex items-center justify-between text-center">
                <template x-for="(d, idx) in ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']" :key="idx">
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-[10px] font-bold" 
                              :class="idx === currentDayIdx ? 'text-orange-600 font-black' : (idx < currentDayIdx ? 'text-slate-700' : 'text-slate-400')" 
                              x-text="d"></span>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black transition-all shadow-2xs"
                             :class="idx <= currentDayIdx 
                                 ? (idx === currentDayIdx ? 'bg-gradient-to-tr from-orange-500 via-rose-500 to-pink-500 text-white ring-2 ring-orange-300 scale-110 shadow-sm animate-pulse' : 'bg-gradient-to-tr from-orange-400 via-rose-500 to-pink-500 text-white ring-1 ring-orange-200/80') 
                                 : 'bg-white border border-slate-200 text-slate-300'">
                            <span x-show="idx <= currentDayIdx">🔥</span>
                            <span x-show="idx > currentDayIdx">·</span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 2. AI Mindset Booster / Daily Affirmation -->
        <div class="bg-gradient-to-br from-pink-50/95 via-purple-50/50 to-white border border-pink-200/90 rounded-3xl p-5 shadow-card relative overflow-hidden flex flex-col justify-between hover:shadow-soft hover:border-pink-300 transition-all">
            <div class="absolute -right-6 -bottom-6 w-28 h-28 bg-pink-300/20 rounded-full blur-xl pointer-events-none"></div>

            <div>
                <div class="flex items-center justify-between pb-3 border-b border-pink-200/60">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">💡</span>
                        <h3 class="text-xs font-black text-slate-900 uppercase tracking-wide">Daily Mindset Booster</h3>
                    </div>
                    <button type="button" @click="nextQuote()" 
                            class="text-[10px] font-black px-2.5 py-1 rounded-xl bg-white border border-pink-200 text-pink-700 hover:bg-pink-50 shadow-2xs transition flex items-center gap-1 cursor-pointer">
                        <span>✨ Ganti</span>
                    </button>
                </div>

                <div class="mt-3.5 bg-white/90 p-3.5 rounded-2xl border border-pink-100 shadow-2xs min-h-[82px] flex flex-col justify-center">
                    <p class="text-xs font-bold text-slate-800 leading-relaxed italic" x-text="'“' + quotes[quoteIdx].text + '”'"></p>
                    <p class="text-[10px] font-extrabold text-pink-600 mt-2 text-right" x-text="'— ' + quotes[quoteIdx].author"></p>
                </div>
            </div>

            <!-- Footer Note -->
            <div class="mt-4 pt-3 border-t border-pink-200/60 flex items-center justify-between text-[11px] text-slate-500 font-semibold">
                <span class="flex items-center gap-1.5 text-pink-700 font-bold">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5 text-pink-500"></i>
                    <span>Inspirasi harian belajar</span>
                </span>
                <span class="text-slate-400">Update berkala</span>
            </div>
        </div>

    </div>

    <!-- ================= 4 METRICS OVERVIEW CARDS (INTERACTIVE & CLICKABLE) ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. Total Tasks -->
        <a href="{{ route('tasks') }}" 
           class="bg-gradient-to-br from-white via-pink-50/50 to-pink-100/40 border border-pink-200/90 rounded-3xl p-5 flex items-center justify-between shadow-card hover:-translate-y-1.5 hover:border-pink-300 hover:shadow-soft transition-all duration-200 group cursor-pointer"
           title="Buka Semua Tugas (My Tasks)">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-pink-500 to-rose-500 flex items-center justify-center text-white shrink-0 shadow-md shadow-pink-500/25 group-hover:scale-110 transition-transform">
                    <i data-lucide="list-todo" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $stats['total_tasks'] }}</p>
                    <p class="text-xs font-bold text-slate-500 group-hover:text-pink-600 transition-colors">Total Tasks</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-pink-100 text-pink-700">{{ $stats['pending_tasks'] }} Aktif</span>
                <span class="text-[10px] text-pink-500 font-extrabold opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                    Lihat <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </span>
            </div>
        </a>

        <!-- 2. Due This Week (Active / Pending Tasks) -->
        <a href="{{ route('tasks', ['status' => 'pending']) }}" 
           class="bg-gradient-to-br from-white via-purple-50/50 to-purple-100/40 border border-purple-200/90 rounded-3xl p-5 flex items-center justify-between shadow-card hover:-translate-y-1.5 hover:border-purple-300 hover:shadow-soft transition-all duration-200 group cursor-pointer"
           title="Lihat Tugas Aktif / Pending">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-purple-500 to-indigo-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-purple-500/25 group-hover:scale-110 transition-transform">
                    <i data-lucide="calendar-clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $stats['due_this_week'] }}</p>
                    <p class="text-xs font-bold text-slate-500 group-hover:text-purple-600 transition-colors">Due This Week</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-purple-100 text-purple-700">
                    @if(($stats['high_priority'] ?? 0) > 0)
                        ⚡ {{ $stats['high_priority'] }} Prioritas
                    @else
                        ⚡ Terjadwal
                    @endif
                </span>
                <span class="text-[10px] text-purple-600 font-extrabold opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                    Lihat <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </span>
            </div>
        </a>

        <!-- 3. Completed (Finished Tasks) -->
        <a href="{{ route('tasks', ['status' => 'completed']) }}" 
           class="bg-gradient-to-br from-white via-emerald-50/50 to-teal-100/40 border border-emerald-200/90 rounded-3xl p-5 flex items-center justify-between shadow-card hover:-translate-y-1.5 hover:border-emerald-300 hover:shadow-soft transition-all duration-200 group cursor-pointer"
           title="Lihat Tugas Selesai (Completed)">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-emerald-500/25 group-hover:scale-110 transition-transform">
                    <i data-lucide="check-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $stats['completed'] }}</p>
                    <p class="text-xs font-bold text-slate-500 group-hover:text-emerald-700 transition-colors">Completed</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800">🎉 +{{ $stats['completed'] * 50 }} XP</span>
                <span class="text-[10px] text-emerald-700 font-extrabold opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                    Lihat <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </span>
            </div>
        </a>

        <!-- 4. Study Time This Week (Weekly Study Plan) -->
        <a href="{{ route('study-plan') }}" 
           class="bg-gradient-to-br from-white via-fuchsia-50/50 to-purple-100/40 border border-fuchsia-200/90 rounded-3xl p-5 flex items-center justify-between shadow-card hover:-translate-y-1.5 hover:border-fuchsia-300 hover:shadow-soft transition-all duration-200 group cursor-pointer"
           title="Buka Jadwal Belajar (Weekly Study Plan)">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-fuchsia-500 to-pink-600 flex items-center justify-center text-white shrink-0 shadow-md shadow-fuchsia-500/25 group-hover:scale-110 transition-transform">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $stats['study_time'] }}</p>
                    <p class="text-xs font-bold text-slate-500 group-hover:text-fuchsia-700 transition-colors">Study Time</p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-fuchsia-100 text-fuchsia-800">🔥 {{ $stats['total_tasks'] }} Modul</span>
                <span class="text-[10px] text-fuchsia-700 font-extrabold opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5">
                    Jadwal <i data-lucide="chevron-right" class="w-3 h-3"></i>
                </span>
            </div>
        </a>
    </div>

    <!-- ================= MIDDLE ROW: MY TASKS & AI ANALYSIS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT COLUMN: MY TASKS (5 cols on lg) -->
        <div class="lg:col-span-5 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-xl bg-purple-50 text-purple-600">
                            <i data-lucide="check-square" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-base font-black text-slate-900">My Tasks</h2>
                    </div>
                    <a href="{{ route('tasks') }}" class="text-xs font-extrabold text-purple-600 hover:text-purple-700 flex items-center gap-1 transition group">
                        <span>View All</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>

                <!-- Task List -->
                <div class="divide-y divide-slate-100 mt-2">
                    @forelse($tasks->take(5) as $task)
                    <div class="py-3.5 flex items-center justify-between gap-3 group hover:bg-slate-50/80 -mx-3 px-3 rounded-2xl transition"
                         x-data="{ completed: {{ $task->status === 'completed' ? 'true' : 'false' }} }">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Custom Circular Checkbox with DB Toggle -->
                            <button type="button" 
                                    @click="
                                        completed = !completed; 
                                        if (completed && window.celebrateConfetti) { window.celebrateConfetti(); }
                                        if (completed && window.playVictoryChime) { window.playVictoryChime(); }
                                        fetch('{{ route('tasks.toggle', $task->id) }}', {
                                            method: 'POST',
                                            headers: {
                                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                                'Accept': 'application/json'
                                            }
                                        }).then(() => {
                                            showToast(completed ? 'Task completed! +50 XP didapat! 🎉' : 'Task restored to pending');
                                            setTimeout(() => window.location.reload(), 350);
                                        });
                                    "
                                    class="w-5 h-5 rounded-full border-2 transition flex items-center justify-center shrink-0 cursor-pointer"
                                    :class="completed ? 'bg-gradient-to-tr from-pink-500 to-purple-600 border-purple-600 text-white' : 'border-slate-300 hover:border-pink-500 bg-white'">
                                <svg x-show="completed" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"></polyline>
                                </svg>
                            </button>

                            <!-- Priority Badge (Soft & Refined) -->
                            <span class="px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase shrink-0 
                                {{ $task->priority === 'High' ? 'bg-rose-50 text-rose-600 border border-rose-200/70' : '' }}
                                {{ $task->priority === 'Medium' ? 'bg-amber-50 text-amber-600 border border-amber-200/70' : '' }}
                                {{ $task->priority === 'Low' ? 'bg-slate-100 text-slate-600 border border-slate-200' : '' }}">
                                {{ $task->priority }}
                            </span>

                            <!-- Title and Subject -->
                            <div class="min-w-0">
                                <p class="text-xs font-bold transition-colors truncate" 
                                   :class="completed ? 'line-through text-slate-400 font-semibold decoration-slate-300' : 'text-slate-800'">
                                    {{ $task->title }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <p class="text-[11px] text-slate-400 font-medium shrink-0">
                                        {{ $task->course ?? $task->category }}
                                    </p>
                                    @if(!empty($task->description) && !str_starts_with($task->description, 'Academic assignment for'))
                                    <span class="text-[10px] text-purple-700 bg-purple-50/90 px-1.5 py-0.2 rounded border border-purple-200/70 font-semibold truncate max-w-[170px]" title="{{ $task->description }}">
                                        📝 {{ $task->description }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Date, Duration & Options -->
                        <div class="flex items-center gap-3 shrink-0 text-slate-400 text-xs font-medium">
                            <span class="flex items-center gap-1 text-[11px]">
                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                {{ $task->due_date }}
                            </span>
                            <span class="flex items-center gap-1 text-[11px]">
                                <i data-lucide="clock" class="w-3.5 h-3.5"></i>
                                {{ $task->duration }}
                            </span>
                        </div>
                    </div>
                    @empty
                    <div class="py-8 text-center text-slate-400 text-xs font-semibold">
                        Belum ada tugas. Klik tombol di bawah untuk menambah tugas baru!
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Add Button -->
            <button @click="addTaskModal = true" 
                    class="mt-4 w-full py-3 rounded-2xl border border-dashed border-purple-300 bg-purple-50/60 hover:bg-purple-100/70 text-purple-800 font-black text-xs flex items-center justify-center gap-2 transition hover:scale-[1.01] cursor-pointer">
                <i data-lucide="plus" class="w-4 h-4 text-purple-600"></i>
                <span>+ Add New Academic Task</span>
            </button>
        </div>

        <!-- RIGHT COLUMN: AI ANALYSIS CARD (7 cols on lg) -->
        <div class="lg:col-span-7 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card flex flex-col justify-between"
             x-data="{ tab: 'breakdown' }">
            
            <div>
                <!-- Header with AI Badge -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2">
                        <span class="text-purple-600 text-lg">✦</span>
                        <h2 class="text-base font-black text-slate-900">AI Analysis</h2>
                    </div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-gradient-to-r from-pink-50 to-purple-50 border border-purple-200 text-purple-800 text-xs font-bold shadow-xs">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-purple-600"></i>
                        <span>Powered by Gemini & GPT</span>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex items-center gap-2 mt-4 pb-2">
                    <button @click="tab = 'breakdown'"
                            class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="tab === 'breakdown' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                        Task Breakdown
                    </button>
                    <button @click="tab = 'insights'"
                            class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="tab === 'insights' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                        Priority Insights
                    </button>
                    <button @click="tab = 'suggestions'"
                            class="px-3.5 py-1.5 rounded-full text-xs font-bold transition cursor-pointer"
                            :class="tab === 'suggestions' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs' : 'bg-slate-50 text-slate-500 hover:bg-slate-100'">
                        Study Suggestions
                    </button>
                </div>

                <!-- Tab 1: Task Breakdown -->
                <div x-show="tab === 'breakdown'" class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-6">
                    <!-- Left: Subtasks & Classification -->
                    <div class="md:col-span-8 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-teal-600 font-bold">✦</span>
                            <h3 class="text-sm font-black text-slate-900">{{ $aiAnalysis['task'] ? $aiAnalysis['task']->title : 'No Task' }}</h3>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <span>This assignment is classified as:</span>
                            <span class="px-2.5 py-0.5 rounded-md font-black text-xs bg-purple-50 text-purple-700 border border-purple-200">
                                {{ $aiAnalysis['classification'] }}
                            </span>
                        </div>

                        <!-- Suggested Subtasks -->
                        <div class="pt-2">
                            <p class="text-xs font-black text-slate-700 mb-2.5">Suggested subtasks:</p>
                            <div class="space-y-2">
                                @foreach($aiAnalysis['subtasks'] as $index => $subtask)
                                <div class="flex items-center gap-3 text-xs text-slate-700 bg-slate-50/80 px-3.5 py-2.5 rounded-xl border border-slate-100/90 hover:bg-teal-50/40 transition">
                                    <span class="w-5 h-5 rounded-full bg-teal-600 text-white font-black flex items-center justify-center text-[10px] shrink-0 shadow-xs">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="font-semibold">{{ $subtask }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Right: Priority Score Gauge -->
                    <div class="md:col-span-4 flex flex-col items-center justify-center border-t md:border-t-0 md:border-l border-slate-100 pl-0 md:pl-6 text-center py-2">
                        <p class="text-xs font-black text-slate-500 mb-2">Priority Score</p>
                        
                        <!-- Circular Gauge SVG -->
                        <div class="relative w-28 h-28 flex items-center justify-center my-1">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                                <defs>
                                    <linearGradient id="gaugePinkPurple" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" stop-color="#EC4899" />
                                        <stop offset="100%" stop-color="#8B5CF6" />
                                    </linearGradient>
                                </defs>
                                <circle cx="50" cy="50" r="40" stroke="#F1F5F9" stroke-width="8" fill="none"/>
                                <circle cx="50" cy="50" r="40" stroke="url(#gaugePinkPurple)" stroke-width="8" stroke-linecap="round"
                                        stroke-dasharray="251.2" stroke-dashoffset="20" fill="none"/>
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center">
                                <span class="text-2xl font-black text-slate-900 tracking-tight">{{ $aiAnalysis['priority_score'] }}%</span>
                            </div>
                        </div>

                        <span class="mt-2 px-3 py-0.5 rounded-full text-xs font-black bg-rose-50 text-rose-600 border border-rose-200">
                            {{ $aiAnalysis['urgency'] }}
                        </span>
                        <p class="text-[11px] text-slate-400 font-medium mt-1.5 max-w-[130px] leading-tight">
                            {{ $aiAnalysis['urgency_note'] }}
                        </p>
                    </div>
                </div>

                <!-- Tab 2: Priority Insights -->
                <div x-cloak x-show="tab === 'insights'" style="display: none;" class="mt-4 p-4.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                    <p class="font-bold text-slate-800">Deterministically Ranked by Urgency & Workload:</p>
                    <p class="text-slate-600 leading-relaxed">
                        • <strong>OOP Java Assignment (92%):</strong> 3 hours required, due in under 24 hours. Highest cognitive load detected.
                    </p>
                    <p class="text-slate-600 leading-relaxed">
                        • <strong>Numerical Analysis Quiz Prep (88%):</strong> Math formulas require retention recall before tomorrow.
                    </p>
                </div>

                <!-- Tab 3: Study Suggestions -->
                <div x-cloak x-show="tab === 'suggestions'" style="display: none;" class="mt-4 p-4.5 rounded-2xl bg-teal-50/50 border border-teal-100 space-y-2 text-xs">
                    <p class="font-bold text-teal-900">Recommended Next Step:</p>
                    <p class="text-slate-600">
                        Work on Subtask 1 & 2 during your allocated evening slot (19:00 - 20:00). Remember to use the Pomodoro timer!
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= BOTTOM ROW: WEEKLY STUDY PLAN & STUDY TIPS ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT: WEEKLY STUDY PLAN (8 cols on lg) -->
        <div class="lg:col-span-8 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="p-1.5 rounded-xl bg-teal-50 text-teal-600">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                    </div>
                    <h2 class="text-base font-black text-slate-900">Weekly Study Plan</h2>
                </div>
                <a href="{{ route('study-plan') }}" class="text-xs font-extrabold text-teal-600 hover:text-teal-700 flex items-center gap-1 transition group">
                    <span>View Calendar</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                </a>
            </div>

            <!-- Timetable Grid -->
            <div class="overflow-x-auto mt-4">
                <table class="w-full text-center border-collapse">
                    <thead>
                        <tr>
                            <th class="p-2 text-left text-xs font-bold text-slate-400 w-36"></th>
                            @foreach($weeklySchedule['days'] as $day)
                            <th class="p-2 text-xs font-semibold text-slate-500">
                                <span class="block text-slate-400 font-medium">{{ $day['name'] }}</span>
                                <span class="block font-black text-slate-800">{{ $day['date'] }}</span>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <!-- Evening Slot Row -->
                        <tr>
                            <td class="p-3 text-left font-bold text-slate-700 bg-slate-50/50 rounded-l-2xl">
                                <div>Evening</div>
                                <div class="text-[10px] text-slate-400 font-normal">(19:00–21:00)</div>
                            </td>
                            @foreach($weeklySchedule['days'] as $day)
                            <td class="p-2">
                                <div class="p-2.5 rounded-2xl border text-[11px] font-bold leading-tight min-h-[50px] flex flex-col items-center justify-center shadow-xs transition hover:scale-[1.02] {{ $day['evening']['color'] }}">
                                    <span>{{ $day['evening']['subject'] }}</span>
                                    @if($day['evening']['duration'])
                                    <span class="text-[10px] opacity-80 font-normal">({{ $day['evening']['duration'] }})</span>
                                    @endif
                                </div>
                            </td>
                            @endforeach
                        </tr>

                        <!-- Flexible Time Row -->
                        <tr>
                            <td class="p-3 text-left font-bold text-slate-700 bg-slate-50/50 rounded-l-2xl">
                                <div>Flexible Time</div>
                            </td>
                            <td colspan="7" class="p-2">
                                <div class="py-3 px-4 rounded-2xl bg-purple-50/90 border border-purple-200 text-purple-800 text-xs font-extrabold flex items-center justify-center gap-2 shadow-xs">
                                    <span>{{ $weeklySchedule['flexible_task']['icon'] }}</span>
                                    <span>{{ $weeklySchedule['flexible_task']['title'] }}</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RIGHT: STUDY TIPS (4 cols on lg) -->
        <div class="lg:col-span-4 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="p-1.5 rounded-xl bg-amber-50 text-amber-600">
                            <i data-lucide="lightbulb" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-base font-black text-slate-900">Study Tips</h2>
                    </div>
                    <button class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                    </button>
                </div>

                <p class="text-xs text-slate-500 font-semibold mt-3">
                    Based on your current tasks, we suggest:
                </p>

                <!-- Tips List -->
                <div class="mt-3.5 space-y-3">
                    @foreach($studyTips as $tip)
                    <div class="flex items-start gap-2.5 text-xs text-slate-700 bg-slate-50/60 p-2.5 rounded-xl border border-slate-100">
                        <div class="w-4 h-4 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0 mt-0.5">
                            <i data-lucide="check" class="w-2.5 h-2.5"></i>
                        </div>
                        <span class="font-medium leading-relaxed">{{ $tip }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- View Full Plan Button -->
            <a href="{{ route('study-plan') }}" 
               class="mt-6 w-full py-3.5 rounded-2xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-black text-xs flex items-center justify-center gap-2 transition shadow-glow hover:scale-[1.01]">
                <span>View Full Plan</span>
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- ================= FULL INTERACTIVE POMODORO FOCUS MODAL ================= -->
    <div x-cloak x-show="pomodoroOpen" 
         style="display: none;"
         @click.self="closePomodoro()"
         @keydown.escape.window="closePomodoro()"
         class="fixed inset-0 z-[60] overflow-y-auto bg-slate-900/60 backdrop-blur-md flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <div class="relative bg-white border border-purple-200/90 rounded-3xl shadow-2xl max-w-lg w-full p-6 sm:p-8 space-y-6 overflow-hidden"
             @click.stop>
            
            <!-- Ambient Background Aura -->
            <div class="absolute -top-16 -right-16 w-48 h-48 bg-pink-300/30 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-16 -left-16 w-48 h-48 bg-purple-300/30 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 relative z-10">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-pink-500 to-purple-600 text-white flex items-center justify-center shadow-xs">
                        <i data-lucide="timer" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black text-slate-900 tracking-tight">Mode Fokus Belajar (Pomodoro)</h3>
                        <p class="text-[11px] font-semibold text-slate-500">Hilangkan distraksi & raih konsentrasi maksimal.</p>
                    </div>
                </div>
                <button type="button" @click="closePomodoro()" class="p-1.5 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition cursor-pointer">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Mode Selector (Focus 25m, Short 5m, Long 15m) -->
            <div class="grid grid-cols-3 gap-2 p-1.5 rounded-2xl bg-slate-100/80 border border-slate-200/80 relative z-10">
                <button type="button" @click="setPomodoroMode('focus')"
                        class="py-2 rounded-xl text-xs font-black transition cursor-pointer"
                        :class="pomodoroMode === 'focus' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                    🎯 Fokus (25m)
                </button>
                <button type="button" @click="setPomodoroMode('short')"
                        class="py-2 rounded-xl text-xs font-black transition cursor-pointer"
                        :class="pomodoroMode === 'short' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                    ☕ Rehat (5m)
                </button>
                <button type="button" @click="setPomodoroMode('long')"
                        class="py-2 rounded-xl text-xs font-black transition cursor-pointer"
                        :class="pomodoroMode === 'long' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                    🌴 Santai (15m)
                </button>
            </div>

            <!-- Big Circular Timer Display -->
            <div class="relative flex flex-col items-center justify-center py-4 z-10">
                <!-- SVG Ring -->
                <div class="relative w-56 h-56 flex items-center justify-center">
                    <svg class="w-full h-full -rotate-90 drop-shadow-md" viewBox="0 0 100 100">
                        <defs>
                            <linearGradient id="pomodoroGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#EC4899"/>
                                <stop offset="50%" stop-color="#D946EF"/>
                                <stop offset="100%" stop-color="#8B5CF6"/>
                            </linearGradient>
                        </defs>
                        <!-- Background Circle -->
                        <circle cx="50" cy="50" r="42" stroke="#F1F5F9" stroke-width="7" fill="none"/>
                        <!-- Animated Progress Circle -->
                        <circle cx="50" cy="50" r="42" stroke="url(#pomodoroGrad)" stroke-width="7" stroke-linecap="round"
                                fill="none"
                                stroke-dasharray="263.89"
                                :stroke-dashoffset="263.89 - (progressPercent / 100 * 263.89)"
                                class="transition-all duration-1000"/>
                    </svg>

                    <!-- Center Digits -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-4xl sm:text-5xl font-black text-slate-900 tracking-tight font-mono" x-text="formatTime(pomodoroTime)">25:00</span>
                        <span class="text-xs font-extrabold text-purple-700 uppercase tracking-widest mt-1" 
                              x-text="pomodoroRunning ? 'Sedang Fokus...' : 'Siap Mulai'">Siap Mulai</span>
                    </div>
                </div>

                <!-- Active Task Pill -->
                <div class="mt-4 px-4 py-1.5 rounded-full bg-purple-50 border border-purple-200 text-xs font-bold text-purple-900 flex items-center gap-2 max-w-xs truncate">
                    <span class="w-2 h-2 rounded-full bg-pink-500 animate-ping"></span>
                    <span>Tugas: <strong x-text="selectedFocusTask">OOP Java Assignment</strong></span>
                </div>
            </div>

            <!-- Controls (Start / Pause, Reset, Finish) -->
            <div class="flex items-center justify-center gap-3 relative z-10">
                <button type="button" 
                        @click="pomodoroRunning ? pausePomodoro() : startPomodoro()"
                        class="px-8 py-3.5 rounded-2xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-black text-sm shadow-glow hover:scale-105 transition flex items-center gap-2 cursor-pointer">
                    <span x-text="pomodoroRunning ? '⏸️ Jeda Sesi' : '▶️ Mulai Fokus'"></span>
                </button>
                <button type="button" @click="resetPomodoro()" 
                        class="p-3.5 rounded-2xl border border-slate-200 text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition cursor-pointer"
                        title="Reset Waktu">
                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Ambient Sound Toggle & Rewards -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs relative z-10">
                <!-- Ambient Audio Switcher -->
                <button type="button" @click="toggleAmbientSound()" 
                        class="flex items-center gap-2 px-3 py-1.5 rounded-xl border transition cursor-pointer"
                        :class="ambientSoundActive ? 'bg-purple-100/80 border-purple-300 text-purple-900 font-bold' : 'bg-slate-50 border-slate-200 text-slate-600 font-medium'">
                    <span>🎧 Suara Hujan (Lo-Fi)</span>
                    <span class="text-[10px] px-1.5 py-0.2 rounded font-black"
                          :class="ambientSoundActive ? 'bg-purple-600 text-white' : 'bg-slate-200 text-slate-600'"
                          x-text="ambientSoundActive ? 'ON' : 'OFF'"></span>
                </button>

                <!-- Claim Button -->
                <button type="button" @click="finishPomodoro()" 
                        class="text-xs font-black text-pink-600 hover:text-pink-700 hover:underline flex items-center gap-1 cursor-pointer">
                    <span>Selesai (+50 XP) 🏆</span>
                </button>
            </div>

        </div>
    </div>

</div>
@endsection
