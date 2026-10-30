@extends('layouts.app')

@section('title', 'SmartStudy - Study Plan')

@section('content')
<div class="p-8 space-y-6 max-w-7xl mx-auto" x-data="{ 
    viewMode: 'calendar',
    selectedDateRange: 'Oct 27 - Nov 2, 2026',
    dateDropdownOpen: false,
    isRegenerating: false,
    selectedDay: '{{ date('D') }}',
    dayPlans: {{ json_encode($dayPlans) }},
    selectedPlan: {{ json_encode($todayPlan) }},
    calendarTasks: {{ json_encode($calendarTaskEvents ?? []) }},
    currentYear: (new Date()).getFullYear(),
    currentMonth: (new Date()).getMonth(), // 0-indexed: 9 = October
    todayKey: (() => {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    })(),
    selectedDateKey: (() => {
        const d = new Date();
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    })(),
    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
    dayNamesShort: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],

    init() {
        this.selectDateKey(this.todayKey);
    },

    getTodayLabel() {
        const d = new Date();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return `Hari Ini (${d.getDate()} ${months[d.getMonth()]})`;
    },

    prevMonth() {
        if (this.currentMonth === 0) {
            this.currentMonth = 11;
            this.currentYear--;
        } else {
            this.currentMonth--;
        }
        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
    },

    nextMonth() {
        if (this.currentMonth === 11) {
            this.currentMonth = 0;
            this.currentYear++;
        } else {
            this.currentMonth++;
        }
        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
    },

    goToToday() {
        const now = new Date();
        this.currentYear = now.getFullYear();
        this.currentMonth = now.getMonth();
        const y = now.getFullYear();
        const m = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const key = `${y}-${m}-${day}`;
        this.todayKey = key;
        this.selectDateKey(key);
    },

    getTasksForDate(dateKey) {
        return this.calendarTasks.filter(t => t.date_key === dateKey);
    },

    getCalendarDays() {
        const year = this.currentYear;
        const month = this.currentMonth;
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        let startDay = (firstDay.getDay() + 6) % 7; // Monday = 0
        const totalDays = lastDay.getDate();
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        const days = [];
        // Previous month padding
        for (let i = startDay - 1; i >= 0; i--) {
            const d = prevMonthLastDay - i;
            const prevM = month === 0 ? 11 : month - 1;
            const prevY = month === 0 ? year - 1 : year;
            const mStr = String(prevM + 1).padStart(2, '0');
            const dStr = String(d).padStart(2, '0');
            const dateKey = `${prevY}-${mStr}-${dStr}`;
            days.push({
                dayNumber: d,
                dateKey: dateKey,
                isCurrentMonth: false,
                isToday: dateKey === this.todayKey,
                tasks: this.getTasksForDate(dateKey)
            });
        }
        // Current month
        for (let d = 1; d <= totalDays; d++) {
            const mStr = String(month + 1).padStart(2, '0');
            const dStr = String(d).padStart(2, '0');
            const dateKey = `${year}-${mStr}-${dStr}`;
            days.push({
                dayNumber: d,
                dateKey: dateKey,
                isCurrentMonth: true,
                isToday: dateKey === this.todayKey,
                tasks: this.getTasksForDate(dateKey)
            });
        }
        // Next month padding to fill grid
        const remaining = (7 - (days.length % 7)) % 7;
        for (let d = 1; d <= remaining; d++) {
            const nextM = month === 11 ? 0 : month + 1;
            const nextY = month === 11 ? year + 1 : year;
            const mStr = String(nextM + 1).padStart(2, '0');
            const dStr = String(d).padStart(2, '0');
            const dateKey = `${nextY}-${mStr}-${dStr}`;
            days.push({
                dayNumber: d,
                dateKey: dateKey,
                isCurrentMonth: false,
                isToday: dateKey === this.todayKey,
                tasks: this.getTasksForDate(dateKey)
            });
        }
        return days;
    },

    selectDateKey(dateKey) {
        this.selectedDateKey = dateKey;
        const tasksOnDate = this.getTasksForDate(dateKey);
        const [y, m, d] = dateKey.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const dayName = dayNames[dateObj.getDay()];
        const formattedDateLabel = `${dayName}, ${d} ${this.monthNames[m - 1]} ${y}`;

        if (tasksOnDate.length > 0) {
            const firstT = tasksOnDate[0];
            this.selectedPlan = {
                day_name: dayName,
                date_label: formattedDateLabel,
                time: firstT.time || '19:00 - 21:00',
                duration: firstT.duration || '2h',
                title: firstT.title,
                category: firstT.category,
                subtasks: firstT.subtasks || [],
                subtask_focus: (firstT.subtasks && firstT.subtasks.length > 0) ? firstT.subtasks[0] : 'Selesaikan target tugas akademik tepat waktu.',
                has_task: true,
                all_tasks_count: tasksOnDate.length
            };
        } else {
            this.selectedPlan = {
                day_name: dayName,
                date_label: formattedDateLabel,
                time: '19:00 - 21:00 (Fleksibel)',
                duration: '2h',
                title: 'Sesi Belajar Bebas & Review Materi',
                category: 'Free Study',
                subtasks: [
                    'Review materi perkuliahan yang telah dipelajari',
                    'Eksplorasi modul atau persiapan tugas berikutnya',
                    'Istirahat teratur untuk menjaga fokus'
                ],
                subtask_focus: 'Tidak ada tugas yang jatuh tempo pada tanggal ini. Waktu optimal untuk review mandiri.',
                has_task: false,
                all_tasks_count: 0
            };
        }
        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
    },

    selectCalendarTask(task) {
        this.selectedDateKey = task.date_key;
        const [y, m, d] = task.date_key.split('-').map(Number);
        const dateObj = new Date(y, m - 1, d);
        const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const dayName = dayNames[dateObj.getDay()];
        const formattedDateLabel = `${dayName}, ${d} ${this.monthNames[m - 1]} ${y}`;

        this.selectedPlan = {
            day_name: dayName,
            date_label: formattedDateLabel,
            time: task.time || '19:00 - 21:00',
            duration: task.duration || '2h',
            title: task.title,
            category: task.category,
            subtasks: task.subtasks || [],
            subtask_focus: (task.subtasks && task.subtasks.length > 0) ? task.subtasks[0] : 'Selesaikan tugas tepat waktu.',
            has_task: true
        };
        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
    },

    openAddTaskOnDate(dateKey) {
        if (window.smartStudyApp) {
            window.smartStudyApp.quickDeadline = dateKey;
            window.smartStudyApp.addTaskModal = true;
        }
    },

    selectDay(day) {
        this.selectedDay = day;
        if (this.dayPlans[day]) {
            this.selectedPlan = this.dayPlans[day];
        }
    },

    selectSlot(title, duration, category, time, subtasks, day) {
        this.selectedDay = day;
        let subtaskList = [];
        if (Array.isArray(subtasks)) {
            subtaskList = subtasks;
        } else if (typeof subtasks === 'string' && subtasks.trim().length > 0) {
            try {
                let parsed = JSON.parse(subtasks);
                subtaskList = Array.isArray(parsed) ? parsed : [subtasks];
            } catch(e) {
                subtaskList = [subtasks];
            }
        }
        this.selectedPlan = {
            day_name: day,
            date_label: day + ' Slot (' + time + ')',
            time: time,
            duration: duration || '1h',
            title: title,
            category: category,
            subtasks: subtaskList,
            subtask_focus: (subtaskList.length > 0) ? subtaskList[0] : 'Selesaikan tugas dan tinjau materi terkait.',
            has_task: true
        };
        this.$nextTick(() => { if (window.lucide) window.lucide.createIcons(); });
    },

    regeneratePlan() {
        this.isRegenerating = true;
        fetch('{{ route('study-plan.regenerate') }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            showToast('AI telah menyusun ulang jadwal belajar secara optimal! ✨');
            setTimeout(() => {
                window.location.reload();
            }, 600);
        })
        .catch(() => {
            this.isRegenerating = false;
            showToast('Jadwal dioptimasi ulang!');
            setTimeout(() => window.location.reload(), 400);
        });
    }
}">

    <!-- ================= TOP HEADER ================= -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                Study Plan <span class="text-xl">📅</span>
            </h1>
            <p class="text-sm font-semibold text-slate-500 mt-1">
                Jadwal belajar dan kalender akademik cerdas berbasis tanggal & deadline riil.
            </p>
        </div>

        <!-- Controls: View Mode Toggle & Regenerate -->
        <div class="flex items-center gap-3 flex-wrap">
            <!-- Mode Switcher Pill (Kalender vs Timetable) -->
            <div class="inline-flex p-1 bg-white border border-slate-200/90 rounded-2xl shadow-card">
                <button type="button" 
                        @click="viewMode = 'calendar'" 
                        :class="viewMode === 'calendar' ? 'bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 text-white shadow-glow font-black' : 'text-slate-600 hover:text-purple-700 font-bold'" 
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                        <line x1="16" x2="16" y1="2" y2="6"/>
                        <line x1="8" x2="8" y1="2" y2="6"/>
                        <line x1="3" x2="21" y1="10" y2="10"/>
                    </svg>
                    <span>Kalender 📅</span>
                </button>
                <button type="button" 
                        @click="viewMode = 'timetable'" 
                        :class="viewMode === 'timetable' ? 'bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 text-white shadow-glow font-black' : 'text-slate-600 hover:text-purple-700 font-bold'" 
                        class="px-4 py-2 rounded-xl text-xs transition-all flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 3h18v18H3zM3 9h18M3 15h18M9 3v18M15 3v18"/>
                    </svg>
                    <span>Timetable Mingguan 📊</span>
                </button>
            </div>

            <!-- Regenerate Button (Active AI Pipeline Re-slotting) -->
            <button @click="regeneratePlan()" 
                    :disabled="isRegenerating"
                    title="Klik untuk menyusun ulang jadwal belajar secara optimal dengan AI"
                    class="bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 disabled:opacity-60 text-white rounded-2xl px-4 py-2.5 text-xs font-black flex items-center gap-2 shadow-glow transition hover:scale-[1.02] cursor-pointer">
                <i data-lucide="sparkles" class="w-4 h-4" :class="isRegenerating ? 'animate-spin' : ''"></i>
                <span x-text="isRegenerating ? 'Menyusun Ulang...' : '✨ Optimasi AI'"></span>
            </button>
        </div>
    </div>

    <!-- ================= MAIN CONTENT GRID ================= -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- LEFT: MAIN CALENDAR OR TIMETABLE (8 cols on lg) -->
        <div class="lg:col-span-8 bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card flex flex-col justify-between">
            
            <!-- ================= VIEW 1: KALENDER AKADEMIK INTERAKTIF ================= -->
            <div x-show="viewMode === 'calendar'" class="space-y-4">
                <!-- Month Navigation Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-1 bg-slate-50 border border-slate-200/80 rounded-xl p-1 shadow-2xs">
                            <button type="button" @click="prevMonth()" 
                                    class="p-1.5 rounded-lg hover:bg-white text-slate-600 hover:text-purple-700 hover:shadow-xs transition cursor-pointer"
                                    title="Bulan Sebelumnya">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"/>
                                </svg>
                            </button>
                            <button type="button" @click="nextMonth()" 
                                    class="p-1.5 rounded-lg hover:bg-white text-slate-600 hover:text-purple-700 hover:shadow-xs transition cursor-pointer"
                                    title="Bulan Berikutnya">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"/>
                                </svg>
                            </button>
                        </div>
                        <h2 class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2">
                            <span x-text="monthNames[currentMonth]"></span>
                            <span x-text="currentYear" class="text-purple-600"></span>
                        </h2>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="goToToday()" 
                                class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 border border-purple-200 text-xs font-black transition cursor-pointer flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span x-text="getTodayLabel()"></span>
                        </button>
                        <button type="button" @click="openAddTaskOnDate(selectedDateKey)" 
                                class="px-3.5 py-1.5 rounded-xl bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white text-xs font-black shadow-glow transition cursor-pointer flex items-center gap-1.5">
                            <span>+ Tambah Tugas</span>
                        </button>
                    </div>
                </div>

                <!-- 7 Day Names Header -->
                <div class="grid grid-cols-7 gap-1.5 text-center text-xs font-black text-slate-400 py-1 border-b border-slate-100">
                    <template x-for="day in dayNamesShort" :key="day">
                        <div class="py-1" x-text="day"></div>
                    </template>
                </div>

                <!-- Calendar Days Grid -->
                <div class="grid grid-cols-7 gap-1.5">
                    <template x-for="(cell, cIdx) in getCalendarDays()" :key="cIdx">
                        <div class="min-h-[92px] sm:min-h-[105px] p-2 rounded-2xl border transition flex flex-col justify-between cursor-pointer relative group"
                             :class="{
                                 'bg-white border-slate-200/90 hover:border-purple-300 hover:shadow-xs': cell.isCurrentMonth && selectedDateKey !== cell.dateKey,
                                 'bg-slate-50/60 border-slate-100 text-slate-300': !cell.isCurrentMonth && selectedDateKey !== cell.dateKey,
                                 'bg-purple-50/70 border-2 border-purple-600 shadow-md ring-2 ring-purple-100': selectedDateKey === cell.dateKey
                             }"
                             @click="selectDateKey(cell.dateKey)">
                            
                            <!-- Top Row: Day Number & Today indicator -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black rounded-lg w-6 h-6 flex items-center justify-center transition"
                                      :class="{
                                          'bg-gradient-to-tr from-pink-500 to-purple-600 text-white shadow-2xs': cell.isToday,
                                          'text-purple-950 font-black': selectedDateKey === cell.dateKey && !cell.isToday,
                                          'text-slate-700': cell.isCurrentMonth && !cell.isToday && selectedDateKey !== cell.dateKey,
                                          'text-slate-400 opacity-60': !cell.isCurrentMonth
                                      }"
                                      x-text="cell.dayNumber">
                                </span>

                                <template x-if="cell.isToday">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-pink-600 bg-pink-100/70 px-1.5 py-0.5 rounded-md">
                                        Hari Ini
                                    </span>
                                </template>

                                <!-- Add shortcut button on hover -->
                                <button type="button" 
                                        @click.stop="openAddTaskOnDate(cell.dateKey)" 
                                        class="opacity-0 group-hover:opacity-100 w-5 h-5 rounded-md bg-purple-100 text-purple-700 hover:bg-purple-200 flex items-center justify-center text-xs font-bold transition"
                                        title="Tambah tugas di tanggal ini">
                                    +
                                </button>
                            </div>

                            <!-- Middle/Bottom: Task badges on this day -->
                            <div class="space-y-1 mt-1 overflow-hidden">
                                <template x-for="task in cell.tasks" :key="task.id">
                                    <div class="px-2 py-1 rounded-lg border text-[10px] font-black truncate shadow-2xs flex items-center justify-between gap-1 transition hover:scale-[1.02]"
                                         :class="task.badge_color"
                                         @click.stop="selectCalendarTask(task)"
                                         :title="task.title + ' (' + task.duration + ')'">
                                        <span class="truncate" x-text="task.title"></span>
                                        <span class="text-[8px] opacity-70 shrink-0" x-text="task.duration"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Calendar Legend Footer -->
                <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500 font-bold">
                    <div class="flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-sky-500"></span>
                            <span>Programming</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                            <span>Exam Prep</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span>Research</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                            <span>Presentation</span>
                        </div>
                    </div>
                    <div class="text-[11px] text-purple-700 font-extrabold flex items-center gap-1">
                        <span>✨ Klik tanggal untuk melihat detail jadwal</span>
                    </div>
                </div>
            </div>

            <!-- ================= VIEW 2: TIMETABLE MINGGUAN (HOURLY) ================= -->
            <div x-cloak x-show="viewMode === 'timetable'" style="display: none;" class="space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-black text-slate-900 tracking-tight flex items-center gap-1.5">
                        <i data-lucide="clock" class="w-4 h-4 text-purple-600"></i>
                        <span>Timetable Belajar Mingguan (Sesi Malam 19:00 - 22:00)</span>
                    </h2>
                    <span class="text-xs font-bold text-purple-600 bg-purple-50 px-3 py-1 rounded-xl border border-purple-200/60">
                        Oct 26 - Nov 1, 2026
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr>
                                <th class="p-3 text-left text-xs font-bold text-slate-400 w-20"></th>
                                @foreach($days as $day)
                                <th class="p-2 text-center">
                                    <button type="button" 
                                            @click="selectDay('{{ $day['name'] }}')"
                                            class="w-full px-2 py-2.5 rounded-2xl text-xs transition cursor-pointer"
                                            :class="selectedDay === '{{ $day['name'] }}' ? 'bg-gradient-to-b from-pink-50 to-purple-50 border-2 border-purple-500 shadow-sm ring-2 ring-purple-100' : 'bg-slate-50/80 hover:bg-slate-100 border border-transparent'">
                                        <span class="block text-[11px] font-bold" :class="selectedDay === '{{ $day['name'] }}' ? 'text-purple-700 font-black' : 'text-slate-400'">
                                            {{ $day['name'] }}
                                        </span>
                                        <span class="block font-black" :class="selectedDay === '{{ $day['name'] }}' ? 'text-purple-900' : 'text-slate-700'">
                                            {{ $day['date'] }}
                                        </span>
                                    </button>
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            <!-- 7 PM ROW -->
                            <tr>
                                <td class="py-5 px-3 text-xs font-black text-slate-400 align-top">7 PM</td>
                                @foreach($days as $day)
                                @php $slot = $timeSlots['7 PM'][$day['name']]; @endphp
                                <td class="p-2 align-top h-28 border-l border-slate-100">
                                    @if($slot)
                                    <div class="p-3 rounded-2xl border text-xs font-bold h-full flex flex-col justify-between shadow-xs transition hover:scale-[1.03] cursor-pointer {{ $slot['color'] }}"
                                         @click="selectSlot('{{ addslashes($slot['title']) }}', '{{ $slot['duration'] }}', '{{ $slot['category'] }}', '{{ $slot['time'] }}', {{ json_encode($slot['subtasks'] ?? []) }}, '{{ $day['name'] }}')">
                                        <div>
                                            <p class="font-black leading-tight line-clamp-2">{{ $slot['title'] }}</p>
                                            <p class="text-[10px] font-bold opacity-80 mt-1">({{ $slot['duration'] }})</p>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-wider opacity-80">{{ $slot['category'] }}</span>
                                    </div>
                                    @else
                                    <div class="h-full rounded-2xl border border-dashed border-slate-100 hover:border-purple-200 hover:bg-purple-50/30 transition cursor-pointer flex items-center justify-center text-[10px] text-slate-300 font-bold"
                                         @click="addTaskModal = true">
                                        + Slot
                                    </div>
                                    @endif
                                </td>
                                @endforeach
                            </tr>

                            <!-- 8 PM ROW -->
                            <tr>
                                <td class="py-5 px-3 text-xs font-black text-slate-400 align-top">8 PM</td>
                                @foreach($days as $day)
                                @php $slot = $timeSlots['8 PM'][$day['name']]; @endphp
                                <td class="p-2 align-top h-28 border-l border-slate-100">
                                    @if($slot)
                                    <div class="p-3 rounded-2xl border text-xs font-bold h-full flex flex-col justify-between shadow-xs transition hover:scale-[1.03] cursor-pointer {{ $slot['color'] }}"
                                         @click="selectSlot('{{ addslashes($slot['title']) }}', '{{ $slot['duration'] }}', '{{ $slot['category'] }}', '{{ $slot['time'] }}', {{ json_encode($slot['subtasks'] ?? []) }}, '{{ $day['name'] }}')">
                                        <div>
                                            <p class="font-black leading-tight line-clamp-2">{{ $slot['title'] }}</p>
                                            <p class="text-[10px] font-bold opacity-80 mt-1">({{ $slot['duration'] }})</p>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-wider opacity-80">{{ $slot['category'] }}</span>
                                    </div>
                                    @else
                                    <div class="h-full rounded-2xl border border-dashed border-slate-100 hover:border-purple-200 hover:bg-purple-50/30 transition cursor-pointer flex items-center justify-center text-[10px] text-slate-300 font-bold"
                                         @click="addTaskModal = true">
                                        + Slot
                                    </div>
                                    @endif
                                </td>
                                @endforeach
                            </tr>

                            <!-- 9 PM ROW -->
                            <tr>
                                <td class="py-5 px-3 text-xs font-black text-slate-400 align-top">9 PM</td>
                                @foreach($days as $day)
                                @php $slot = $timeSlots['9 PM'][$day['name']]; @endphp
                                <td class="p-2 align-top h-28 border-l border-slate-100">
                                    @if($slot)
                                    <div class="p-3 rounded-2xl border text-xs font-bold h-full flex flex-col justify-between shadow-xs transition hover:scale-[1.03] cursor-pointer {{ $slot['color'] }}"
                                         @click="selectSlot('{{ addslashes($slot['title']) }}', '{{ $slot['duration'] }}', '{{ $slot['category'] }}', '{{ $slot['time'] }}', {{ json_encode($slot['subtasks'] ?? []) }}, '{{ $day['name'] }}')">
                                        <div>
                                            <p class="font-black leading-tight line-clamp-2">{{ $slot['title'] }}</p>
                                            <p class="text-[10px] font-bold opacity-80 mt-1">({{ $slot['duration'] }})</p>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-wider opacity-80">{{ $slot['category'] }}</span>
                                    </div>
                                    @else
                                    <div class="h-full rounded-2xl border border-dashed border-slate-100 hover:border-purple-200 hover:bg-purple-50/30 transition cursor-pointer flex items-center justify-center text-[10px] text-slate-300 font-bold"
                                         @click="addTaskModal = true">
                                        + Slot
                                    </div>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT: DYNAMIC SELECTED PLAN & STUDY SUMMARY (4 cols on lg) -->
        <div class="lg:col-span-4 space-y-5">
            
            <!-- 1. Dynamic Day Plan Card -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card space-y-4">
                <div class="flex items-center justify-between pb-3.5 border-b border-slate-100">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-xl bg-gradient-to-tr from-pink-500 to-purple-600 flex items-center justify-center text-white shadow-xs">
                            <i data-lucide="calendar-check" class="w-4 h-4"></i>
                        </div>
                        <h2 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                            Jadwal Belajar • <span class="text-purple-700 font-black" x-text="selectedPlan.date_label"></span>
                        </h2>
                    </div>
                    <a href="{{ route('tasks') }}" class="text-xs font-bold text-purple-600 hover:text-purple-700 flex items-center gap-1.5 group bg-purple-50 hover:bg-purple-100/70 px-3 py-1.5 rounded-xl transition">
                        <span>Lihat Tugas</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
                    </a>
                </div>

                <!-- Slot Card Dynamic -->
                <div class="relative bg-gradient-to-br from-white via-purple-50/20 to-pink-50/30 border border-purple-200/80 rounded-2xl p-5 shadow-xs transition-all overflow-hidden">
                    <!-- Left Accent Gradient Strip -->
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-pink-500 via-fuchsia-500 to-purple-600 rounded-l-2xl"></div>

                    <div class="space-y-3.5 pl-1.5">
                        <!-- Top Row: Time, Duration & Category Badge -->
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-purple-100/70 text-purple-900 text-xs font-black">
                                <i data-lucide="clock" class="w-3.5 h-3.5 text-purple-600 shrink-0"></i>
                                <span x-text="selectedPlan.time"></span>
                                <template x-if="selectedPlan.duration">
                                    <span class="text-purple-600 font-bold text-[11px]">· <span x-text="selectedPlan.duration"></span></span>
                                </template>
                            </div>
                            <span class="text-[10px] font-black px-2.5 py-1 rounded-lg bg-white border border-purple-200 text-purple-800 shadow-2xs uppercase tracking-wider" 
                                  x-text="selectedPlan.category"></span>
                        </div>

                        <!-- Task Title -->
                        <h3 class="text-base font-black text-slate-900 tracking-tight leading-snug" x-text="selectedPlan.title"></h3>

                        <!-- Subtasks / Fokus Section -->
                        <div class="pt-3 border-t border-purple-100/80 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-black text-slate-800 uppercase tracking-wide flex items-center gap-1.5">
                                    <i data-lucide="check-square" class="w-4 h-4 text-purple-600"></i>
                                    <span>Target Fokus & Subtask:</span>
                                </p>
                                <template x-if="selectedPlan.subtasks && selectedPlan.subtasks.length > 0">
                                    <span class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-purple-100 text-purple-800" 
                                          x-text="selectedPlan.subtasks.length + ' Langkah'"></span>
                                </template>
                            </div>

                            <!-- Render full list of subtasks when available -->
                            <template x-if="selectedPlan.subtasks && selectedPlan.subtasks.length > 0">
                                <div class="space-y-2">
                                    <template x-for="(sub, idx) in selectedPlan.subtasks" :key="idx">
                                        <div class="flex items-start gap-2.5 bg-white p-3 rounded-xl border border-slate-200/90 hover:border-purple-300 shadow-2xs transition group">
                                            <span class="w-5 h-5 rounded-lg bg-gradient-to-tr from-pink-500 to-purple-600 text-white flex items-center justify-center text-[10px] font-black shrink-0 mt-0.5 shadow-2xs" 
                                                  x-text="idx + 1"></span>
                                            <p class="text-xs text-slate-700 font-semibold leading-relaxed group-hover:text-slate-900 transition" x-text="sub"></p>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Fallback if single subtask_focus text -->
                            <template x-if="!selectedPlan.subtasks || selectedPlan.subtasks.length === 0">
                                <div class="bg-white p-3.5 rounded-xl border border-slate-200/90 shadow-2xs">
                                    <p class="text-xs text-slate-700 font-medium leading-relaxed" 
                                       x-text="selectedPlan.subtask_focus">
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="p-3 rounded-2xl bg-purple-50/70 border border-purple-100 text-center">
                    <p class="text-[11px] text-purple-800 font-semibold flex items-center justify-center gap-1.5">
                        <span class="text-sm">💡</span>
                        <span>Klik tanggal di kalender untuk melihat detail jadwal atau menambah tugas baru.</span>
                    </p>
                </div>
            </div>

            <!-- 2. Study Plan Summary -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-card space-y-5">
                <div class="flex items-center gap-2 pb-3 border-b border-slate-100">
                    <div class="p-1 rounded-xl bg-purple-50 text-purple-600">
                        <i data-lucide="bar-chart-2" class="w-4 h-4"></i>
                    </div>
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-wide">
                        Study Plan Summary
                    </h2>
                </div>

                <!-- 3 Stats Columns -->
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $summary['total_tasks'] }}</p>
                        <p class="text-[11px] font-bold text-slate-400 mt-0.5">Total Tasks</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900 tracking-tight">{{ $summary['total_study_time'] }}</p>
                        <p class="text-[11px] font-bold text-slate-400 mt-0.5">Total Study Time</p>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-pink-600 to-purple-600 tracking-tight">{{ $summary['on_track_percent'] }}%</p>
                        <p class="text-[11px] font-bold text-slate-400 mt-0.5">On Track</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5 pt-2">
                    <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden shadow-inner">
                        <div class="bg-gradient-to-r from-pink-500 via-fuchsia-500 to-purple-600 h-2.5 rounded-full transition-all duration-700 shadow-xs" style="width: {{ $summary['on_track_percent'] }}%"></div>
                    </div>
                    <p class="text-[11px] text-right font-black text-slate-400">Target completion rate</p>
                </div>
            </div>

            <!-- 3. AI Assistant Helper Card -->
            <div class="bg-gradient-to-br from-pink-50/80 via-purple-50/60 to-purple-100/40 border border-purple-200/70 rounded-3xl p-5 shadow-card space-y-2">
                <div class="flex items-center gap-2 text-xs font-black text-purple-900">
                    <i data-lucide="sparkles" class="w-4 h-4 text-purple-600"></i>
                    <span>Adaptive Schedule & Calendar Active</span>
                </div>
                <p class="text-xs text-slate-600 font-medium leading-relaxed">
                    Kalender dan jadwal belajar sinkron otomatis dengan deadline riil setiap tugas tanpa bentrok waktu.
                </p>
            </div>

        </div>

    </div>

</div>
@endsection
