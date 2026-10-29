<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F8FAFC]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SmartStudy - Plan smarter. Study better.')</title>
    
    <!-- Alpine x-cloak style immediately to prevent FOUC / input glitch during navigation -->
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS (Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#FDF2F8',
                            100: '#FCE7F3',
                            200: '#FBCFE8',
                            300: '#F472B6',
                            400: '#E879F9',
                            500: '#EC4899',
                            600: '#DB2777',
                            700: '#BE185D',
                            800: '#9D174D',
                            900: '#831843',
                        },
                        accent: {
                            50: '#FAF5FF',
                            100: '#F3E8FF',
                            200: '#E9D5FF',
                            300: '#D8B4FE',
                            400: '#C084FC',
                            500: '#A855F7',
                            600: '#9333EA',
                            700: '#7E22CE',
                            800: '#6B21A8',
                            900: '#581C87',
                        },
                        teal: {
                            50: '#FAF5FF',
                            100: '#F3E8FF',
                            200: '#E9D5FF',
                            300: '#D8B4FE',
                            400: '#C084FC',
                            500: '#A855F7',
                            600: '#9333EA',
                            700: '#7E22CE',
                            800: '#6B21A8',
                            900: '#581C87',
                        },
                        dark: '#0F172A',
                        muted: '#64748B',
                        cardborder: '#E2E8F0',
                        canvas: '#F8FAFC'
                    },
                    boxShadow: {
                        'soft': '0 4px 20px -2px rgba(168, 85, 247, 0.08)',
                        'card': '0 2px 10px -1px rgba(15, 23, 42, 0.04), 0 1px 3px 0 rgba(15, 23, 42, 0.02)',
                        'glow': '0 0 25px -4px rgba(236, 72, 153, 0.35)',
                        'glow-purple': '0 0 25px -4px rgba(168, 85, 247, 0.35)',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

    <!-- Alpine App State Definition -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('appState', () => ({
                addTaskModal: false, 
                quickTaskTitle: 'OOP Java Assignment - Build Class Structures',
                quickTimeNeeded: '3 Hours',
                quickDeadline: '2026-10-29',
                quickCategory: 'Programming',
                quickPriority: 'High',
                toastMessage: '{{ session('success') ?? '' }}',
                aiChatOpen: false,
                chatInput: '',
                chatLoading: false,
                chatMessages: [
                    {
                        role: 'assistant',
                        text: 'Halo! 👋 Saya **SmartStudy AI Tutor & Academic Companion**.\nAda soal kuliah yang ingin kamu tanyakan (coding, matematika, PR, teori), atau ingin mengecek jadwal & deadline tugasmu?'
                    }
                ],
                init() {
                    window.smartStudyApp = this;
                    if (this.toastMessage) {
                        setTimeout(() => this.toastMessage = '', 4500);
                    }
                },
                formatAiMessage(text) {
                    if (!text) return '';
                    let escaped = text
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                    
                    // Multi-line code blocks
                    escaped = escaped.replace(/```([a-zA-Z0-9_-]*)\n([\s\S]*?)```/g, (m, lang, code) => {
                        return '<div class="my-2.5 rounded-xl bg-slate-900 text-slate-100 overflow-hidden border border-slate-800 shadow-md text-left"><div class="px-3 py-1 bg-slate-800/90 text-[10px] font-mono text-purple-300 font-bold border-b border-slate-700/60 uppercase tracking-wider flex items-center justify-between"><span>' + (lang || 'code') + '</span><span class="text-slate-400 text-[9px]">Syntax</span></div><pre class="p-3 text-[11px] font-mono overflow-x-auto leading-relaxed"><code>' + code.trim() + '</code></pre></div>';
                    });

                    // Inline code
                    escaped = escaped.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded-md bg-purple-100 text-purple-900 font-mono text-[11px] border border-purple-200/60 font-semibold">$1</code>');

                    // Headers
                    escaped = escaped.replace(/^###\s+(.+)$/gm, '<h4 class="font-black text-xs mt-3 mb-1 text-purple-950">$1</h4>');
                    escaped = escaped.replace(/^##\s+(.+)$/gm, '<h3 class="font-black text-sm mt-3.5 mb-1.5 text-purple-950">$1</h3>');

                    // Horizontal rules
                    escaped = escaped.replace(/^---$/gm, '<hr class="my-2.5 border-purple-100">');

                    // Blockquotes
                    escaped = escaped.replace(/^>\s+(.+)$/gm, '<div class="pl-2.5 my-1.5 border-l-2 border-purple-400 text-slate-600 italic bg-purple-50/60 py-1 rounded-r-lg">$1</div>');

                    // Bold
                    escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-extrabold text-purple-950">$1</strong>');

                    // Bullets
                    escaped = escaped.replace(/^[*-]\s+(.+)$/gm, '<li class="ml-4 list-disc text-slate-700 leading-relaxed">$1</li>');

                    // Linebreaks
                    escaped = escaped.replace(/\n/g, '<br>');

                    // Fix <br> inside code blocks
                    escaped = escaped.replace(/<pre([^>]*)><code>([\s\S]*?)<\/code><\/pre>/g, (m, attrs, c) => {
                        return '<pre' + attrs + '><code>' + c.replace(/<br>/g, '\n') + '</code></pre>';
                    });

                    return escaped;
                },
                toggleAiChat() {
                    this.aiChatOpen = !this.aiChatOpen;
                },
                openAiChat(initialMessage = '') {
                    this.aiChatOpen = true;
                    if (initialMessage) {
                        this.$nextTick(() => {
                            this.sendChatMessage(initialMessage);
                        });
                    }
                },
                sendChatMessage(customText = null) {
                    const q = (customText !== null ? customText : this.chatInput).trim();
                    if (!q || this.chatLoading) return;
                    
                    // Capture conversation history before pushing new user message
                    const historyToSend = this.chatMessages.map(m => ({
                        role: m.role === 'user' ? 'user' : 'model',
                        text: m.text
                    }));

                    this.chatMessages.push({ role: 'user', text: q });
                    this.chatInput = '';
                    this.chatLoading = true;
                    
                    this.$nextTick(() => {
                        const container = document.getElementById('aiChatMessagesContainer');
                        if (container) container.scrollTop = container.scrollHeight;
                    });
                    
                    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                    const csrfToken = csrfMeta ? csrfMeta.content : '{{ csrf_token() }}';
                    
                    fetch('{{ route('ai-chat') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ 
                            message: q,
                            history: historyToSend
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.chatLoading = false;
                        this.chatMessages.push({
                            role: 'assistant',
                            text: data.reply || 'Maaf, belum dapat memproses jawaban saat ini.'
                        });
                        this.$nextTick(() => {
                            const container = document.getElementById('aiChatMessagesContainer');
                            if (container) container.scrollTop = container.scrollHeight;
                        });
                    })
                    .catch(err => {
                        this.chatLoading = false;
                        this.chatMessages.push({
                            role: 'assistant',
                            text: '⚠️ Terjadi kesalahan jaringan. Silakan coba sesaat lagi.'
                        });
                    });
                },
                updateAIDetection() {
                    let t = this.quickTaskTitle.toLowerCase();
                    if (t.includes('math') || t.includes('calculus') || t.includes('quiz') || t.includes('stat') || t.includes('exam')) {
                        this.quickCategory = 'Exam Prep';
                        this.quickPriority = 'High';
                    } else if (t.includes('report') || t.includes('security') || t.includes('paper') || t.includes('research') || t.includes('cyber')) {
                        this.quickCategory = 'Research';
                        this.quickPriority = 'Medium';
                    } else if (t.includes('slide') || t.includes('presentation') || t.includes('hci') || t.includes('deck')) {
                        this.quickCategory = 'Presentation';
                        this.quickPriority = 'Medium';
                    } else if (t.includes('reading') || t.includes('network') || t.includes('book') || t.includes('chapter')) {
                        this.quickCategory = 'Reading';
                        this.quickPriority = 'Low';
                    } else {
                        this.quickCategory = 'Programming';
                        this.quickPriority = 'High';
                    }
                },
                getPriorityScore() {
                    if (this.quickPriority === 'High') return 'High Priority (92% Urgensi)';
                    if (this.quickPriority === 'Medium') return 'Medium Priority (65% Urgensi)';
                    return 'Low Priority (35% Urgensi)';
                },
                getPriorityBadgeClass() {
                    if (this.quickPriority === 'High') return 'bg-rose-100 text-rose-700 border border-rose-200/80';
                    if (this.quickPriority === 'Medium') return 'bg-amber-100 text-amber-700 border border-amber-200/80';
                    return 'bg-emerald-100 text-emerald-700 border border-emerald-200/80';
                },
                showToast(msg) {
                    this.toastMessage = msg;
                    setTimeout(() => this.toastMessage = '', 4500);
                }
            }));
        });

        // Global Helpers callable from any component or script
        window.openAiChat = function(initialMessage = '') {
            if (window.smartStudyApp) {
                window.smartStudyApp.openAiChat(initialMessage);
            } else {
                window.dispatchEvent(new CustomEvent('open-ai-chat', { detail: { message: initialMessage } }));
            }
        };
        window.toggleAiChat = function() {
            if (window.smartStudyApp) {
                window.smartStudyApp.toggleAiChat();
            } else {
                window.dispatchEvent(new CustomEvent('toggle-ai-chat'));
            }
        };
    </script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Canvas Confetti for Gamified Celebrations -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body { 
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            letter-spacing: -0.01em;
        }
        /* Custom Smooth Scrollbar */
        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }

        /* Cute Animated Cat Keyframes */
        @keyframes catBob {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3.5px); }
        }
        @keyframes catTail {
            0%, 100% { transform: rotate(-8deg); }
            50% { transform: rotate(18deg); }
        }
        @keyframes catEarL {
            0%, 80%, 100% { transform: rotate(0deg); }
            85% { transform: rotate(-8deg); }
            90% { transform: rotate(4deg); }
        }
        @keyframes catEarR {
            0%, 82%, 100% { transform: rotate(0deg); }
            87% { transform: rotate(8deg); }
            92% { transform: rotate(-4deg); }
        }
        @keyframes catBlink {
            0%, 88%, 100% { transform: scaleY(1); }
            93% { transform: scaleY(0.08); }
        }
        @keyframes catPawL {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-3px); }
        }
        @keyframes catPawR {
            0%, 100% { transform: translateY(-2.5px); }
            50% { transform: translateY(0.5px); }
        }
        @keyframes starTwinkle {
            0%, 100% { transform: scale(0.85) rotate(0deg); opacity: 0.75; }
            50% { transform: scale(1.25) rotate(15deg); opacity: 1; }
        }
        @keyframes heartPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.3); }
        }

        .animate-cat-bob { animation: catBob 2.6s ease-in-out infinite; }
        .animate-cat-tail { animation: catTail 2.4s ease-in-out infinite; transform-origin: 28px 60px; }
        .animate-cat-ear-l { animation: catEarL 3.8s ease-in-out infinite; transform-origin: 46px 28px; }
        .animate-cat-ear-r { animation: catEarR 4s ease-in-out infinite; transform-origin: 74px 28px; }
        .animate-cat-eyes { animation: catBlink 3.6s infinite; transform-origin: 60px 38px; }
        .animate-cat-paw-l { animation: catPawL 0.7s ease-in-out infinite; }
        .animate-cat-paw-r { animation: catPawR 0.7s ease-in-out infinite 0.35s; }
        .animate-star-1 { animation: starTwinkle 2s ease-in-out infinite; transform-origin: 18px 28px; }
        .animate-star-2 { animation: starTwinkle 2.5s ease-in-out infinite 0.6s; transform-origin: 110px 22px; }
        .animate-heart-beat { animation: heartPulse 1.4s ease-in-out infinite; }

        /* Dashboard Banner Animations */
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            20%, 60% { transform: rotate(14deg); }
            40%, 80% { transform: rotate(-10deg); }
        }
        .animate-wave {
            display: inline-block;
            animation: wave 2.2s ease-in-out infinite;
            transform-origin: 75% 75%;
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-6px) rotate(1.5deg); }
        }
        .animate-float {
            animation: floatSlow 3.8s ease-in-out infinite;
        }

        @keyframes floatSlowReverse {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(5px) rotate(-1.5deg); }
        }
        .animate-float-reverse {
            animation: floatSlowReverse 4.2s ease-in-out infinite;
        }

        @keyframes bellSwing {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(15deg); }
            40% { transform: rotate(-12deg); }
            60% { transform: rotate(8deg); }
            80% { transform: rotate(-5deg); }
        }
        .animate-bell-hover:hover .bell-icon,
        .group:hover .bell-icon {
            animation: bellSwing 0.7s ease-in-out;
            transform-origin: top center;
        }

        @keyframes pulseGlow {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.65; transform: scale(1.1); }
        }
        .animate-pulse-glow {
            animation: pulseGlow 4s ease-in-out infinite;
        }

        @keyframes lightSweep {
            0% { transform: translateX(-150%) skewX(-20deg); }
            40%, 100% { transform: translateX(250%) skewX(-20deg); }
        }
        .animate-light-sweep {
            animation: lightSweep 6s ease-in-out infinite;
        }
    </style>
