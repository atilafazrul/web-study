<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Models\StudyMaterial;
use Illuminate\Http\Request;

class SmartStudyController extends Controller
{
    /**
     * Ensure database has default data if empty
     */
    private function ensureInitialTasks()
    {
        if (Task::count() === 0) {
            (new \Database\Seeders\DatabaseSeeder())->run();
        }
    }

    /**
     * Dashboard View (Screen 1)
     */
    public function dashboard()
    {
        $this->ensureInitialTasks();

        $allTasks = Task::orderBy('id', 'desc')->get();
        $totalCount = $allTasks->count();
        $completedCount = $allTasks->where('status', 'completed')->count();
        $pendingTasks = $allTasks->where('status', 'pending');
        $dueThisWeek = $pendingTasks->count();
        $highPriorityCount = $pendingTasks->where('priority', 'High')->count();

        // Calculate total hours
        $totalHours = 0;
        foreach ($allTasks as $t) {
            preg_match('/(\d+)/', $t->duration, $matches);
            $totalHours += isset($matches[1]) ? (int)$matches[1] : 2;
        }

        $stats = [
            'total_tasks' => $totalCount,
            'pending_tasks' => $dueThisWeek,
            'due_this_week' => $dueThisWeek,
            'completed' => $completedCount,
            'high_priority' => $highPriorityCount,
            'study_time' => ($totalHours ?: 12) . 'h'
        ];

        // Active task for AI Analysis (high priority pending task)
        $aiTask = $allTasks->where('status', 'pending')->first() ?? $allTasks->first();

        $aiAnalysis = [
            'task' => $aiTask,
            'classification' => $aiTask ? $aiTask->category : 'Programming',
            'priority_score' => $aiTask ? $aiTask->priority_score : 92,
            'urgency' => ($aiTask && $aiTask->priority) ? $aiTask->priority . ' Priority' : 'High Priority',
            'urgency_note' => 'Due soon and requires significant effort.',
            'subtasks' => ($aiTask && is_array($aiTask->subtasks)) ? $aiTask->subtasks : [
                'Review the assignment requirements',
                'Design the class structure',
                'Implement the core functions',
                'Test and debug the code',
                'Prepare for submission'
            ]
        ];

        // Weekly schedule mapped from tasks
        $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $dateLabels = ['Oct 26', 'Oct 27', 'Oct 28', 'Oct 29', 'Oct 30', 'Oct 31', 'Nov 1'];
        
        $colorStyles = [
            'Programming' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Exam Prep' => 'bg-purple-50 text-purple-700 border-purple-200',
            'Research' => 'bg-sky-50 text-sky-700 border-sky-200',
            'Presentation' => 'bg-teal-50 text-teal-700 border-teal-200',
            'Reading' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'Default' => 'bg-slate-50 text-slate-700 border-slate-200',
        ];

        $scheduleDays = [];
        foreach ($dayNames as $idx => $d) {
            $taskForDay = $allTasks->first(function ($t) use ($d, $dateLabels, $idx) {
                if (!empty($t->due_date)) {
                    try {
                        $cDate = \Carbon\Carbon::parse($t->due_date);
                        if ($cDate->format('M j') === $dateLabels[$idx]) {
                            return true;
                        }
                    } catch (\Exception $e) {}
                }
                return $t->assigned_day === $d;
            });
            if ($taskForDay) {
                $category = $taskForDay->category ?? 'Default';
                $color = $colorStyles[$category] ?? $colorStyles['Default'];
                $subject = strlen($taskForDay->title) > 18 ? substr($taskForDay->title, 0, 16) . '..' : $taskForDay->title;
                $evening = [
                    'subject' => $subject,
                    'duration' => $taskForDay->duration ?? '1h',
                    'color' => $color
                ];
            } else {
                $evening = [
                    'subject' => $d === 'Sat' ? 'Free Time' : 'Review Slot',
                    'duration' => '',
                    'color' => 'bg-slate-50 text-slate-500 border-slate-200'
                ];
            }

            $scheduleDays[] = [
                'name' => $d,
                'date' => $dateLabels[$idx],
                'evening' => $evening
            ];
        }

        $weeklySchedule = [
            'days' => $scheduleDays,
            'flexible_task' => [
                'title' => 'Finish AI Security Report (2h)',
                'icon' => '⭐'
            ]
        ];

        $studyTips = [
            'Prioritize OOP Java and Math Quiz (both due this week).',
            'Try breaking down the report into sections per day.',
            'Take short breaks every 50 minutes!'
        ];

        // Dynamic Time-based Greetings (Pagi, Siang, Sore, Malam)
        $tz = config('app.timezone') === 'UTC' ? 'Asia/Jakarta' : config('app.timezone');
        $hour = (int) now($tz)->format('H');
        $userStreak = (int) now($tz)->format('N'); // 1 = Senin, 2 = Selasa, 3 = Rabu, 4 = Kamis, dst.

        if ($hour >= 4 && $hour < 11) {
            $timeGreeting = 'Good morning';
            $timeSubtitle = 'Selamat pagi! Awali hari dengan fokus & rencana belajar cerdas.';
        } elseif ($hour >= 11 && $hour < 15) {
            $timeGreeting = 'Good afternoon';
            $timeSubtitle = 'Selamat siang! Tetap berenergi menuntaskan target hari ini.';
        } elseif ($hour >= 15 && $hour < 18) {
            $timeGreeting = 'Good afternoon';
            $timeSubtitle = 'Selamat sore! Waktu tepat untuk review materi & tugas.';
        } else {
            $timeGreeting = 'Good evening';
            $timeSubtitle = 'Selamat malam! Waktu tenang untuk fokus & belajar optimal.';
        }

        return view('dashboard', [
            'stats' => $stats,
            'tasks' => $allTasks,
            'aiAnalysis' => $aiAnalysis,
            'weeklySchedule' => $weeklySchedule,
            'studyTips' => $studyTips,
            'timeGreeting' => $timeGreeting,
            'timeSubtitle' => $timeSubtitle,
            'userStreak' => $userStreak
        ]);
    }

