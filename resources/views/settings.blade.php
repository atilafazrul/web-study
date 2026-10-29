@extends('layouts.app')

@section('title', 'SmartStudy - Settings')

@section('content')
<div class="p-8 space-y-6 max-w-4xl mx-auto" x-data="{ 
    displayName: '{{ old('name', $currentUser->name ?? 'Student') }}',
    email: '{{ old('email', $currentUser->email ?? 'student@university.edu') }}',
    geminiEnabled: true,
    gptEnabled: true
}">

    <!-- Header -->
    <div>
        <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            Settings <span class="text-slate-400 text-xl"><i data-lucide="settings" class="w-6 h-6"></i></span>
        </h1>
        <p class="text-sm font-semibold text-slate-500 mt-1">
            Configure your student profile and AI assistance preferences.
        </p>
    </div>

    <!-- Form Settings to Save to MySQL -->
    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Profile Settings Card -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-7 shadow-card space-y-6">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <h2 class="text-xs font-black text-slate-900 uppercase tracking-wider">
                    Student Profile
                </h2>
                <span class="text-[11px] font-bold text-purple-700 bg-pink-50 px-3 py-1 rounded-full border border-purple-200 shadow-2xs">
                    Active Account
                </span>
            </div>

            <div class="flex items-center gap-5">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-pink-500 via-fuchsia-500 to-purple-600 text-white font-black flex items-center justify-center text-2xl shadow-glow ring-4 ring-pink-100 uppercase"
                     x-text="displayName ? displayName.charAt(0) : 'S'">
                    {{ strtoupper(substr($currentUser->name ?? 'S', 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900" x-text="displayName || 'Student Account'">
                        {{ $currentUser->name ?? 'Student Account' }}
                    </h3>
                    <p class="text-xs text-slate-500 font-medium">Computer Science / Informatics Undergraduate</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wider">Display Name *</label>
                    <input type="text" 
                           name="name" 
                           required
                           value="{{ old('name', $currentUser->name ?? 'Student') }}"
                           x-model="displayName"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition"
                           placeholder="Enter your name">
                </div>
                <div>
                    <label class="block text-xs font-black text-slate-700 mb-1.5 uppercase tracking-wider">Email Address *</label>
                    <input type="email" 
                           name="email" 
                           required
                           value="{{ old('email', $currentUser->email ?? 'student@university.edu') }}"
                           x-model="email"
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-purple-400 focus:bg-white transition"
                           placeholder="student@university.edu">
                </div>
            </div>
        </div>

        <!-- AI Integration Preferences -->
        <div class="bg-white border border-slate-200/80 rounded-3xl p-7 shadow-card space-y-5">
            <h2 class="text-xs font-black text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-4">
                AI Assistant Configuration (Google AI Studio & OpenAI)
            </h2>

            <div class="space-y-4">
                <!-- Gemini Toggle -->
                <div class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 transition">
                    <div>
                        <h3 class="text-xs font-black text-slate-800">Google AI Studio (Gemini 2.0 / 1.5)</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Automatically classify academic subjects and generate smart subtasks.</p>
                    </div>
                    <button type="button" @click="geminiEnabled = !geminiEnabled"
                            class="w-12 h-6.5 rounded-full transition-colors relative shadow-inner cursor-pointer"
                            :class="geminiEnabled ? 'bg-gradient-to-r from-pink-500 to-purple-600' : 'bg-slate-300'">
                        <span class="w-4.5 h-4.5 rounded-full bg-white block transition-transform absolute top-1 left-1 shadow-xs"
                              :class="geminiEnabled ? 'translate-x-5.5' : 'translate-x-0'"></span>
                    </button>
                </div>

                <!-- GPT Toggle -->
                <div class="flex items-center justify-between p-3 rounded-2xl hover:bg-slate-50 transition border-t border-slate-100">
                    <div>
                        <h3 class="text-xs font-black text-slate-800">GPT Schedule Optimizer</h3>
                        <p class="text-[11px] text-slate-500 font-medium mt-0.5">Suggest optimal study time windows avoiding cognitive burnout.</p>
                    </div>
                    <button type="button" @click="gptEnabled = !gptEnabled"
                            class="w-12 h-6.5 rounded-full transition-colors relative shadow-inner cursor-pointer"
                            :class="gptEnabled ? 'bg-gradient-to-r from-pink-500 to-purple-600' : 'bg-slate-300'">
                        <span class="w-4.5 h-4.5 rounded-full bg-white block transition-transform absolute top-1 left-1 shadow-xs"
                              :class="gptEnabled ? 'translate-x-5.5' : 'translate-x-0'"></span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Save Settings Button -->
        <div class="flex justify-end">
            <button type="submit"
                    class="px-7 py-3 rounded-2xl bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-black text-xs shadow-glow transition hover:scale-[1.02] flex items-center gap-2 cursor-pointer">
                <i data-lucide="check" class="w-4 h-4"></i>
                <span>Save Preferences</span>
            </button>
        </div>
    </form>

</div>
@endsection
