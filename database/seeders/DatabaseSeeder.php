<?php

namespace Database\Seeders;

use App\Models\Task;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (Task::count() === 0) {
            Task::create([
                'title' => 'OOP Java Assignment',
                'course' => 'OOP',
                'category' => 'Programming',
                'description' => 'Implement main functions and class structures for the assignment.',
                'due_date' => 'Oct 29, 2026',
                'duration' => '3h',
                'priority' => 'High',
                'priority_score' => 92,
                'status' => 'pending',
                'subtasks' => [
                    'Review the assignment requirements',
                    'Design the class structure',
                    'Implement the core functions',
                    'Test and debug the code',
                    'Prepare for submission',
                ],
                'assigned_day' => 'Thu',
                'assigned_time' => '7 PM',
            ]);

            Task::create([
                'title' => 'Numerical Analysis Quiz Prep',
                'course' => 'Math',
                'category' => 'Exam Prep',
                'description' => 'Review differential equations and formulas for the upcoming quiz.',
                'due_date' => 'Oct 29, 2026',
                'duration' => '2h',
                'priority' => 'High',
                'priority_score' => 88,
                'status' => 'pending',
                'subtasks' => [
                    'Review lecture slide chapters 4-6',
                    'Solve 10 sample problems',
                    'Summarize cheat sheet formula guide',
                ],
                'assigned_day' => 'Thu',
                'assigned_time' => '8 PM',
            ]);

            Task::create([
                'title' => 'AI Cybersecurity Report',
                'course' => 'Security',
                'category' => 'Research',
                'description' => 'Write a comprehensive report about recent AI threats and defenses.',
                'due_date' => 'Oct 30, 2026',
                'duration' => '4h',
                'priority' => 'Medium',
                'priority_score' => 74,
                'status' => 'pending',
                'subtasks' => [
                    'Gather recent IEEE papers',
                    'Draft threat analysis section',
                    'Compile mitigation best practices',
                ],
                'assigned_day' => 'Fri',
                'assigned_time' => '7 PM',
            ]);

            Task::create([
                'title' => 'HCI Presentation Slides',
                'course' => 'HCI',
                'category' => 'Presentation',
                'description' => 'Prepare 10-slide deck on UX heuristics for HCI class presentation.',
                'due_date' => 'Oct 31, 2026',
                'duration' => '2h',
                'priority' => 'Medium',
                'priority_score' => 65,
                'status' => 'pending',
                'subtasks' => [
                    'Structure slide outline',
                    'Add Figma prototype screenshots',
                    'Rehearse speech timing (5 mins)',
                ],
                'assigned_day' => 'Sat',
                'assigned_time' => '7 PM',
            ]);

            Task::create([
                'title' => 'Computer Networks Reading',
                'course' => 'Networks',
                'category' => 'Reading',
                'description' => 'Read and annotate Chapter 5 on TCP/IP transport protocols.',
                'due_date' => 'Nov 2, 2026',
                'duration' => '1h',
                'priority' => 'Low',
                'priority_score' => 42,
                'status' => 'completed',
                'subtasks' => [
                    'Read Chapter 5 pages 120-145',
                    'Answer review questions at the end',
                ],
                'assigned_day' => 'Mon',
                'assigned_time' => '7 PM',
            ]);
        }
    }
}