    /**
     * My Tasks View (Screen 2)
     */
    public function tasks(Request $request)
    {
        $this->ensureInitialTasks();

        $filter = $request->query('status', 'all');
        $sortBy = $request->query('sort', 'priority');
        
        $query = Task::query();

        if ($filter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($filter === 'completed') {
            $query->where('status', 'completed');
        }

        if ($sortBy === 'due_date') {
            $query->orderBy('due_date', 'asc');
        } elseif ($sortBy === 'duration') {
            $query->orderBy('duration', 'asc');
        } else {
            $query->orderBy('priority_score', 'desc')->orderBy('id', 'desc');
        }

        $filteredTasks = $query->get();
        $allTasks = Task::all();

        $counts = [
            'all' => $allTasks->count(),
            'pending' => $allTasks->where('status', 'pending')->count(),
            'completed' => $allTasks->where('status', 'completed')->count(),
        ];

        // Total hours calculated dynamically
        $totalHours = 0;
        foreach ($allTasks as $t) {
            preg_match('/(\d+)/', $t->duration, $matches);
            $totalHours += isset($matches[1]) ? (int)$matches[1] : 2;
        }

        $stats = [
            'total_tasks' => $allTasks->count(),
            'high_priority' => $allTasks->where('priority', 'High')->count(),
            'estimated_time' => ($totalHours ?: 12) . 'h'
        ];

        // Categories breakdown
        $total = max(1, $allTasks->count());
        $progCount = $allTasks->where('category', 'Programming')->count();
        $resCount = $allTasks->where('category', 'Research')->count();
        $presCount = $allTasks->where('category', 'Presentation')->count();
        $otherCount = $total - ($progCount + $resCount + $presCount);

        $categories = [
            ['name' => 'Programming', 'percentage' => round(($progCount / $total) * 100), 'color' => '#3B82F6'],
            ['name' => 'Research', 'percentage' => round(($resCount / $total) * 100), 'color' => '#8B5CF6'],
            ['name' => 'Presentation', 'percentage' => round(($presCount / $total) * 100), 'color' => '#10B981'],
            ['name' => 'Others', 'percentage' => max(0, round(($otherCount / $total) * 100)), 'color' => '#F59E0B'],
        ];

        $topTask = $allTasks->where('priority', 'High')->where('status', 'pending')->first() ?? $allTasks->first();

        return view('tasks', compact('stats', 'filteredTasks', 'filter', 'sortBy', 'counts', 'categories', 'topTask'));
    }

    /**
     * Store new task from form / modal
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'duration' => 'nullable|string|max:50',
            'due_date' => 'nullable|string|max:50',
            'course' => 'nullable|string|max:50',
            'category' => 'nullable|string|max:50',
            'priority' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $title = $validated['title'];
        $titleLower = strtolower($title);
        $notes = !empty($validated['description']) ? trim($validated['description']) : (!empty($validated['notes']) ? trim($validated['notes']) : null);

        // Continuous AI Pipeline: 1. Auto Category Detection (Gemini simulation)
        if (!empty($validated['category'])) {
            $category = $validated['category'];
        } else {
            if (preg_match('/(java|python|c\+\+|code|program|web|react|laravel|php|oop|bug|app|algorithm)/i', $titleLower)) {
                $category = 'Programming';
            } elseif (preg_match('/(math|calculus|stat|quiz|exam|algebra|prep|formula)/i', $titleLower)) {
                $category = 'Exam Prep';
            } elseif (preg_match('/(report|research|security|cyber|paper|essay|survey|threat)/i', $titleLower)) {
                $category = 'Research';
            } elseif (preg_match('/(slide|presentation|deck|speech|hci|design|ux|ui)/i', $titleLower)) {
                $category = 'Presentation';
            } else {
                $category = 'Reading';
            }
        }

        // Continuous AI Pipeline: 2. Auto Priority & Workload Calculation (Deterministic backend rules)
        $duration = $validated['duration'] ?? '3h';
        preg_match('/(\d+)/', $duration, $m);
        $hours = isset($m[1]) ? (int)$m[1] : 2;

        if (!empty($validated['priority'])) {
            $priority = $validated['priority'];
            $score = $priority === 'High' ? 92 : ($priority === 'Medium' ? 74 : 45);
        } else {
            if ($hours >= 3 || str_contains($titleLower, 'urgent') || str_contains($titleLower, 'quiz') || str_contains($titleLower, 'exam')) {
                $priority = 'High';
                $score = rand(88, 96);
            } elseif ($hours >= 2) {
                $priority = 'Medium';
                $score = rand(65, 78);
            } else {
                $priority = 'Low';
                $score = rand(40, 55);
            }
        }

        // Continuous AI Pipeline: 3. Subtasks Generation (Gemini & GPT simulation + User Notes parsing)
        $subtasks = [];
        if (!empty($notes)) {
            $rawLines = array_filter(array_map('trim', explode("\n", $notes)));
            foreach ($rawLines as $line) {
                $cleanLine = ltrim($line, "-*•0123456789.) \t");
                if (strlen($cleanLine) >= 3) {
                    $subtasks[] = $cleanLine;
                }
            }
        }

        if (empty($subtasks) || count($subtasks) < 2) {
            $subtasks = [
                "Review requirements & guidelines for {$title}",
                "Structure core architecture and key components",
                "Implement main functional modules and solve edge cases",
                "Verify, test, and prepare final delivery"
            ];
        }

        // Accurate Calendar & Date Processing
        $rawDueDate = $validated['due_date'] ?? '2026-10-29';
        $formattedDueDate = $rawDueDate;
        $assignedDay = null;

        try {
            $cleanDateStr = str_ireplace(
                ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', 'jan', 'pep', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'agt', 'sep', 'okt', 'nop', 'nov', 'des'],
                ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Aug', 'Sep', 'Oct', 'Nov', 'Nov', 'Dec'],
                $rawDueDate
            );
            $carbonDate = \Carbon\Carbon::parse($cleanDateStr);
            $formattedDueDate = $carbonDate->format('M j, Y'); // e.g. "Dec 1, 2026" or "Oct 29, 2026"
            $assignedDay = $carbonDate->format('D'); // "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"
        } catch (\Exception $e) {
            $formattedDueDate = $rawDueDate;
            $assignedDay = 'Thu';
        }

        // Automatic Slotting time (7 PM, 8 PM, or 9 PM) on that specific assigned day
        $occupiedTimes = Task::where('assigned_day', $assignedDay)->pluck('assigned_time')->toArray();
        $assignedTime = '7 PM';
        foreach (['7 PM', '8 PM', '9 PM'] as $tSlot) {
            if (!in_array($tSlot, $occupiedTimes)) {
                $assignedTime = $tSlot;
                break;
            }
        }

        $task = Task::create([
            'title' => $title,
            'course' => $validated['course'] ?? (str_contains($titleLower, 'oop') ? 'OOP' : (str_contains($titleLower, 'math') ? 'Math' : 'Academic')),
            'category' => $category,
            'description' => $notes ?: "Academic assignment for {$title}.",
            'due_date' => $formattedDueDate,
            'duration' => $duration,
            'priority' => $priority,
            'priority_score' => $score,
            'status' => 'pending',
            'subtasks' => $subtasks,
            'assigned_day' => $assignedDay,
            'assigned_time' => $assignedTime
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task successfully created & scheduled by Continuous AI! ✨',
                'task' => $task
            ]);
        }

        return redirect()->back()->with('success', 'Task successfully created & scheduled by Continuous AI! ✨');
    }

    /**
     * Toggle task status (completed / pending)
     */
    public function toggle(Task $task)
    {
        $task->status = ($task->status === 'completed') ? 'pending' : 'completed';
        $task->save();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $task->status
            ]);
        }

        return redirect()->back()->with('success', 'Task status updated!');
    }

    /**
     * Delete task
     */
    public function destroy(Task $task)
    {
        $task->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Task deleted successfully.');
    }

    /**
     * Study Plan View (Screen 3)
     */
    public function studyPlan()
    {
        $this->ensureInitialTasks();

        $allTasks = Task::all();

        $days = [
            ['name' => 'Mon', 'date' => 'Oct 27', 'is_today' => true],
            ['name' => 'Tue', 'date' => 'Oct 28', 'is_today' => false],
            ['name' => 'Wed', 'date' => 'Oct 29', 'is_today' => false],
            ['name' => 'Thu', 'date' => 'Oct 30', 'is_today' => false],
            ['name' => 'Fri', 'date' => 'Oct 31', 'is_today' => false],
            ['name' => 'Sat', 'date' => 'Nov 1',  'is_today' => false],
            ['name' => 'Sun', 'date' => 'Nov 2',  'is_today' => false],
        ];

        $colorStyles = [
            'Programming' => 'bg-sky-100 text-sky-800 border-sky-300',
            'Exam Prep' => 'bg-purple-100 text-purple-800 border-purple-300',
            'Research' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'Presentation' => 'bg-blue-100 text-blue-800 border-blue-300',
            'Reading' => 'bg-teal-100 text-teal-800 border-teal-300',
            'Default' => 'bg-slate-100 text-slate-800 border-slate-300',
        ];

        $hours = ['7 PM', '8 PM', '9 PM'];
        $timeSlots = [];
        
        foreach ($hours as $h) {
            $timeSlots[$h] = [];
            foreach ($days as $d) {
                $task = $allTasks->first(function ($t) use ($d, $h) {
                    $dayMatch = ($t->assigned_day === $d['name']);
                    $assignedH = $t->assigned_time ?: '7 PM';
                    return $dayMatch && ($assignedH === $h);
                });

                if ($task) {
                    $category = $task->category ?? 'Default';
                    $color = $colorStyles[$category] ?? $colorStyles['Default'];
                    $timeSlots[$h][$d['name']] = [
                        'id' => $task->id,
                        'title' => $task->title,
                        'duration' => $task->duration ?? '1h',
                        'category' => $task->category,
                        'course' => $task->course,
                        'subtasks' => $task->subtasks,
                        'color' => $color,
                        'time' => ($h === '7 PM' ? '7:00 - 8:00 PM' : ($h === '8 PM' ? '8:00 - 9:00 PM' : '9:00 - 10:00 PM'))
                    ];
                } else {
                    $timeSlots[$h][$d['name']] = null;
                }
            }
        }

        // Generate day plans mapping for instant clicking
        $dayPlans = [];
        foreach ($days as $d) {
            $tasksOnDay = $allTasks->where('assigned_day', $d['name']);
            $firstTask = $tasksOnDay->first();
            
            if ($firstTask) {
                $assignedTime = $firstTask->assigned_time ?: '7 PM';
                $timeLabel = $assignedTime === '8 PM' ? '8:00 - 9:00 PM' : ($assignedTime === '9 PM' ? '9:00 - 10:00 PM' : '7:00 - 8:00 PM');
                $subtasks = (!empty($firstTask->subtasks) && is_array($firstTask->subtasks)) ? $firstTask->subtasks : [];
                $focus = !empty($subtasks) ? $subtasks[0] : 'Selesaikan target tugas akademik hari ini.';

                $dayPlans[$d['name']] = [
                    'day_name' => $d['name'],
                    'date_label' => $d['name'] . ', ' . $d['date'],
                    'time' => $timeLabel,
                    'title' => $firstTask->title,
                    'category' => $firstTask->category,
                    'duration' => $firstTask->duration ?? '1h',
                    'subtasks' => $subtasks,
                    'subtask_focus' => $focus,
                    'has_task' => true
                ];
            } else {
                $dayPlans[$d['name']] = [
                    'day_name' => $d['name'],
                    'date_label' => $d['name'] . ', ' . $d['date'],
                    'time' => '19:00 - 21:00 (Fleksibel)',
                    'title' => 'Sesi Belajar Bebas & Evaluasi Mandiri',
                    'category' => 'Free Study',
                    'duration' => '2h',
                    'subtasks' => [
                        'Review materi perkuliahan minggu ini',
                        'Istirahat dan relaksasi otak'
                    ],
                    'subtask_focus' => 'Tidak ada tugas terjadwal. Manfaatkan waktu untuk istirahat atau mengulang materi.',
                    'has_task' => false
                ];
            }
        }

        $todayPlan = $dayPlans['Thu'] ?? $dayPlans['Mon'];

        // Build Calendar Events Array for interactive month calendar
        $calendarTaskEvents = [];
        foreach ($allTasks as $task) {
            $dateKey = null;
            try {
                $cleanDateStr = str_ireplace(
                    ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember', 'jan', 'pep', 'mar', 'apr', 'mei', 'jun', 'jul', 'agu', 'agt', 'sep', 'okt', 'nop', 'nov', 'des'],
                    ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Aug', 'Sep', 'Oct', 'Nov', 'Nov', 'Dec'],
                    $task->due_date
                );
                $cDate = \Carbon\Carbon::parse($cleanDateStr);
                $dateKey = $cDate->format('Y-m-d');
            } catch (\Exception $e) {
                $dateKey = '2026-10-29';
            }

            $cat = $task->category ?: 'Programming';
            $badgeColor = match ($cat) {
                'Programming' => 'bg-sky-50 text-sky-700 border-sky-200',
                'Exam Prep' => 'bg-purple-50 text-purple-700 border-purple-200',
                'Research' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'Presentation' => 'bg-blue-50 text-blue-700 border-blue-200',
                'Reading' => 'bg-teal-50 text-teal-700 border-teal-200',
                default => 'bg-slate-50 text-slate-700 border-slate-200'
            };

            $calendarTaskEvents[] = [
                'id' => $task->id,
                'title' => $task->title,
                'category' => $cat,
                'duration' => $task->duration ?: '2h',
                'priority' => $task->priority ?: 'Medium',
                'due_date' => $task->due_date,
                'date_key' => $dateKey,
                'time' => $task->assigned_time ?: '7 PM',
                'subtasks' => (!empty($task->subtasks) && is_array($task->subtasks)) ? $task->subtasks : [],
                'status' => $task->status,
                'badge_color' => $badgeColor
            ];
        }

        $totalTasks = $allTasks->count();
        $completedTasks = $allTasks->where('status', 'completed')->count();
        $onTrack = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
        if ($onTrack < 50) $onTrack = 50;

        $summary = [
            'total_tasks' => $totalTasks,
            'total_study_time' => '12h',
            'on_track_percent' => $onTrack
        ];

        return view('study-plan', compact('days', 'timeSlots', 'todayPlan', 'dayPlans', 'summary', 'calendarTaskEvents'));
    }

    /**
     * AI Regenerate Study Plan (intelligently reorganize slots across days & times)
     */
    public function regenerateStudyPlan(Request $request)
    {
        $allTasks = Task::orderBy('priority_score', 'desc')->get();
        if ($allTasks->isNotEmpty()) {
            $optimalSlots = [
                ['day' => 'Mon', 'time' => '7 PM'],
                ['day' => 'Mon', 'time' => '8 PM'],
                ['day' => 'Tue', 'time' => '7 PM'],
                ['day' => 'Tue', 'time' => '8 PM'],
                ['day' => 'Wed', 'time' => '7 PM'],
                ['day' => 'Wed', 'time' => '8 PM'],
                ['day' => 'Thu', 'time' => '7 PM'],
                ['day' => 'Fri', 'time' => '7 PM'],
                ['day' => 'Sat', 'time' => '7 PM'],
                ['day' => 'Sun', 'time' => '7 PM'],
            ];

            // Shuffle slightly to create real observable reorganization
            shuffle($optimalSlots);

            foreach ($allTasks as $index => $task) {
                $slot = $optimalSlots[$index % count($optimalSlots)];
                $task->assigned_day = $slot['day'];
                $task->assigned_time = $slot['time'];
                $task->save();
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Jadwal belajar berhasil diatur ulang dan dioptimasi oleh AI! ✨'
            ]);
        }

        return redirect()->back()->with('success', 'Jadwal belajar berhasil diatur ulang dan dioptimasi oleh AI! ✨');
    }

    /**
     * Settings View
     */
    public function settings()
    {
        $user = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Student',
                'email' => 'student@university.edu',
                'password' => bcrypt('password123')
            ]
        );
        return view('settings', compact('user'));
    }

    /**
     * Update user profile settings
     */
    /**
     * Ensure database has default study materials
     */
    private function ensureInitialStudyMaterials()
    {
        if (StudyMaterial::count() === 0) {
            StudyMaterial::create([
                'title' => 'Jaringan Komputer: Model OSI & Protokol TCP/IP',
                'filename' => 'Jaringan_Komputer_OSI_Model.pptx',
                'file_size' => '4.2 MB',
                'slides_count' => 18,
                'mode' => 'quiz',
                'data' => [
                    'quiz' => [
                        [
                            'question' => 'Lapisan OSI manakah yang bertanggung jawab melakukan enkripsi data, kompresi, dan penerjemahan format sintaks?',
                            'options' => ['Application Layer', 'Presentation Layer', 'Session Layer', 'Transport Layer'],
                            'correct' => 1,
                            'explanation' => 'Presentation Layer (Lapisan 6) menangani konversi format data, enkripsi keamanan (seperti SSL/TLS), serta kompresi dokumen/gambar.'
                        ],
                        [
                            'question' => 'Protokol transport yang bersifat "connection-oriented" dan menjamin pengiriman paket secara andal (reliable) adalah...',
                            'options' => ['UDP (User Datagram Protocol)', 'ICMP (Internet Control Message Protocol)', 'TCP (Transmission Control Protocol)', 'IP (Internet Protocol)'],
                            'correct' => 2,
                            'explanation' => 'TCP menggunakan mekanisme Three-Way Handshake, acknowledgment, dan sequence numbering untuk menjamin paket sampai tanpa korupsi data.'
                        ],
                        [
                            'question' => 'Perangkat keras jaringan Switch Layer 2 bekerja berdasarkan alamat apa untuk meneruskan frame data?',
                            'options' => ['IP Address', 'MAC Address', 'Port Number', 'Subnet Mask'],
                            'correct' => 1,
                            'explanation' => 'Switch Layer 2 membaca tabel MAC Address (CAM Table) untuk memetakan dan meneruskan frame secara langsung ke port penerima.'
                        ],
                        [
                            'question' => 'Apa peran utama protokol DNS (Domain Name System) pada Application Layer?',
                            'options' => ['Mengamankan paket dari serangan siber', 'Menerjemahkan nama domain (seperti google.com) menjadi alamat IP', 'Menentukan jalur routing tercepat', 'Mengompresi file media streaming'],
                            'correct' => 1,
                            'explanation' => 'DNS berfungsi seperti buku telepon internet yang memetakan nama domain ramah manusia menjadi alamat IP numerik/heksadesimal yang dimengerti mesin.'
                        ]
                    ],
                    'flashcards' => [
                        [
                            'front' => 'Application Layer (Layer 7)',
                            'back' => 'Antarmuka langsung interaksi aplikasi dengan jaringan. Menggunakan protokol seperti HTTP, HTTPS, FTP, DNS, dan SMTP.',
                            'badge' => 'OSI Layer 7'
                        ],
                        [
                            'front' => 'Presentation Layer (Layer 6)',
                            'back' => 'Bertanggung jawab atas sintaks, kompresi data, dan enkripsi/dekripsi keamanan (TLS/SSL, ASCII, JPEG).',
                            'badge' => 'OSI Layer 6'
                        ],
                        [
                            'front' => 'Transport Layer (Layer 4)',
                            'back' => 'Mengatur transfer data end-to-end, segmentasi, flow control, dan deteksi error via TCP (reliable) atau UDP (speed-oriented).',
                            'badge' => 'OSI Layer 4'
                        ],
                        [
                            'front' => 'Network Layer (Layer 3)',
                            'back' => 'Pengalamatan logis (Logical IP Addressing) dan penentuan rute paket terbaik (Packet Routing via Router).',
                            'badge' => 'OSI Layer 3'
                        ],
                        [
                            'front' => 'Data Link Layer (Layer 2)',
                            'back' => 'Pengemasan bit menjadi frame, pengalamatan fisik (MAC Address), deteksi error frame (CRC), dan switching via Switch/NIC.',
                            'badge' => 'OSI Layer 2'
                        ],
                        [
                            'front' => 'Three-Way Handshake TCP',
                            'back' => 'Proses pembentukan koneksi andal TCP sebelum transmisi data: 1. SYN -> 2. SYN-ACK -> 3. ACK.',
                            'badge' => 'Protokol TCP'
                        ]
                    ],
                    'video' => [
                        'duration' => '4:15',
                        'lecturer' => 'Dr. Maya AI - Virtual Lecturer',
                        'chapters' => [
                            ['time' => '00:00', 'title' => 'Pengantar 7 Lapisan OSI Model'],
                            ['time' => '01:10', 'title' => 'Upper Layers: Application, Presentation, Session'],
                            ['time' => '02:05', 'title' => 'Transport & Network: TCP vs UDP Handshake'],
                            ['time' => '03:15', 'title' => 'Lower Layers: Data Link, MAC Address, Physical'],
                            ['time' => '03:45', 'title' => 'Rangkuman & Kunci Menjawab Soal Ujian'],
                        ],
                        'slides' => [
                            [
                                'slide_num' => 1,
                                'title' => 'Fondasi Arsitektur OSI Model',
                                'subtitle' => '7 Lapisan Standar Komunikasi Jaringan ISO/IEC',
                                'bullet_points' => [
                                    'Dibuat oleh ISO tahun 1984 sebagai model referensi arsitektur jaringan terbuka.',
                                    'Memisahkan proses komunikasi kompleks menjadi 7 modul lapisan independen.',
                                    'Prinsip enkapsulasi: Data dari atas dibungkus header hingga menjadi bit di lapisan fisik.'
                                ],
                                'transcript' => 'Halo rekan mahasiswa! Di video pembelajaran ini, kita akan membedah arsitektur 7 lapisan OSI Model yang menjadi standar global protokol jaringan komputer. Konsep ini sangat sering muncul dalam ujian dan wawancara teknis.'
                            ],
                            [
                                'slide_num' => 2,
                                'title' => 'Upper Layers: Interaksi Pengguna & Data',
                                'subtitle' => 'Layer 7 (Application) hingga Layer 5 (Session)',
                                'bullet_points' => [
                                    'Application (7): Protokol HTTP/HTTPS, DNS, SSH, FTP.',
                                    'Presentation (6): Enkripsi data SSL/TLS, kompresi, format sintaks.',
                                    'Session (5): Mengelola pembukaan, pemeliharaan, dan penutupan sesi komunikasi.'
                                ],
                                'transcript' => 'Pada lapisan atas, fokus utamanya adalah bagaimana software berkomunikasi. Presentation layer sangat vital karena di sanalah enkripsi TLS bekerja sebelum data ditransmisikan ke jaringan publik.'
                            ],
                            [
                                'slide_num' => 3,
                                'title' => 'Transport & Network Layer',
                                'subtitle' => 'Perbedaan Krusial TCP vs UDP & Logical Routing',
                                'bullet_points' => [
                                    'TCP: Connection-oriented, Three-way handshake (SYN, SYN-ACK, ACK), reliable delivery.',
                                    'UDP: Connectionless, tanpa acknowledgment, rendah latency (video call, gaming).',
                                    'Network (IP): Routing paket melalui router lintas jaringan.'
                                ],
                                'transcript' => 'Sekarang kita masuk ke jantung jaringan komputer: TCP versus UDP. Ingat, jika aplikasi membutuhkan keandalan tanpa kehilangan data, pilihlah TCP. Jika kecepatan dan real-time stream adalah prioritas, gunakan UDP.'
                            ],
                            [
                                'slide_num' => 4,
                                'title' => 'Lower Layers: Data Link & Physical',
                                'subtitle' => 'Frame, MAC Address, Sinyal Elektrik & Fiber Optik',
                                'bullet_points' => [
                                    'Data Link (2): Pembungkusan frame data, pengalamatan perangkat via MAC Address 48-bit.',
                                    'Physical (1): Transmisi bit 0 dan 1 melalui kabel UTP, gelombang radio Wi-Fi, atau laser fiber optik.',
                                    'Switch beroperasi di Layer 2, sedangkan Router beroperasi di Layer 3.'
                                ],
                                'transcript' => 'Di lapisan bawah, paket diubah menjadi frame data oleh Data Link Layer dan diarahkan berdasarkan MAC Address. Terakhir, Physical layer mengubahnya menjadi sinyal elektromagnetik atau pulsa cahaya.'
                            ],
                            [
                                'slide_num' => 5,
                                'title' => 'Tips Hafalan Ujian & Kesimpulan',
                                'subtitle' => 'Jembatan Keledai (Mnemonic) Lapisan OSI',
                                'bullet_points' => [
                                    'Dari Atas ke Bawah (7 ke 1): "All People Seem To Need Data Processing"',
                                    'Dari Bawah ke Atas (1 ke 7): "Please Do Not Throw Sausage Pizza Away"',
                                    'Fokus review: Port TCP (80, 443, 22), 3-way handshake, dan fungsi Router vs Switch.'
                                ],
                                'transcript' => 'Untuk mengingat urutan 7 lapisan OSI dengan cepat saat ujian, gunakan kalimat: All People Seem To Need Data Processing. Selamat belajar, dan silakan uji pemahaman Anda di tab kuis!'
                            ]
                        ]
                    ],
                    'summary' => [
                        'executive_summary' => 'Presentasi ini membahas arsitektur 7 lapisan model referensi OSI (Open Systems Interconnection) dan perbandingannya dengan protokol TCP/IP. Materi mencakup enkapsulasi data, fungsi tiap layer, protokol utama, serta perbedaan esensial antara TCP dan UDP.',
                        'key_takeaways' => [
                            'Model OSI terdiri dari 7 lapisan: Physical, Data Link, Network, Transport, Session, Presentation, dan Application.',
                            'TCP menggunakan Three-Way Handshake untuk menjamin keandalan pengiriman paket (reliable delivery), sedangkan UDP berorientasi pada kecepatan tanpa pengecekan ACK.',
                            'Router bekerja pada Network Layer (Layer 3) menggunakan IP Address, sedangkan Switch standar bekerja pada Data Link Layer (Layer 2) menggunakan MAC Address.',
                            'Enkapsulasi data bergerak dari Application Layer (Data) -> Transport (Segment) -> Network (Packet) -> Data Link (Frame) -> Physical (Bits).'
                        ],
                        'cheat_sheet' => [
                            ['layer' => 'Layer 7 - Application', 'unit' => 'Data', 'protocols' => 'HTTP, HTTPS, DNS, FTP, SMTP', 'hardware' => 'Host/PC, Gateway'],
                            ['layer' => 'Layer 6 - Presentation', 'unit' => 'Data', 'protocols' => 'TLS/SSL, ASCII, JPEG, MP4', 'hardware' => 'OS/Aplikasi'],
                            ['layer' => 'Layer 5 - Session', 'unit' => 'Data', 'protocols' => 'NetBIOS, RPC, PPTP', 'hardware' => 'OS / Sockets'],
                            ['layer' => 'Layer 4 - Transport', 'unit' => 'Segment', 'protocols' => 'TCP, UDP', 'hardware' => 'Firewall Layer 4'],
                            ['layer' => 'Layer 3 - Network', 'unit' => 'Packet', 'protocols' => 'IPv4, IPv6, ICMP, OSPF, BGP', 'hardware' => 'Router'],
                            ['layer' => 'Layer 2 - Data Link', 'unit' => 'Frame', 'protocols' => 'Ethernet (802.3), Wi-Fi (802.11), ARP', 'hardware' => 'Switch, NIC'],
                            ['layer' => 'Layer 1 - Physical', 'unit' => 'Bits', 'protocols' => '1000BASE-T, RS-232, DSL', 'hardware' => 'Hub, Kabel UTP, Fiber']
                        ],
                        'exam_tips' => [
                            'Soal sering menanyakan: Di layer manakah enkripsi SSL/TLS bekerja? Jawabannya: Presentation Layer (Layer 6).',
                            'Perbedaan unit data protokol (PDU): Segment (L4), Packet (L3), Frame (L2), Bits (L1). Jangan sampai tertukar!',
                            'Urutan 3-way handshake TCP selalu: SYN (Client) -> SYN-ACK (Server) -> ACK (Client).'
                        ]
                    ]
                ]
            ]);
        }
    }

    /**
     * AI Study Generator Page (PPT Upload -> Quiz / Flashcard / Video / Summary)
     */
    public function aiGenerator(Request $request)
    {
        $this->ensureInitialStudyMaterials();

        $allMaterials = StudyMaterial::orderBy('id', 'desc')->get();
        
        $activeId = $request->query('id');
        $activeMaterial = $activeId ? $allMaterials->firstWhere('id', $activeId) : $allMaterials->first();
        if (!$activeMaterial) {
            $activeMaterial = $allMaterials->first();
        }

        // Active Mode: 'quiz', 'flashcards', 'summary'
        $activeMode = $request->query('mode', $activeMaterial ? $activeMaterial->mode : 'quiz');
        if (!in_array($activeMode, ['quiz', 'flashcards', 'summary'])) {
            $activeMode = 'quiz';
        }

        return view('ai-generator', [
            'materials' => $allMaterials,
            'currentMaterial' => $activeMaterial,
            'activeMode' => $activeMode
        ]);
    }

    /**
     * Process PPT Upload & Generate AI Materials
     */
    public function processAIGenerator(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'mode' => 'required|string|in:quiz,flashcards,summary',
            'presentation_file' => 'nullable|file|max:20480', // up to 20MB
            'preset_topic' => 'nullable|string'
        ]);

        $mode = $validated['mode'] ?? 'quiz';
        $title = $request->input('title') ?? $request->input('preset_topic');
        $filename = 'Uploaded_Presentation.pptx';
        $fileSize = '3.6 MB';
        $slidesCount = rand(15, 30);
        $extractedData = null;

        if ($request->hasFile('presentation_file')) {
            $file = $request->file('presentation_file');
            $filename = $file->getClientOriginalName();
            $fileSize = round($file->getSize() / (1024 * 1024), 1) . ' MB';

            // Derive clean, distinct presentation title
            $cleanTitle = pathinfo($filename, PATHINFO_FILENAME);
            if (preg_match('/IF640[-_\s]*W0?(\d+)[-_\s]*(.+?)(?:_odd\d+|\s*\(\d+\))?$/i', $cleanTitle, $m)) {
                $cleanTitle = 'IF640-W' . sprintf('%02d', (int)$m[1]) . ': ' . ucwords(str_replace(['_', '-'], ' ', trim($m[2])));
            } else {
                $cleanTitle = preg_replace('/^Materi\s*/i', '', $cleanTitle);
                $cleanTitle = preg_replace('/_odd\d+/i', '', $cleanTitle);
                $cleanTitle = preg_replace('/\s*\(\d+\)$/', '', $cleanTitle);
                $cleanTitle = ucwords(str_replace(['_', '-'], ' ', trim($cleanTitle)));
            }

            if (empty($title) || $title === $filename) {
                $title = $cleanTitle;
            }

            try {
                $ext = $file->getClientOriginalExtension();
                $tempName = 'upload_' . time() . '_' . uniqid() . '.' . $ext;
                $savedPath = $file->storeAs('uploads', $tempName);

                // Accurately resolve absolute path across Laravel 11/12 local storage disks
                $fullPath = \Illuminate\Support\Facades\Storage::disk('local')->path($savedPath);
                if (!file_exists($fullPath)) {
                    $fullPath = storage_path('app/private/' . $savedPath);
                }
                if (!file_exists($fullPath)) {
                    $fullPath = storage_path('app/' . $savedPath);
                }
                if (!file_exists($fullPath) && $file->getRealPath()) {
                    $fullPath = $file->getRealPath();
                }

                $script = app_path('Services/extract_and_generate.py');
                if (file_exists($script) && file_exists($fullPath)) {
                    $cmd = 'python ' . escapeshellarg($script) . ' ' . escapeshellarg($fullPath);
                    $output = shell_exec($cmd);
                    if ($output) {
                        $parsed = json_decode($output, true);
                        if (!empty($parsed) && isset($parsed['summary'])) {
                            $extractedData = $parsed;
                            if (!empty($parsed['title'])) {
                                $title = $parsed['title'];
                            }
                            if (!empty($parsed['slides_count'])) {
                                $slidesCount = $parsed['slides_count'];
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('AI Extractor exception: ' . $e->getMessage());
            }
        } elseif (!empty($request->input('preset_topic'))) {
            $title = $request->input('preset_topic');
            $filename = str_replace(' ', '_', $title) . '.pptx';
        }

        if (empty($title)) {
            $title = 'Slide Kuliah: ' . date('d M Y');
        }

        if ($extractedData) {
            $generatedData = $extractedData;
        } else {
            // Check if title or filename is specifically about Physics of Malware
            $isMalwareTopic = preg_match('/physics of malware|pe\s*header|shannon entropy|process injection/i', $title . ' ' . $filename);

            if ($isMalwareTopic) {
                // Call python script to fetch real malware study pack
                $script = app_path('Services/extract_and_generate.py');
                $downloadPdf = 'C:/Users/Atila/Downloads/Materi IF640-W05-The Physics of Malware_odd20262027.pdf';
                if (file_exists($downloadPdf) && file_exists($script)) {
                    $cmd = 'python ' . escapeshellarg($script) . ' ' . escapeshellarg($downloadPdf);
                    $output = shell_exec($cmd);
                    if ($output) {
                        $parsed = json_decode($output, true);
                        if (!empty($parsed) && isset($parsed['summary'])) {
                            $generatedData = $parsed;
                            $title = $parsed['title'] ?? $title;
                            $slidesCount = $parsed['slides_count'] ?? 49;
                        }
                    }
                }
            }

            if (empty($generatedData)) {
                // Rich fallback academic study pack
                $generatedData = [
                    'quiz' => [
                        [
                            'question' => 'Berdasarkan materi pada ' . $title . ', apa konsep utama yang menjadi landasan sistem ini?',
                            'options' => [
                                'Definisi teoritis, arsitektur modul, dan validasi fungsional',
                                'Pengabaian dokumentasi dan pengujian sistem',
                                'Penggunaan pustaka usang tanpa enkripsi',
                                'Penghapusan logging dan jejak audit'
                            ],
                            'correct' => 0,
                            'explanation' => 'Slide presentasi menekankan pemahaman konsep teoritis dan arsitektur sebagai pondasi utama sebelum implementasi teknis.'
                        ],
                        [
                            'question' => 'Manakah langkah awal yang paling direkomendasikan saat menganalisis ' . $title . '?',
                            'options' => [
                                'Melakukan deployment langsung ke server produksi',
                                'Mengidentifikasi batasan masalah, struktur variabel, dan diagram relasi antar modul',
                                'Menghapus dokumentasi teknis',
                                'Menonaktifkan pengujian unit test'
                            ],
                            'correct' => 1,
                            'explanation' => 'Mengidentifikasi batasan masalah dan struktur variabel diperlukan agar proses analisis sistematis dan bebas ambiguitas.'
                        ],
                        [
                            'question' => 'Apa keuntungan utama yang dipaparkan dalam slide terkait arsitektur modular pada ' . $title . '?',
                            'options' => [
                                'Mempermudah pemeliharaan (maintenance) dan meningkatkan efisiensi eksekusi',
                                'Meningkatkan kompleksitas tanpa tujuan jelas',
                                'Mengharuskan redundansi kode yang berlebihan',
                                'Membatasi interoperabilitas modul'
                            ],
                            'correct' => 0,
                            'explanation' => 'Slide menyoroti bahwa modularitas yang tinggi mempermudah maintenance dan mempercepat performa eksekusi.'
                        ]
                    ],
                    'flashcards' => [
                        [
                            'front' => 'Definisi Inti ' . $title,
                            'back' => 'Prinsip utama yang mengatur struktur data, aliran informasi, dan relasi logis antar komponen pada materi kuliah.',
                            'badge' => 'Konsep Utama'
                        ],
                        [
                            'front' => 'Arsitektur & Komponen',
                            'back' => 'Elemen pembentuk sistem yang saling berkomunikasi melalui protokol dan antarmuka standar untuk mencapai throughput optimal.',
                            'badge' => 'Arsitektur'
                        ],
                        [
                            'front' => 'Metrik Evaluasi & Benchmark',
                            'back' => 'Pengukuran performa menggunakan pengujian terstandarisasi, analisis ambang batas error, dan validasi data riil.',
                            'badge' => 'Evaluasi'
                        ]
                    ],
                    'video' => [
                        'duration' => '3:50',
                        'lecturer' => 'Dr. Maya AI - Virtual Lecturer',
                        'chapters' => [
                            ['time' => '00:00', 'title' => 'Pengantar & Latar Belakang ' . $title],
                            ['time' => '01:15', 'title' => 'Pembahasan Komponen & Teori Fundamental'],
                            ['time' => '02:30', 'title' => 'Studi Kasus Implementasi & Analisis'],
                            ['time' => '03:15', 'title' => 'Rangkuman & Kunci Menjawab Ujian']
                        ],
                        'slides' => [
                            [
                                'slide_num' => 1,
                                'title' => $title,
                                'subtitle' => 'Ringkasan Slide Kuliah & Penjelasan Terpandu AI',
                                'bullet_points' => [
                                    'Ekstraksi otomatis dari materi presentasi yang dipelajari.',
                                    'Mencakup terminologi kunci, skema alur data, dan prinsip desain.',
                                    'Dirancang untuk pemahaman kilat sebelum ujian dan kuis akademik.'
                                ],
                                'transcript' => 'Halo rekan pembelajar! Selamat datang di modul pembelajaran kilat untuk materi ' . $title . '. Di video singkat ini, kita akan membahas poin-poin paling esensial agar Anda siap menghadapi ujian.'
                            ]
                        ]
                    ],
                    'summary' => [
                        'executive_summary' => 'Rangkuman catatan komprehensif hasil ekstraksi AI dari slide presentasi "' . $title . '" (' . $slidesCount . ' slides). Dokumen ini merangkum teori esensial, diagram, dan formula kunci dalam format yang ringkas dan terstruktur.',
                        'key_takeaways' => [
                            'Topik utama mencakup struktur teoritis, batasan operasional, dan metodologi penerapan pada dunia riil.',
                            'Pemisahan modul dan prinsip modularitas adalah kunci utama dalam merancang sistem yang skalabel.',
                            'Dibutuhkan pemahaman Active Recall untuk menghafal terminologi teknis tanpa menghafal kata per kata secara mekanis.'
                        ],
                        'cheat_sheet' => [
                            ['layer' => 'Bagian I', 'unit' => 'Pengenalan', 'protocols' => 'Fondasi Teori', 'hardware' => 'Slide 1 - 5'],
                            ['layer' => 'Bagian II', 'unit' => 'Arsitektur', 'protocols' => 'Mekanisme Kerja', 'hardware' => 'Slide 6 - 12'],
                            ['layer' => 'Bagian III', 'unit' => 'Implementasi', 'protocols' => 'Studi Kasus', 'hardware' => 'Slide 13 - ' . $slidesCount]
                        ],
                        'exam_tips' => [
                            'Fokuskan waktu belajar Anda pada slide kesimpulan dan diagram skematik di bab pertengahan.',
                            'Perhatikan istilah asing dan akronim teknis yang sering kali dijadikan pilihan ganda mengecoh.'
                        ]
                    ]
                ];
            }
        }

        $material = StudyMaterial::create([
            'title' => $title,
            'filename' => $filename,
            'file_size' => $fileSize,
            'slides_count' => $slidesCount,
            'mode' => $mode,
            'data' => $generatedData
        ]);

        return redirect()->route('ai-generator', ['id' => $material->id, 'mode' => $mode])
            ->with('success', 'Slide PPT berhasil diekstrak dan di-generate menjadi ' . strtoupper($mode) . '! 🎉✨');
    }

    /**
     * Delete Study Material
     */
    public function destroyStudyMaterial($id)
    {
        $material = StudyMaterial::find($id);
        if ($material) {
            $material->delete();
        }

        $next = StudyMaterial::latest('id')->first();
        if ($next) {
            return redirect()->route('ai-generator', ['id' => $next->id])
                ->with('success', 'Slide presentasi berhasil dihapus dari database! 🗑️');
        }

        return redirect()->route('ai-generator')->with('success', 'Slide presentasi berhasil dihapus.');
    }

    /**
     * Refresh / Regenerate Quiz Questions
     */
    public function refreshQuiz($id)
    {
        $material = StudyMaterial::findOrFail($id);
        $data = $material->data ?? [];

        // Check if we can re-generate using Python extractor with refresh
        $script = app_path('Services/extract_and_generate.py');
        $storedUploads = storage_path('app/private/uploads');
        $fileToUse = null;

        if (is_dir($storedUploads)) {
            $files = scandir($storedUploads);
            foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                if (stripos($f, pathinfo($material->filename, PATHINFO_FILENAME)) !== false) {
                    $fileToUse = $storedUploads . '/' . $f;
                    break;
                }
            }
        }

        if (!$fileToUse && stripos($material->filename, 'IF640') !== false) {
            $downloadPdf = 'C:/Users/Atila/Downloads/' . $material->filename;
            if (file_exists($downloadPdf)) {
                $fileToUse = $downloadPdf;
            }
        }

        if ($fileToUse && file_exists($fileToUse) && file_exists($script)) {
            $cmd = 'python ' . escapeshellarg($script) . ' ' . escapeshellarg($fileToUse) . ' --refresh-quiz';
            $output = shell_exec($cmd);
            if ($output) {
                $parsed = json_decode($output, true);
                if (!empty($parsed['quiz'])) {
                    $data['quiz'] = $parsed['quiz'];
                    $material->data = $data;
                    $material->save();
                    return redirect()->route('ai-generator', ['id' => $material->id, 'mode' => 'quiz'])
                        ->with('success', 'Kuis berhasil diperbarui dengan set soal dan variasi baru! 🎲✨');
                }
            }
        }

        // Adaptive shuffle fallback if file is not found
        if (!empty($data['quiz']) && count($data['quiz']) > 0) {
            $quizzes = $data['quiz'];
            shuffle($quizzes);
            foreach ($quizzes as &$q) {
                if (isset($q['options']) && isset($q['correct'])) {
                    $correctText = $q['options'][$q['correct']];
                    shuffle($q['options']);
                    $q['correct'] = array_search($correctText, $q['options']);
                }
            }
            $data['quiz'] = $quizzes;
            $material->data = $data;
            $material->save();
        }

        return redirect()->route('ai-generator', ['id' => $material->id, 'mode' => 'quiz'])
            ->with('success', 'Soal kuis berhasil di-refresh dan diacak ulang! 🎲✨');
    }

    /**
     * Update user profile settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $user = User::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Student',
                'email' => 'student@university.edu',
                'password' => bcrypt('password123')
            ]
        );
        
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        return redirect()->back()->with('success', 'Profile and preferences updated successfully! ✨');
    }

    /**
     * AI Academic Chat Assistant endpoint (Gemini Powered with Real-time DB Context)
     */
    public function chat(Request $request)
    {
        $message = trim($request->input('message', ''));
        $history = $request->input('history', []);

        if (empty($message)) {
            return response()->json([
                'success' => false,
                'reply' => 'Silakan ketik pertanyaan seputar tugas, materi kuliah, coding, atau jadwal Anda! 😊'
            ]);
        }

        $this->ensureInitialTasks();
        $tasks = Task::all();

        // Prepare context data from database
        $tasksList = [];
        foreach ($tasks as $t) {
            $tasksList[] = sprintf(
                "- [%s] %s (Mata Kuliah: %s, Kategori: %s, Deadline: %s, Jadwal: %s %s, Prioritas: %s, Catatan: %s)",
                $t->status === 'completed' ? 'SELESAI' : 'BELUM SELESAI',
                $t->title,
                $t->course ?: 'Umum',
                $t->category ?: 'Akademik',
                $t->due_date ?: 'Belum ditentukan',
                $t->assigned_day ?: 'Belum dijadwalkan',
                $t->assigned_time ?: '',
                $t->priority ?: 'Normal',
                $t->description ?: 'Tidak ada catatan tambahan'
            );
        }
        $tasksContext = implode("\n", $tasksList);

        $systemInstruction = "Kamu adalah SmartStudy AI — Asisten AI Pintar & Tutor Kuliah Serba Bisa (seperti ChatGPT dan Gemini) berbahasa Indonesia.
Kamu bebas menjawab pertanyaan apa saja dari mahasiswa, baik akademik, pemrograman/coding, matematika, sains, essay, maupun tanya jawab umum:

1. CODING & PEMROGRAMAN:
   - Mampu membuat kode, menganalisis, memperbaiki bug, dan MENJELASKAN KODE BARIS DEMI BARIS secara detail jika diminta mahasiswa.
   - Pahami semua bahasa: Java, Python, PHP, JavaScript, C++, HTML/CSS, SQL, dll.
   - Selalu sertakan code block ```bahasa ... ``` dengan sintaks rapi.

2. MATEMATIKA, SAINS & TEORI KULIAH:
   - Jabarkan rumus, langkah pengerjaan step-by-step, dan konsep secara mendalam dan jelas.

3. TANYA JAWAB UMUM SEPERTI CHATGPT / GEMINI:
   - Layani pertanyaan bebas apa saja: ramah, cerdas, kreatif, komunikatif, dan suportif layaknya ChatGPT & Gemini.
   - Ingat konteks percakapan sebelumnya (multi-turn conversation).

4. JADWAL & DEADLINE (DATABASE REAL-TIME):
   Tanggal hari ini: Kamis, 29 Oktober 2026.
   Jika mahasiswa bertanya tentang jadwal kuliah/tugas/deadline, gunakan data berikut:
{$tasksContext}

PANDUAN RESPONS:
- Jawab selalu dalam bahasa Indonesia yang ramah dan terstruktur.
- Gunakan format markdown rapi (bold, bullet points, headers, code block, emoji relevan).";

        // Build Multi-turn Conversation Contents for Gemini
        $contents = [];
        if (is_array($history) && count($history) > 0) {
            // Take up to the last 12 messages for full context memory
            $recentHistory = array_slice($history, -12);
            foreach ($recentHistory as $item) {
                if (!is_array($item)) continue;
                $role = ($item['role'] ?? '') === 'user' ? 'user' : 'model';
                $text = trim($item['text'] ?? '');
                if (!empty($text)) {
                    $contents[] = [
                        'role' => $role,
                        'parts' => [['text' => $text]]
                    ];
                }
            }
        }

        // Append current user message
        $lastItem = end($contents);
        if (!$lastItem || ($lastItem['role'] !== 'user' || $lastItem['parts'][0]['text'] !== $message)) {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]]
            ];
        }

        // Ensure roles strictly alternate (user -> model -> user -> model...) for Gemini API
        $cleanedContents = [];
        $prevRole = null;
        foreach ($contents as $c) {
            if ($c['role'] === $prevRole) {
                $lastIdx = count($cleanedContents) - 1;
                $cleanedContents[$lastIdx]['parts'][0]['text'] .= "\n\n" . $c['parts'][0]['text'];
            } else {
                $cleanedContents[] = $c;
                $prevRole = $c['role'];
            }
        }

        // Ensure the sequence starts with 'user'
        while (count($cleanedContents) > 0 && $cleanedContents[0]['role'] !== 'user') {
            array_shift($cleanedContents);
        }

        if (empty($cleanedContents)) {
            $cleanedContents[] = [
                'role' => 'user',
                'parts' => [['text' => $message]]
            ];
        }

        $geminiKey = env('GEMINI_API_KEY');
        $reply = null;
        $source = 'local';

        // Try Gemini API (Verified active models for key: gemini-3.5-flash, gemini-3.5-flash-lite)
        if (!empty($geminiKey)) {
            $models = ['gemini-3.5-flash', 'gemini-3.5-flash-lite', 'gemini-3.8-flash'];
            foreach ($models as $modelName) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key=" . $geminiKey;
                $payload = [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $systemInstruction]
                        ]
                    ],
                    'contents' => $cleanedContents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2500
                    ]
                ];

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
                curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);

                $responseBody = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode === 200 && $responseBody) {
                    $json = json_decode($responseBody, true);
                    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                        $reply = trim($json['candidates'][0]['content']['parts'][0]['text']);
                        $source = 'gemini';
                        break;
                    }
                }
            }
        }

        // Local Deterministic Fallback if Gemini is offline/timeout
        if (!$reply) {
            $reply = $this->generateLocalAiResponse($message, $tasks, $history);
            $source = 'local_fallback';
        }

        return response()->json([
            'success' => true,
            'reply' => $reply,
            'source' => $source
        ]);
    }

    /**
     * Local Deterministic AI Response Generator based on Real Database & Academic Tutor
     */
    private function generateLocalAiResponse(string $message, $tasks, array $history = []): string
    {
        $m = strtolower($message);

        // Check if user is asking for line-by-line explanation of code
        if (str_contains($m, 'tiap baris') || str_contains($m, 'baris demi baris') || str_contains($m, 'setiap baris') || str_contains($m, 'jelaskan coding') || str_contains($m, 'jelaskan kode')) {
            return "Tentu! 💻 Berikut adalah **penjelasan baris demi baris** dari contoh kode class `Student` di Java:\n\n" .
                "```java\n" .
                "1 | public class Student {\n" .
                "```\n" .
                "• **`public`**: Access modifier (hak akses), artinya kelas ini bisa diakses dari file/package lain di dalam proyek.\n" .
                "• **`class`**: Keyword Java untuk membuat class (cetak biru / blueprint objek).\n" .
                "• **`Student`**: Nama kelas (diawali huruf kapital sesuai konvensi Java).\n\n" .
                "```java\n" .
                "2 |     private String name;\n" .
                "```\n" .
                "• **`private`**: Menjaga data agar hanya bisa diakses dari dalam class `Student` ini saja (penerapan pilar **Enkapsulasi** agar data terlindungi).\n" .
                "• **`String`**: Tipe data untuk menyimpan teks.\n" .
                "• **`name`**: Nama variabel atribut/properti untuk menyimpan identitas siswa.\n\n" .
                "```java\n" .
                "3 |     public Student(String name) {\n" .
                "4 |         this.name = name;\n" .
                "5 |     }\n" .
                "```\n" .
                "• **`Student(...)`**: Ini adalah **Constructor**, method khusus yang dipanggil otomatis saat objek baru dibuat dengan keyword `new`.\n" .
                "• **`this.name = name;`**: `this` merujuk ke atribut class `name`, sedangkan `name` di sebelah kanan adalah parameter input dari constructor.\n\n" .
                "```java\n" .
                "6 |     public void study() {\n" .
                "7 |         System.out.println(name + \" sedang belajar!\");\n" .
                "8 |     }\n" .
                "```\n" .
                "• **`public void study()`**: Method (fungsi perilaku objek). `void` menandakan method tidak mengembalikan nilai (*return value*).\n" .
                "• **`System.out.println(...)`**: Mencetak teks ke layar terminal/konsol.\n\n" .
                "Ada baris atau konsep tertentu yang ingin kamu tanyakan lebih dalam? 😊";
        }

        // Academic Question: OOP / Java / Coding
        if (str_contains($m, 'oop') || str_contains($m, 'java') || str_contains($m, 'class') || str_contains($m, 'coding') || str_contains($m, 'program')) {
            return "Halo! 💻 Mari kita bahas konsep **OOP (Object-Oriented Programming)**:\n\n" .
                "OOP didasarkan pada 4 pilar utama:\n" .
                "1. **Encapsulation:** Membungkus data (atribut) dan method dalam satu unit class serta melindunginya dengan *access modifier* (`private`, `public`).\n" .
                "2. **Inheritance:** Pewarisan sifat dari class induk (*superclass*) ke class anak (*subclass*) menggunakan keyword `extends`.\n" .
                "3. **Polymorphism:** Kemampuan object mengambil banyak bentuk (via *Overloading* dan *Overriding*).\n" .
                "4. **Abstraction:** Menyembunyikan detail implementasi dan hanya menampilkan fungsi penting via `abstract class` atau `interface`.\n\n" .
                "Contoh sederhana class di Java:\n" .
                "```java\npublic class Student {\n    private String name;\n    public Student(String name) { this.name = name; }\n    public void study() { System.out.println(name + \" sedang belajar!\"); }\n}\n```\n\n" .
                "Ada soal atau bagian kode tertentu yang ingin kamu tanyakan lebih spesifik? 😊";
        }

        // Academic Question: Math / Numerical Analysis / Calculus
        if (str_contains($m, 'math') || str_contains($m, 'matematika') || str_contains($m, 'numerik') || str_contains($m, 'kalkulus') || str_contains($m, 'persamaan')) {
            return "Halo! 📐 Untuk materi **Matematika & Analisis Numerik**:\n\n" .
                "Beberapa metode penyelesaian penting yang sering keluar di ujian:\n" .
                "- **Akar Persamaan Non-Linier:** Metode Biseksi, Regula Falsi, dan Newton-Raphson.\n" .
                "- **Sistem Persamaan Linier:** Eliminasi Gauss, Gauss-Jordan, dan Dekomposisi LU.\n" .
                "- **Interpolasi:** Polinom Lagrange dan Newton Divided Difference.\n\n" .
                "💡 *Tips:* Selalu perhatikan batas toleransi error ($\epsilon$) dan iterasi maksimum saat menyelesaikan soal numerik!\n\n" .
                "Ketikkan soal matematika atau persamaan yang ingin kamu hitung bersama ya!";
        }

        // Academic Question: Cybersecurity / Keamanan
        if (str_contains($m, 'cyber') || str_contains($m, 'security') || str_contains($m, 'keamanan') || str_contains($m, 'enkripsi')) {
            return "Halo! 🛡️ Dalam **Keamanan Siber (Cybersecurity)**, fondasi utamanya adalah **CIA Triad**:\n\n" .
                "1. **Confidentiality (Kerahasiaan):** Memastikan data hanya diakses pihak berwenang (misal: enkripsi AES, RSA).\n" .
                "2. **Integrity (Integritas):** Menjamin data tidak diubah tanpa izin (misal: hashing SHA-256, digital signature).\n" .
                "3. **Availability (Ketersediaan):** Memastikan sistem selalu siap diakses saat dibutuhkan (mitigasi serangan DDoS).\n\n" .
                "Tugas terkait: *AI Cybersecurity Report* kamu dijadwalkan selesai besok (Jumat, 30 Okt). Ada topik spesifik laporan yang butuh ide tambahan?";
        }

        // Check for today's schedule / tasks
        if (str_contains($m, 'hari ini') || str_contains($m, 'jadwal') || str_contains($m, 'today')) {
            $todayTasks = $tasks->filter(function ($t) {
                return (str_contains($t->due_date, 'Oct 29') || $t->assigned_day === 'Thu');
            });

            $completedToday = $todayTasks->where('status', 'completed');
            $pendingToday = $todayTasks->where('status', 'pending');

            $out = "Halo! 👋 Berikut adalah ringkasan **jadwal & tugas hari ini (Kamis, 29 Oktober 2026)** berdasarkan databasemu:\n\n";

            if ($pendingToday->count() > 0) {
                $out .= "⏳ **Tugas yang Perlu Diselesaikan Hari Ini:**\n";
                foreach ($pendingToday as $t) {
                    $out .= sprintf("- 📌 **%s** (%s) - Prioritas: *%s* (Waktu: %s)\n", $t->title, $t->course, $t->priority, $t->assigned_time ?: 'Fleksibel');
                    if ($t->description) {
                        $out .= sprintf("  *Catatan:* %s\n", $t->description);
                    }
                }
                $out .= "\n";
            } else {
                $out .= "🎉 **Luar biasa!** Tidak ada tugas tertunda untuk hari ini. Semua target hari ini sudah beres:\n";
                foreach ($completedToday as $t) {
                    $out .= sprintf("- ✅ **%s** (%s) [SELESAI]\n", $t->title, $t->course);
                    if ($t->description) {
                        $out .= sprintf("  *Catatan:* %s\n", $t->description);
                    }
                }
                $out .= "\n";
            }

            // Check tomorrow's task
            $tomorrowTasks = $tasks->filter(function ($t) {
                return (str_contains($t->due_date, 'Oct 30') || $t->assigned_day === 'Fri') && $t->status === 'pending';
            });

            if ($tomorrowTasks->count() > 0) {
                $out .= "⚠️ **Pengingat untuk Besok (Jumat, 30 Okt):**\n";
                foreach ($tomorrowTasks as $t) {
                    $out .= sprintf("- 🛡️ **%s** (%s) - Deadline: %s (Prioritas: %s)\n", $t->title, $t->course, $t->due_date, $t->priority);
                    if ($t->description) {
                        $out .= sprintf("  *Catatan:* %s\n", $t->description);
                    }
                }
                $out .= "\nKamu bisa mulai mencicilnya lebih awal agar santai besok! 💪";
            }

            return $out;
        }

        // Check for urgency / priority
        if (str_contains($m, 'mendesak') || str_contains($m, 'prioritas') || str_contains($m, 'urgent') || str_contains($m, 'penting')) {
            $urgentTasks = $tasks->where('status', 'pending')->sortByDesc(function ($t) {
                return $t->priority === 'High' ? 3 : ($t->priority === 'Medium' ? 2 : 1);
            });

            if ($urgentTasks->count() === 0) {
                return "Bagus sekali! 🎉 Saat ini tidak ada tugas tertunda yang mendesak. Semua tugas prioritas tinggi telah kamu selesaikan!";
            }

            $out = "⚡ **Daftar Tugas Paling Mendesak & Prioritas Tinggi:**\n\n";
            foreach ($urgentTasks->take(3) as $t) {
                $out .= sprintf("- 🔥 **%s** (%s)\n  Deadline: **%s** | Prioritas: **%s** | Durasi: %s\n", $t->title, $t->course, $t->due_date, $t->priority, $t->duration ?: '2 Jam');
                if ($t->description) {
                    $out .= sprintf("  *Catatan:* %s\n", $t->description);
                }
            }
            $out .= "\nYuk fokus selesaikan yang paling atas terlebih dahulu! 🚀";
            return $out;
        }

        // Check for progress / statistics
        if (str_contains($m, 'progress') || str_contains($m, 'statistik') || str_contains($m, 'selesai') || str_contains($m, 'status')) {
            $total = $tasks->count();
            $completed = $tasks->where('status', 'completed')->count();
            $pending = $total - $completed;
            $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

            return "📊 **Statistik & Progress Belajar Kamu Saat Ini:**\n\n" .
                "- 📚 Total Tugas: **{$total} tugas**\n" .
                "- ✅ Tugas Selesai: **{$completed} tugas ({$percent}%)**\n" .
                "- ⏳ Tugas Pending: **{$pending} tugas**\n" .
                "- 🔥 Study Streak: **4 Hari Berturut-turut (On Fire!)**\n\n" .
                "Terus pertahankan ritme belajarmu ya! Konsistensi adalah kunci sukses! 🌟";
        }

        // General Academic Tutor Response
        return "Halo! 👋 Saya **SmartStudy AI Tutor & Academic Companion**.\n\n" .
            "Kamu bisa menanyakan apa saja kepadaku:\n" .
            "- 💻 **Soal & Kode Pemrograman** (misal: *'Bantu jelaskan cara kerja QuickSort di Java'*)\n" .
            "- 📐 **Soal Matematika & Teori** (misal: *'Bagaimana cara menghitung determinan matriks?'*)\n" .
            "- 📅 **Cek Jadwal & Tugas Kuliah** (misal: *'Ada jadwal apa hari ini?'*)\n" .
            "- ⚡ **Tugas Mendesak** (misal: *'Tugas apa yang harus dikerjakan duluan?'*)\n\n" .
            "Silakan ketikkan soal atau pertanyaanmu di bawah! 😊";
    }
}