</head>
<body class="h-full bg-[#F8FAFC] text-slate-800 antialiased selection:bg-teal-500 selection:text-white" x-data="appState()" @open-ai-chat.window="openAiChat($event.detail?.message || '')" @toggle-ai-chat.window="toggleAiChat()">

    <div class="flex min-h-screen">
        <!-- ================= SIDEBAR ================= -->
        <aside class="w-64 bg-white border-r border-slate-200/80 flex flex-col justify-between shrink-0 fixed inset-y-0 left-0 z-30 shadow-[2px_0_15px_-4px_rgba(0,0,0,0.03)]">
            <div>
                <!-- Brand / Logo -->
                <div class="px-6 py-6 border-b border-slate-100/80">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-pink-500 via-fuchsia-500 to-purple-600 flex items-center justify-center text-white shadow-glow group-hover:scale-105 transition-transform duration-200">
                            <!-- Cap Icon -->
                            <svg class="w-6 h-6 drop-shadow-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                                <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-xl font-black text-slate-900 tracking-tight block leading-tight">SmartStudy</span>
                            <span class="text-[10px] font-bold text-transparent bg-clip-text bg-gradient-to-r from-pink-500 to-purple-600 uppercase tracking-wider block">AI Academic Hub</span>
                        </div>
                    </a>
                </div>

                <!-- Navigation Links -->
                <nav class="px-3.5 space-y-1.5 mt-4">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 shadow-xs ring-1 ring-purple-200/70' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                        <div class="p-1 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-400' }}">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                        </div>
                        <span>Dashboard</span>
                    </a>

                    <!-- My Tasks -->
                    <a href="{{ route('tasks') }}" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 {{ request()->routeIs('tasks') ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 shadow-xs ring-1 ring-purple-200/70' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                        <div class="p-1 rounded-lg {{ request()->routeIs('tasks') ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-400' }}">
                            <i data-lucide="check-square" class="w-4 h-4"></i>
                        </div>
                        <span>My Tasks</span>
                    </a>

                    <!-- Study Plan -->
                    <a href="{{ route('study-plan') }}" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 {{ request()->routeIs('study-plan') ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 shadow-xs ring-1 ring-purple-200/70' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                        <div class="p-1 rounded-lg {{ request()->routeIs('study-plan') ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-400' }}">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                        </div>
                        <span>Study Plan</span>
                    </a>

                    <!-- PPT Study AI Generator (NEW FEATURE) -->
                    <a href="{{ route('ai-generator') }}" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 {{ request()->routeIs('ai-generator*') ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 shadow-xs ring-1 ring-purple-200/70' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                        <div class="p-1 rounded-lg {{ request()->routeIs('ai-generator*') ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-400' }}">
                            <i data-lucide="sparkles" class="w-4 h-4"></i>
                        </div>
                        <span>PPT Study AI</span>
                    </a>

                    <!-- Settings -->
                    <a href="{{ route('settings') }}" 
                       class="flex items-center gap-3.5 px-4 py-3 rounded-xl text-sm font-bold transition-all duration-200 {{ request()->routeIs('settings') ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 shadow-xs ring-1 ring-purple-200/70' : 'text-slate-500 hover:text-slate-900 hover:bg-slate-50' }}">
                        <div class="p-1 rounded-lg {{ request()->routeIs('settings') ? 'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-xs' : 'text-slate-400' }}">
                            <i data-lucide="settings" class="w-4 h-4"></i>
                        </div>
                        <span>Settings</span>
                    </a>
                </nav>
            </div>

            <!-- Mascot / Motivation Card (Bottom) -->
            <div class="p-3.5 mb-2">
                <div class="bg-gradient-to-b from-pink-50/90 via-purple-50/70 to-pink-100/40 border border-pink-200/80 rounded-3xl p-3.5 text-center relative overflow-hidden shadow-card hover:border-pink-300 hover:shadow-soft transition-all duration-300 group cursor-pointer"
                     @click="catPepTalk()">
                    
                    <p class="text-xs font-black text-purple-900 mb-1 flex items-center justify-center gap-1.5">
                        <span>You've got this!</span> 
                        <span class="text-rose-500 animate-heart-beat inline-block">♡</span>
                    </p>

                    <!-- Cute Animated Kawaii Cat SVG -->
                    <div class="relative w-32 h-24 mx-auto my-0.5 flex items-center justify-center">
                        <svg viewBox="0 0 130 96" class="w-full h-full drop-shadow-sm overflow-visible">
                            <!-- Background Sparkles / Stars -->
                            <path class="animate-star-1" d="M16 26 L18 31 L23 33 L18 35 L16 40 L14 35 L9 33 L14 31 Z" fill="#F472B6"/>
                            <path class="animate-star-2" d="M112 18 L114 22 L118 23 L114 24 L112 28 L110 24 L106 23 L110 22 Z" fill="#A855F7"/>
                            <circle cx="25" cy="48" r="1.5" fill="#EC4899" opacity="0.6"/>
                            <circle cx="106" cy="38" r="1.5" fill="#C084FC" opacity="0.6"/>

                            <!-- Stack of Study Books at Bottom -->
                            <!-- Bottom Purple Book -->
                            <rect x="18" y="74" width="94" height="12" rx="4" fill="#7E22CE"/>
                            <rect x="22" y="76" width="86" height="7" rx="2" fill="#F3E8FF"/>
                            <line x1="26" y1="79" x2="102" y2="79" stroke="#DDD6FE" stroke-width="1.2"/>
                            <!-- Top Pink Book -->
                            <rect x="22" y="64" width="86" height="12" rx="4" fill="#EC4899"/>
                            <rect x="26" y="66" width="78" height="7" rx="2" fill="#FCE7F3"/>
                            <line x1="30" y1="69" x2="98" y2="69" stroke="#FBCFE8" stroke-width="1.2"/>
                            <!-- Cute Bookmark Ribbon hanging down -->
                            <polygon points="90,75 96,75 93,84 90,82" fill="#BE185D"/>

                            <!-- Fluffy Wagging Tail in back -->
                            <g class="animate-cat-tail">
                                <path d="M28 58 C18 56 12 45 16 38 C18 34 24 36 22 42 C20 48 24 53 30 55 Z" fill="#F59E0B"/>
                                <path d="M16 38 C18 34 24 36 22 42 C20 40 18 39 16 38 Z" fill="#FFFBEB"/>
                            </g>

                            <!-- Cat Body and Head (Breathing Bobbing Group) -->
                            <g class="animate-cat-bob">
                                <!-- Chubby Cat Body -->
                                <ellipse cx="60" cy="53" rx="28" ry="18" fill="#FDE68A"/>
                                <!-- Belly white patch -->
                                <ellipse cx="60" cy="56" rx="19" ry="12" fill="#FFFBEB"/>
                                <!-- Gentle back stripes -->
                                <path d="M38 50 C40 47 43 48 41 52" stroke="#F59E0B" stroke-width="2" stroke-linecap="round"/>
                                <path d="M42 45 C44 42 47 43 45 47" stroke="#F59E0B" stroke-width="2" stroke-linecap="round"/>

                                <!-- Cat Head Group -->
                                <g>
                                    <!-- Left Ear (Animated Twitch) -->
                                    <g class="animate-cat-ear-l">
                                        <path d="M42 32 C38 23 43 12 51 19 C55 23 51 31 44 32 Z" fill="#F59E0B"/>
                                        <path d="M44 29 C41 23 44 16 49 21 C51 24 49 28 45 29 Z" fill="#FDA4AF"/>
                                    </g>

                                    <!-- Right Ear (Animated Twitch) -->
                                    <g class="animate-cat-ear-r">
                                        <path d="M78 32 C82 23 77 12 69 19 C65 23 69 31 76 32 Z" fill="#F59E0B"/>
                                        <path d="M76 29 C79 23 76 16 71 21 C69 24 71 28 75 29 Z" fill="#FDA4AF"/>
                                    </g>

                                    <!-- Head Base -->
                                    <circle cx="60" cy="38" r="21" fill="#FEF3C7"/>
                                    <!-- Chubby Cheeks -->
                                    <ellipse cx="44" cy="42" rx="6" ry="5" fill="#FEF3C7"/>
                                    <ellipse cx="76" cy="42" rx="6" ry="5" fill="#FEF3C7"/>

                                    <!-- Forehead tabby markings -->
                                    <path d="M60 21 L60 25 M56 22 L57 26 M64 22 L63 26" stroke="#F59E0B" stroke-width="1.8" stroke-linecap="round"/>

                                    <!-- Blushing Pink Cheeks -->
                                    <ellipse cx="45" cy="41" rx="4.5" ry="3" fill="#FB7185" opacity="0.65"/>
                                    <ellipse cx="75" cy="41" rx="4.5" ry="3" fill="#FB7185" opacity="0.65"/>

                                    <!-- Eyes (Blinking Animation) -->
                                    <g class="animate-cat-eyes">
                                        <!-- Left Eye -->
                                        <ellipse cx="51" cy="36" rx="4.2" ry="4.5" fill="#1E293B"/>
                                        <circle cx="52.8" cy="34.5" r="1.6" fill="#FFFFFF"/>
                                        <circle cx="49.5" cy="37.8" r="0.9" fill="#FFFFFF"/>
                                        <!-- Right Eye -->
                                        <ellipse cx="69" cy="36" rx="4.2" ry="4.5" fill="#1E293B"/>
                                        <circle cx="70.8" cy="34.5" r="1.6" fill="#FFFFFF"/>
                                        <circle cx="67.5" cy="37.8" r="0.9" fill="#FFFFFF"/>
                                    </g>

                                    <!-- Cute Tiny Nose -->
                                    <polygon points="60,39.5 58,41.8 62,41.8" fill="#F43F5E"/>
                                    <!-- Sweet Cat Smile :3 -->
                                    <path d="M55.5 42.5 Q57.8 45 60 43 Q62.2 45 64.5 42.5" stroke="#334155" stroke-width="1.3" fill="none" stroke-linecap="round"/>

                                    <!-- Cute Whiskers -->
                                    <line x1="38" y1="40" x2="31" y2="39" stroke="#CBD5E1" stroke-width="1" stroke-linecap="round"/>
                                    <line x1="38" y1="43" x2="32" y2="44" stroke="#CBD5E1" stroke-width="1" stroke-linecap="round"/>
                                    <line x1="82" y1="40" x2="89" y2="39" stroke="#CBD5E1" stroke-width="1" stroke-linecap="round"/>
                                    <line x1="82" y1="43" x2="88" y2="44" stroke="#CBD5E1" stroke-width="1" stroke-linecap="round"/>
                                </g>
                            </g>

                            <!-- Laptop in front of Cat -->
                            <g>
                                <!-- Laptop Base / Keyboard -->
                                <polygon points="76,60 106,60 102,72 72,72" fill="#334155"/>
                                <!-- Keyboard keys texture -->
                                <rect x="76" y="62" width="24" height="6" rx="1" fill="#1E293B" opacity="0.6"/>
                                <!-- Open Laptop Screen -->
                                <rect x="78" y="42" width="28" height="20" rx="3.5" fill="#1E293B" stroke="#475569" stroke-width="0.8"/>
                                <!-- Glowing Screen Display -->
                                <rect x="80" y="44" width="24" height="16" rx="2" fill="#38BDF8"/>
                                <!-- Code Editor on Screen -->
                                <path d="M85 49 L89 52 L85 55" stroke="#FFFFFF" stroke-width="1.2" fill="none" stroke-linecap="round"/>
                                <line x1="91" y1="55" x2="98" y2="55" stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round"/>
                                <line x1="84" y1="47" x2="94" y2="47" stroke="#BAE6FD" stroke-width="1" stroke-linecap="round"/>
                            </g>

                            <!-- Cute Typing Paws (Tapping Animation) -->
                            <ellipse class="animate-cat-paw-l" cx="72" cy="59" rx="4.5" ry="3.5" fill="#FFFBEB" stroke="#FDE68A" stroke-width="1"/>
                            <ellipse class="animate-cat-paw-r" cx="81" cy="59" rx="4.5" ry="3.5" fill="#FFFBEB" stroke="#FDE68A" stroke-width="1"/>
                        </svg>
                    </div>

                    <p class="text-[11px] font-black text-teal-800 tracking-tight mt-1 flex items-center justify-center gap-1">
                        <span>Plan smart, study better!</span>
                        <span class="text-teal-500">🐾</span>
                    </p>
                </div>
            </div>
        </aside>

        <!-- ================= MAIN CONTENT AREA ================= -->
        <main class="flex-1 ml-64 min-w-0 bg-[#F8FAFC] pb-12">
            @yield('content')
        </main>
    </div>

    <!-- ================= ADD TASK MODAL (POLISHED CONTINUOUS AI PIPELINE) ================= -->
    <div x-cloak x-show="addTaskModal" 
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="bg-white rounded-3xl w-full max-w-xl shadow-2xl border border-slate-100 overflow-hidden max-h-[92vh] flex flex-col"
             @click.away="addTaskModal = false">
            
            <form action="{{ route('tasks.store') }}" method="POST" class="flex flex-col overflow-hidden max-h-full">
                @csrf

                <!-- Modal Header -->
                <div class="px-7 py-5 bg-gradient-to-r from-pink-50/90 via-purple-50/70 to-white border-b border-purple-100 flex items-center justify-between shrink-0">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded-lg bg-gradient-to-tr from-pink-500 to-purple-600 text-white text-xs shadow-xs">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            </span>
                            <h3 class="text-lg font-black text-slate-900">Add Academic Task</h3>
                        </div>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">Input detail tugas akademik — AI akan mengklasifikasikan & menjadwalkan secara otomatis</p>
                    </div>
                    <button type="button" @click="addTaskModal = false" class="text-slate-400 hover:text-slate-700 p-2 rounded-xl hover:bg-white transition shadow-xs">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-7 space-y-4 overflow-y-auto">
                    <!-- Task Title -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Judul Tugas / Assignment *</label>
                        <input type="text" 
                               name="title" 
                               required
                               x-model="quickTaskTitle" 
                               @input="updateAIDetection()"
                               class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition"
                               placeholder="Contoh: OOP Java Assignment - Build Class Structures">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Time Needed -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Estimasi Waktu Belajar</label>
                            <input type="text" 
                                   name="duration" 
                                   x-model="quickTimeNeeded" 
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition"
                                   placeholder="e.g. 3 Hours">
                        </div>

                        <!-- Deadline (Interactive Calendar Datepicker) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">
                                Deadline Pengumpulan *
                            </label>
                            <input type="date" 
                                   name="due_date" 
                                   x-model="quickDeadline" 
                                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition cursor-pointer shadow-2xs"
                                   required>
                        </div>
                    </div>

                    <!-- Category & Priority Selector Pills -->
                    <div class="grid grid-cols-2 gap-4 pt-1">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Kategori Subjek</label>
                            <select name="category" x-model="quickCategory" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 cursor-pointer">
                                <option value="Programming">Programming (Coding)</option>
                                <option value="Exam Prep">Exam Prep (Quiz/Ujian)</option>
                                <option value="Research">Research (Paper/Laporan)</option>
                                <option value="Presentation">Presentation (Slides)</option>
                                <option value="Reading">Reading (Buku/Jurnal)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5 uppercase tracking-wider">Tingkat Prioritas</label>
                            <select name="priority" x-model="quickPriority" 
                                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 cursor-pointer">
                                <option value="High">High Priority (Urgent)</option>
                                <option value="Medium">Medium Priority</option>
                                <option value="Low">Low Priority</option>
                            </select>
                        </div>
                    </div>

                    <!-- Notes / Catatan Tugas -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                <i data-lucide="file-text" class="w-3.5 h-3.5 text-purple-600"></i>
                                <span>Catatan Tugas / Notes (Apa yang Mau Dikerjakan)</span>
                            </label>
                            <span class="text-[10px] font-semibold text-slate-400">Opsional</span>
                        </div>
                        <textarea name="description" 
                                  rows="2"
                                  class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition resize-none leading-relaxed"
                                  placeholder="Tuliskan catatan apa yang mau dikerjakan (contoh: buat class diagram, baca bab 3, format laporan IEEE, poin tugas dosen, dll)..."></textarea>
                    </div>

                    <!-- Continuous AI Box (Steps 1-3) -->
                    <div class="bg-gradient-to-br from-pink-50/70 via-purple-50/50 to-pink-50/40 border border-purple-200/80 rounded-2xl p-4.5 space-y-3 shadow-xs">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-purple-600 font-bold text-base">✦</span>
                                <h4 class="text-xs font-black text-purple-950 uppercase tracking-wide">Continuous AI Pipeline</h4>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">Automated</span>
                        </div>

                        <!-- Step 1: Google AI Studio Gemini -->
                        <div class="text-xs flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-purple-100 shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-pink-100 text-pink-700 font-black flex items-center justify-center text-[10px]">1</span>
                                <span class="font-bold text-slate-800">Google AI Studio (Gemini):</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-purple-100 text-purple-700 shadow-xs" x-text="quickCategory">Programming</span>
                        </div>

                        <!-- Step 2: Backend Priority Calculator -->
                        <div class="text-xs flex items-center justify-between bg-white px-3.5 py-2.5 rounded-xl border border-purple-100 shadow-xs">
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 font-black flex items-center justify-center text-[10px]">2</span>
                                <span class="font-bold text-slate-800">Backend Priority Calculator:</span>
                            </div>
                            <span class="px-2.5 py-1 rounded-lg text-xs font-black shadow-xs transition-colors" 
                                  :class="getPriorityBadgeClass()" 
                                  x-text="getPriorityScore()">High Priority (92% Urgensi)</span>
                        </div>

                        <!-- Step 3: Gemini & GPT Planner -->
                        <div class="text-xs bg-white px-3.5 py-2.5 rounded-xl border border-purple-100 shadow-xs space-y-1">
                            <div class="flex items-center gap-2.5">
                                <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 font-black flex items-center justify-center text-[10px]">3</span>
                                <span class="font-bold text-slate-800">Gemini & GPT Planner:</span>
                            </div>
                            <p class="text-[11px] text-slate-600 pl-7 leading-relaxed">
                                4 Subtasks generated & otomatis dislotkan ke jadwal belajar mingguan.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer Buttons -->
                <div class="px-7 py-4.5 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" @click="addTaskModal = false"
                            class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-bold text-xs hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit" 
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-black text-xs shadow-glow transition hover:scale-[1.02] flex items-center gap-2 cursor-pointer">
                        <span>Confirm & Add to Study Plan</span>
                        <span>✨</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= FLOATING AI ASSISTANT BUTTON & TOOLTIP ================= -->
    <div class="fixed bottom-6 right-6 z-[60] flex items-center gap-3">
        <!-- Quick Prompt Pill (visible when chat closed) -->
        <div x-show="!aiChatOpen" 
             @click.stop="openAiChat('Ada jadwal apa hari ini?')"
             class="hidden sm:flex items-center gap-2 bg-white/95 backdrop-blur-md px-3.5 py-2 rounded-2xl border border-purple-200/90 shadow-soft text-xs font-bold text-slate-700 hover:border-purple-300 hover:text-purple-900 transition-all cursor-pointer group">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Tanya Jadwal & Soal AI</span>
            <span class="text-pink-500">✨</span>
        </div>

        <button type="button" 
                @click.stop="toggleAiChat()"
                id="floatingAiChatBtn"
                title="Tanya AI Soal & Jadwal"
                class="relative group w-14 h-14 rounded-2xl bg-gradient-to-tr from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white shadow-glow flex items-center justify-center transition-all duration-200 cursor-pointer">
            
            <!-- Sparkles / Chat Icon -->
            <span x-show="!aiChatOpen" class="flex items-center justify-center text-white pointer-events-none">
                <svg class="w-6 h-6 stroke-white drop-shadow-xs" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                    <path d="M5 3v4"/>
                    <path d="M19 17v4"/>
                    <path d="M3 5h4"/>
                    <path d="M17 19h4"/>
                </svg>
            </span>
            <span x-show="aiChatOpen" style="display: none;" class="flex items-center justify-center text-white pointer-events-none">
                <svg class="w-6 h-6 stroke-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18M6 6l12 12"/>
                </svg>
            </span>
            
            <!-- Online status badge -->
            <span class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-500 border-2 border-white shadow-xs pointer-events-none"></span>
        </button>
    </div>

    <!-- ================= AI CHAT WINDOW ================= -->
    <div id="aiChatWindow"
         x-cloak
         x-show="aiChatOpen"
         style="display: none; position: fixed; bottom: 84px;"
         @click.stop
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         class="fixed bottom-[84px] right-3 sm:right-6 z-[70] w-[calc(100vw-24px)] sm:w-[450px] h-[min(580px,calc(100vh-105px))] bg-white rounded-3xl shadow-2xl border border-purple-200/90 flex flex-col overflow-hidden backdrop-blur-xl">
        
        <!-- Header -->
        <div class="px-5 py-4 bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 text-white flex items-center justify-between shadow-xs shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white shadow-inner">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 8V4H8"/>
                        <rect width="16" height="12" x="4" y="8" rx="2"/>
                        <path d="M2 14h2"/>
                        <path d="M20 14h2"/>
                        <path d="M15 13v2"/>
                        <path d="M9 13v2"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-black tracking-tight leading-tight flex items-center gap-1.5">
                        <span>SmartStudy AI Tutor</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-md bg-white/25 font-bold uppercase tracking-wider">Gemini 3.5</span>
                    </h3>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-300"></span>
                        <span class="text-[11px] font-medium text-purple-100">Tanya Soal, Materi & Cek Jadwal</span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <button type="button" @click="aiChatOpen = false" class="p-1.5 rounded-xl hover:bg-white/20 text-white transition cursor-pointer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Quick Prompt Chips -->
        <div class="px-3.5 py-2.5 bg-purple-50/70 border-b border-purple-100 flex items-center gap-2 overflow-x-auto no-scrollbar shrink-0 text-xs">
            <button type="button" 
                    @click="sendChatMessage('Ada jadwal apa hari ini?')"
                    class="shrink-0 px-3 py-1.5 rounded-full bg-white border border-purple-200 text-purple-900 font-bold hover:bg-purple-100 transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                <span>📅</span>
                <span>Jadwal Hari Ini</span>
            </button>
            <button type="button" 
                    @click="sendChatMessage('Bantu jelaskan konsep OOP di Java beserta contoh kodenya')"
                    class="shrink-0 px-3 py-1.5 rounded-full bg-white border border-purple-200 text-purple-900 font-bold hover:bg-purple-100 transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                <span>💻</span>
                <span>Tanya Soal Coding</span>
            </button>
            <button type="button" 
                    @click="sendChatMessage('Bantu cara menyelesaikan sistem persamaan linier dan matriks')"
                    class="shrink-0 px-3 py-1.5 rounded-full bg-white border border-purple-200 text-purple-900 font-bold hover:bg-purple-100 transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                <span>📐</span>
                <span>Tanya Soal Matematika</span>
            </button>
            <button type="button" 
                    @click="sendChatMessage('Tugas apa yang paling mendesak sekarang?')"
                    class="shrink-0 px-3 py-1.5 rounded-full bg-white border border-purple-200 text-purple-900 font-bold hover:bg-purple-100 transition shadow-2xs flex items-center gap-1.5 cursor-pointer">
                <span>⚡</span>
                <span>Tugas Mendesak</span>
            </button>
        </div>

        <!-- Messages Container -->
        <div id="aiChatMessagesContainer" class="flex-1 p-4 space-y-3.5 overflow-y-auto bg-slate-50/60 text-xs">
            <template x-for="(msg, idx) in chatMessages" :key="idx">
                <div class="flex flex-col" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                    <div class="flex items-start gap-2 max-w-[90%]" :class="msg.role === 'user' ? 'flex-row-reverse' : ''">
                        <!-- Avatar -->
                        <div class="w-6 h-6 rounded-lg flex items-center justify-center shrink-0 text-[10px] font-black text-white shadow-2xs"
                             :class="msg.role === 'user' ? 'bg-purple-600' : 'bg-gradient-to-tr from-pink-500 to-purple-600'">
                            <span x-show="msg.role === 'user'">Kamu</span>
                            <span x-show="msg.role !== 'user'">AI</span>
                        </div>
                        
                        <!-- Bubble -->
                        <div class="p-3.5 rounded-2xl text-xs leading-relaxed shadow-card text-left"
                             :class="msg.role === 'user' ? 'bg-gradient-to-r from-pink-500 to-purple-600 text-white rounded-tr-none font-medium' : 'bg-white border border-purple-100/90 text-slate-800 rounded-tl-none'">
                            <div class="space-y-1.5 overflow-hidden" x-html="formatAiMessage(msg.text)"></div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Loading State -->
            <div x-show="chatLoading" class="flex items-start gap-2 max-w-[85%]">
                <div class="w-6 h-6 rounded-lg bg-gradient-to-tr from-pink-500 to-purple-600 text-white flex items-center justify-center shrink-0 text-[10px] font-black shadow-2xs">
                    AI
                </div>
                <div class="p-3 bg-white border border-purple-100 rounded-2xl rounded-tl-none shadow-card flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-pink-500 animate-bounce"></span>
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-2 h-2 rounded-full bg-indigo-500 animate-bounce [animation-delay:0.4s]"></span>
                    <span class="text-[11px] font-semibold text-slate-500 ml-1">AI sedang menganalisis & menulis jawaban... ✨</span>
                </div>
            </div>
        </div>

        <!-- Chat Input Form -->
        <div class="p-3.5 bg-white border-t border-purple-100 shrink-0">
            <form @submit.prevent="sendChatMessage()" class="flex items-center gap-2">
                <input type="text" 
                       x-model="chatInput" 
                       placeholder="Tanya soal, materi kuliah, tugas, atau jadwal..."
                       class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 focus:border-purple-400 focus:ring-2 focus:ring-purple-200 text-xs text-slate-800 placeholder-slate-400 outline-none transition"
                       :disabled="chatLoading">
                <button type="submit" 
                        :disabled="chatLoading || !chatInput.trim()"
                        class="p-2.5 rounded-xl bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white shadow-glow disabled:opacity-50 transition cursor-pointer flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="22" y1="2" x2="11" y2="13"/>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
                    </svg>
                </button>
            </form>
            <p class="text-[10px] text-slate-400 text-center mt-1.5 font-medium">SmartStudy AI Tutor ditenagai Google Gemini & database jadwal.</p>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-cloak x-show="toastMessage" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-4"
         class="fixed bottom-24 right-6 z-50 bg-slate-900/95 text-white px-5 py-3.5 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-700/80 text-xs font-bold backdrop-blur-md">
        <div class="w-6 h-6 rounded-full bg-teal-500/20 text-teal-400 flex items-center justify-center">
            <i data-lucide="check" class="w-3.5 h-3.5"></i>
        </div>
        <span x-text="toastMessage"></span>
    </div>

    <!-- Global Audio & Celebration Helpers -->
    <script>
        // Global Toast Notification Helper
        window.showToast = function(msg) {
            const app = document.querySelector('body[x-data]');
            if (app && app._x_dataStack && app._x_dataStack[0]) {
                app._x_dataStack[0].toastMessage = msg;
                setTimeout(() => {
                    if (app._x_dataStack[0].toastMessage === msg) {
                        app._x_dataStack[0].toastMessage = '';
                    }
                }, 4500);
            }
        };

        // Confetti Fireworks Burst
        window.celebrateConfetti = function() {
            if (typeof confetti === 'function') {
                confetti({
                    particleCount: 90,
                    spread: 80,
                    origin: { y: 0.6 },
                    colors: ['#EC4899', '#A855F7', '#8B5CF6', '#F59E0B', '#10B981']
                });
            }
        };

        // Mascot Interactive Pep-talk
        window.catPepTalk = function() {
            const quotes = [
                'Meow! Semangat belajarnya ya, kamu pasti bisa lulus dengan nilai terbaik! 🐾✨',
                'Fokus 25 menit sekarang, nanti malam bisa santai tanpa beban pikiran! 🎓💖',
                'Ingat: langkah kecil yang konsisten setiap hari menghasilkan prestasi besar! 🚀',
                'Jangan lupa minum air putih dan istirahatkan mata sejenak ya! 💧🐱',
                'Kamu hebat sudah belajar konsisten hari ini! Bangga padamu! 🌟',
                'Meow! Sesi belajar berikutnya pasti makin paham dan lancar jaya! ⚡🐾'
            ];
            const quote = quotes[Math.floor(Math.random() * quotes.length)];
            const app = document.querySelector('[x-data]');
            if (app && app._x_dataStack && app._x_dataStack[0] && app._x_dataStack[0].showToast) {
                app._x_dataStack[0].showToast(quote);
            }
            window.celebrateConfetti();
        };

        // Ambient Rain & Focus Noise Generator via Web Audio API (Native, no external audio file needed!)
        let focusAudioCtx = null;
        let rainSource = null;
        let rainGain = null;

        window.toggleAmbientFocusSound = function(enable) {
            try {
                if (!focusAudioCtx) {
                    focusAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                if (focusAudioCtx.state === 'suspended') {
                    focusAudioCtx.resume();
                }
                if (!enable) {
                    if (rainGain) rainGain.gain.setValueAtTime(0, focusAudioCtx.currentTime);
                    return;
                }
                if (!rainSource) {
                    const bufferSize = focusAudioCtx.sampleRate * 2;
                    const buffer = focusAudioCtx.createBuffer(1, bufferSize, focusAudioCtx.sampleRate);
                    const data = buffer.getChannelData(0);
                    let b0 = 0, b1 = 0, b2 = 0;
                    for (let i = 0; i < bufferSize; i++) {
                        const white = Math.random() * 2 - 1;
                        b0 = 0.99886 * b0 + white * 0.0555179;
                        b1 = 0.99332 * b1 + white * 0.0750759;
                        b2 = 0.96900 * b2 + white * 0.1538520;
                        data[i] = (b0 + b1 + b2) * 0.04;
                    }
                    rainSource = focusAudioCtx.createBufferSource();
                    rainSource.buffer = buffer;
                    rainSource.loop = true;

                    const filter = focusAudioCtx.createBiquadFilter();
                    filter.type = 'lowpass';
                    filter.frequency.setValueAtTime(1100, focusAudioCtx.currentTime);

                    rainGain = focusAudioCtx.createGain();
                    rainGain.gain.setValueAtTime(0.25, focusAudioCtx.currentTime);

                    rainSource.connect(filter);
                    filter.connect(rainGain);
                    rainGain.connect(focusAudioCtx.destination);
                    rainSource.start(0);
                } else if (rainGain) {
                    rainGain.gain.setValueAtTime(0.25, focusAudioCtx.currentTime);
                }
            } catch(e) {
                console.log('Audio ambient init error:', e);
            }
        };

        // Victory Chime Synthesizer via Web Audio API
        window.playVictoryChime = function() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const notes = [523.25, 659.25, 783.99, 1046.50]; // C5, E5, G5, C6
                notes.forEach((freq, index) => {
                    const osc = ctx.createOscillator();
                    const gain = ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(freq, ctx.currentTime + index * 0.11);
                    gain.gain.setValueAtTime(0, ctx.currentTime + index * 0.11);
                    gain.gain.linearRampToValueAtTime(0.22, ctx.currentTime + index * 0.11 + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + index * 0.11 + 0.45);
                    osc.connect(gain);
                    gain.connect(ctx.destination);
                    osc.start(ctx.currentTime + index * 0.11);
                    osc.stop(ctx.currentTime + index * 0.11 + 0.5);
                });
            } catch(e) {}
        };

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
