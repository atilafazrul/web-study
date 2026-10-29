@extends('layouts.app')

@section('title', 'SmartStudy - My Tasks')

@section('content')
<div class="p-8 space-y-6 max-w-7xl mx-auto" x-data="{ 
    searchQuery: '',
    selectedCategory: 'all',
    currentTab: '{{ $filter }}',
    sortBy: '{{ $sortBy }}'
}">

    <!-- ================= TOP HEADER BAR ================= -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                My Tasks <span class="text-teal-600">✨</span>
            </h1>
            <p class="text-sm font-semibold text-slate-500 mt-1">
                Manage all your academic tasks in one place.
            </p>
        </div>

        <!-- Search Bar & Controls -->
        <div class="flex items-center gap-3">
            <!-- Search Input -->
            <div class="relative w-72">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input type="text" x-model="searchQuery" 
                       placeholder="Cari tugas akademik..." 
                       class="w-full pl-9 pr-8 py-2.5 bg-white border border-slate-200/90 rounded-2xl text-xs font-semibold text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition shadow-card">
                <button x-cloak x-show="searchQuery" style="display: none;" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                </button>
            </div>

            <!-- Theme Toggle -->
            <button class="w-10 h-10 rounded-2xl bg-white border border-slate-200/90 flex items-center justify-center text-slate-600 hover:text-teal-600 transition shadow-card"
                    title="Toggle theme">
                <i data-lucide="sun" class="w-4 h-4"></i>
            </button>

            <!-- User Avatar -->
            <a href="{{ route('settings') }}" title="Go to Settings" 
               class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-teal-700 to-teal-500 text-white font-black flex items-center justify-center text-sm shadow-glow ring-2 ring-teal-100 uppercase hover:scale-105 transition">
                {{ strtoupper(substr($currentUser->name ?? 'S', 0, 1)) }}
            </a>
        </div>
    </div>

    <!-- ================= METRICS CARDS & ADD BUTTON ================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- 1. Total Tasks -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4.5 flex items-center gap-3.5 shadow-card hover:border-teal-200 transition">
            <div class="w-12 h-12 rounded-xl bg-teal-50 border border-teal-100 flex items-center justify-center text-teal-600 shrink-0">
                <i data-lucide="clipboard-list" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['total_tasks'] }}</p>
                <p class="text-xs font-bold text-slate-500">Total Tasks</p>
            </div>
        </div>

        <!-- 2. High Priority -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4.5 flex items-center gap-3.5 shadow-card hover:border-purple-200 transition">
            <div class="w-12 h-12 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shrink-0">
                <i data-lucide="alert-circle" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['high_priority'] }}</p>
                <p class="text-xs font-bold text-slate-500">High Priority</p>
            </div>
        </div>

        <!-- 3. Total Estimated Time -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-4.5 flex items-center gap-3.5 shadow-card hover:border-amber-200 transition">
            <div class="w-12 h-12 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xl font-black text-slate-900 tracking-tight">{{ $stats['estimated_time'] }}</p>
                <p class="text-xs font-bold text-slate-500">Total Estimated Time</p>
            </div>
        </div>

        <!-- 4. Add New Task Action Button -->
        <div class="flex items-center">
            <button @click="addTaskModal = true" 
                    class="w-full h-full min-h-[60px] bg-teal-600 hover:bg-teal-700 text-white rounded-2xl px-5 font-black text-sm flex items-center justify-center gap-2 shadow-glow transition hover:scale-[1.01]">
                <i data-lucide="plus" class="w-5 h-5"></i>
                <span>+ Add New Task</span>
            </button>
        </div>
    </div>

    <!-- ================= MAIN SECTION: TASK LIST (LEFT) & AI INSIGHTS (RIGHT) ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT: TASK LIST & FILTERS (8 cols on lg) -->
        <div class="lg:col-span-8 space-y-4">
            
            <!-- Filters & Sort Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200/80 shadow-card">
                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('tasks', ['status' => 'all', 'sort' => $sortBy]) }}" 
                       class="px-4 py-1.5 rounded-full text-xs font-bold transition {{ $filter === 'all' ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 border border-purple-200/90 shadow-xs' : 'text-slate-500 hover:bg-slate-50' }}">
                        All ({{ $counts['all'] }})
                    </a>
                    <a href="{{ route('tasks', ['status' => 'pending', 'sort' => $sortBy]) }}" 
                       class="px-4 py-1.5 rounded-full text-xs font-bold transition {{ $filter === 'pending' ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 border border-purple-200/90 shadow-xs' : 'text-slate-500 hover:bg-slate-50' }}">
                        Pending ({{ $counts['pending'] }})
                    </a>
                    <a href="{{ route('tasks', ['status' => 'completed', 'sort' => $sortBy]) }}" 
                       class="px-4 py-1.5 rounded-full text-xs font-bold transition {{ $filter === 'completed' ? 'bg-gradient-to-r from-pink-50 to-purple-50 text-purple-900 border border-purple-200/90 shadow-xs' : 'text-slate-500 hover:bg-slate-50' }}">
                        Completed ({{ $counts['completed'] }})
                    </a>
                </div>

                <!-- Sort Dropdown -->
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 pr-2">
                    <span>Sort by:</span>
                    <select onchange="window.location.href = '{{ route('tasks', ['status' => $filter]) }}&sort=' + this.value" 
                            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-1.5 font-bold text-slate-700 focus:outline-none focus:ring-1 focus:ring-purple-400 cursor-pointer">
                        <option value="priority" {{ $sortBy === 'priority' ? 'selected' : '' }}>Priority</option>
                        <option value="due_date" {{ $sortBy === 'due_date' ? 'selected' : '' }}>Due Date</option>
                        <option value="duration" {{ $sortBy === 'duration' ? 'selected' : '' }}>Duration</option>
                    </select>
                </div>
            </div>

            <!-- Task Items -->
            <div class="space-y-4">
                @forelse($filteredTasks as $task)
                <div class="relative bg-white border border-slate-200/90 rounded-2xl p-5.5 sm:p-6 pl-7 sm:pl-8 shadow-sm hover:shadow-md hover:border-slate-300 transition-all duration-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4 overflow-hidden group min-h-[96px]"
                    :class="done ? 'bg-slate-50/70 border-slate-200/60 opacity-80' : 'bg-white'"
                    x-data="{ done: {{ $task->status === 'completed' ? 'true' : 'false' }} }"
                    x-show="searchQuery === '' || '{{ strtolower($task->title) }}'.includes(searchQuery.toLowerCase())">
                    
                    <!-- Clean Left Accent Pill Bar (Taller & Elegant) -->
                    <div class="absolute left-0 top-4 bottom-4 w-1.5 rounded-r-full transition-colors 
                        {{ $task->category === 'Programming' ? 'bg-indigo-500' : '' }}
                        {{ $task->category === 'Research' ? 'bg-sky-500' : '' }}
                        {{ $task->category === 'Presentation' ? 'bg-emerald-500' : '' }}
                        {{ $task->category === 'Exam Prep' ? 'bg-amber-500' : '' }}
                        {{ $task->category === 'Reading' ? 'bg-teal-500' : '' }}">
                    </div>

                    <!-- Left: Title, Category, and Description -->
                    <div class="min-w-0 space-y-1.5 flex-1 pr-2">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <h3 class="text-[15px] font-bold text-slate-900 transition-colors tracking-tight" 
                                :class="done ? 'line-through text-slate-400 font-semibold decoration-slate-300' : 'text-slate-900'">
                                {{ $task->title }}
                            </h3>
                            
                            <!-- Category Pill (Soft Pastel & Elegant) -->
                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold tracking-tight inline-flex items-center gap-1
                                {{ $task->category === 'Programming' ? 'bg-indigo-50 text-indigo-700 border border-indigo-100' : '' }}
                                {{ $task->category === 'Research' ? 'bg-sky-50 text-sky-700 border border-sky-100' : '' }}
                                {{ $task->category === 'Presentation' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : '' }}
                                {{ $task->category === 'Exam Prep' ? 'bg-amber-50 text-amber-700 border border-amber-100' : '' }}
                                {{ $task->category === 'Reading' ? 'bg-teal-50 text-teal-700 border border-teal-100' : '' }}">
                                {{ $task->category }}
                            </span>
                        </div>

                        @if(!empty($task->description) && !str_starts_with($task->description, 'Academic assignment for'))
                        <p class="text-xs text-purple-800 bg-purple-50/90 border border-purple-200/80 rounded-xl px-3 py-1.5 font-medium inline-flex items-center gap-1.5 max-w-full shadow-2xs">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-purple-600 shrink-0"></i>
                            <span class="truncate">{{ $task->description }}</span>
                        </p>
                        @else
                        <p class="text-xs sm:text-[13px] text-slate-500 font-medium leading-relaxed line-clamp-2">
                            {{ $task->description }}
                        </p>
                        @endif
                    </div>

                    <!-- Right meta information & Actions -->
                    <div class="flex items-center gap-3 sm:gap-3.5 shrink-0 text-xs pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100">
                        <span class="inline-flex items-center gap-1.5 text-slate-500 font-semibold bg-slate-50 sm:bg-transparent px-2.5 py-1 sm:p-0 rounded-lg">
                            <i data-lucide="calendar" class="w-4 h-4 text-slate-400"></i>
                            <span>{{ $task->due_date }}</span>
                        </span>

                        <span class="inline-flex items-center gap-1.5 text-slate-500 font-semibold bg-slate-50 sm:bg-transparent px-2.5 py-1 sm:p-0 rounded-lg">
                            <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                            <span>{{ $task->duration }}</span>
                        </span>

                        <!-- Priority Badge (Calm, Professional Colors) -->
                        <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase tracking-wider
                            {{ $task->priority === 'High' ? 'bg-rose-50 text-rose-600 border border-rose-200/70' : '' }}
                            {{ $task->priority === 'Medium' ? 'bg-amber-50 text-amber-600 border border-amber-200/70' : '' }}
                            {{ $task->priority === 'Low' ? 'bg-slate-100 text-slate-600 border border-slate-200' : '' }}">
                            {{ $task->priority }}
                        </span>

                        <!-- Interactive Checkbox with DB Toggle -->
                        <button type="button" 
                                @click="
                                    done = !done;
                                    if (done && window.celebrateConfetti) { window.celebrateConfetti(); }
                                    if (done && window.playVictoryChime) { window.playVictoryChime(); }
                                    fetch('{{ route('tasks.toggle', $task->id) }}', {
                                        method: 'POST',
                                        headers: {
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        }
                                    }).then(() => {
                                        showToast(done ? 'Tugas ditandai selesai! +50 XP 🎉' : 'Tugas dipulihkan');
                                        setTimeout(() => window.location.reload(), 350);
                                    });
                                "
                                class="w-7 h-7 rounded-xl border-2 transition-all flex items-center justify-center shrink-0 shadow-2xs hover:scale-105 cursor-pointer"
                                :class="done ? 'bg-gradient-to-tr from-pink-500 to-purple-600 border-purple-500 text-white' : 'border-slate-300 hover:border-purple-400 bg-white'">
                            <svg x-show="done" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>

                        <!-- Delete Button -->
                        <form action="{{ route('tasks.destroy', $task->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tugas ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-slate-300 hover:text-rose-500 p-2 rounded-xl hover:bg-rose-50 transition cursor-pointer" title="Hapus Tugas">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                </div>
                @empty
                <div class="bg-white border border-slate-200/80 rounded-2xl p-10 text-center text-slate-400 space-y-2">
                    <i data-lucide="inbox" class="w-8 h-8 mx-auto text-slate-300"></i>
                    <p class="text-sm font-bold">Tidak ada tugas pada kategori ini.</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- RIGHT: AI INSIGHTS SIDE PANEL (4 cols on lg) -->
        <div class="lg:col-span-4 space-y-4">
            
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card space-y-5">
                <!-- Header -->
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <span class="text-teal-600 font-bold">✨</span>
                    <h2 class="text-base font-black text-slate-900">AI Insights</h2>
                </div>

                <!-- Top Priority Task Card -->
                <div class="bg-slate-50/80 border border-slate-200/70 rounded-2xl p-4.5 space-y-2 shadow-xs">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1.5 text-xs font-black text-slate-800">
                            <span class="text-amber-500">⭐</span>
                            <span>Top Priority Task</span>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-black bg-rose-50 text-rose-600 border border-rose-200">
                            High
                        </span>
                    </div>
                    <p class="text-xs font-black text-slate-900">{{ $topTask ? $topTask->title : 'No Task' }}</p>
                    <p class="text-[11px] text-slate-500 font-medium">Due in 1 day • {{ $topTask ? $topTask->duration : '3h' }} estimated</p>
                </div>

                <!-- Task Category Overview (Donut Chart) -->
                <div>
                    <h3 class="text-xs font-black text-slate-700 mb-3.5 uppercase tracking-wide">Task Category Overview</h3>
                    
                    <div class="flex items-center justify-between gap-4">
                        <!-- SVG Donut Chart -->
                        <div class="relative w-28 h-28 shrink-0">
                            <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
                                <circle cx="50" cy="50" r="38" stroke="#F1F5F9" stroke-width="12" fill="none"/>
                                
                                <circle cx="50" cy="50" r="38" stroke="#3B82F6" stroke-width="12" fill="none"
                                        stroke-dasharray="95.5 143.2" stroke-dashoffset="0"/>
                                
                                <circle cx="50" cy="50" r="38" stroke="#8B5CF6" stroke-width="12" fill="none"
                                        stroke-dasharray="47.7 191" stroke-dashoffset="-95.5"/>
                                
                                <circle cx="50" cy="50" r="38" stroke="#10B981" stroke-width="12" fill="none"
                                        stroke-dasharray="47.7 191" stroke-dashoffset="-143.2"/>
                                
                                <circle cx="50" cy="50" r="38" stroke="#F59E0B" stroke-width="12" fill="none"
                                        stroke-dasharray="47.7 191" stroke-dashoffset="-190.9"/>
                            </svg>
                        </div>

                        <!-- Legend -->
                        <div class="space-y-1.5 text-xs flex-1">
                            @foreach($categories as $cat)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $cat['color'] }}"></span>
                                    <span class="text-slate-600 font-medium">{{ $cat['name'] }}</span>
                                </div>
                                <span class="font-bold text-slate-800">{{ $cat['percentage'] }}%</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Study Tip Pill Box -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-teal-50 to-emerald-50/60 border border-teal-100 flex items-center justify-between gap-3 text-xs shadow-xs">
                    <div class="flex items-start gap-2.5">
                        <span class="text-amber-500 text-base mt-0.5">💡</span>
                        <div>
                            <span class="font-black text-teal-900 block">Study Tip</span>
                            <span class="text-slate-600 text-[11px] leading-tight font-medium">Break down large assignments into smaller steps to stay on track!</span>
                        </div>
                    </div>
                    <button class="text-teal-600 hover:text-teal-700 shrink-0">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
