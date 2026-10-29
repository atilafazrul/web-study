<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartStudyTest extends TestCase
{
    use RefreshDatabase;
    public function test_dashboard_screen_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Student!');
        $response->assertSee('AI Analysis');
        $response->assertSee('Weekly Study Plan');
    }

    public function test_my_tasks_screen_loads_successfully(): void
    {
        $response = $this->get('/tasks');
        $response->assertStatus(200);
        $response->assertSee('My Tasks');
        $response->assertSee('AI Insights');
    }

    public function test_can_create_new_task_and_persist(): void
    {
        $response = $this->post('/tasks', [
            'title' => 'Web Security Penetration Testing Lab',
            'duration' => '4 Hours',
            'due_date' => 'Nov 5, 2026'
        ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('tasks', [
            'title' => 'Web Security Penetration Testing Lab'
        ]);
    }

    public function test_can_create_new_task_with_custom_notes(): void
    {
        $response = $this->post('/tasks', [
            'title' => 'Mobile App Flutter Project',
            'duration' => '3 Hours',
            'due_date' => 'Nov 10, 2026',
            'description' => "- Setup project flutter\n- Buat UI login & dashboard\n- Integrasikan API"
        ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('tasks', [
            'title' => 'Mobile App Flutter Project',
            'description' => "- Setup project flutter\n- Buat UI login & dashboard\n- Integrasikan API"
        ]);
    }

    public function test_can_toggle_task_status(): void
    {
        $task = Task::create([
            'title' => 'OOP Java Assignment',
            'duration' => '3h',
            'priority' => 'High',
            'status' => 'pending'
        ]);

        $response = $this->post("/tasks/{$task->id}/toggle");
        $response->assertStatus(302);

        $task->refresh();
        $this->assertEquals('completed', $task->status);
    }

    public function test_study_plan_screen_loads_successfully(): void
    {
        $response = $this->get('/study-plan');
        $response->assertStatus(200);
        $response->assertSee('Study Plan');
        $response->assertSee('Study Plan Summary');
        $response->assertSee('Target Fokus');
    }

    public function test_can_regenerate_study_plan(): void
    {
        $response = $this->post('/study-plan/regenerate');
        $response->assertStatus(302);
    }

    public function test_settings_screen_loads_successfully(): void
    {
        $response = $this->get('/settings');
        $response->assertStatus(200);
        $response->assertSee('Settings');
        $response->assertSee('Student Profile');
    }

    public function test_can_update_settings_profile(): void
    {
        $response = $this->post('/settings', [
            'name' => 'Atila Pratama',
            'email' => 'atila@example.com'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'name' => 'Atila Pratama',
            'email' => 'atila@example.com'
        ]);
    }

    public function test_ai_generator_screen_loads_successfully(): void
    {
        $response = $this->get('/ai-generator');
        $response->assertStatus(200);
        $response->assertSee('PPT to Study AI');
        $response->assertSee('Kuis Interaktif');
        $response->assertSee('Flashcard 3D');
        $response->assertDontSee('Video Belajar');
        $response->assertSee('Rangkuman Catatan');
    }

    public function test_can_generate_new_study_material_from_ppt(): void
    {
        $response = $this->post('/ai-generator', [
            'title' => 'Kecerdasan Buatan dan Deep Learning',
            'mode' => 'quiz',
            'preset_topic' => 'Kecerdasan Buatan: Dasar Machine Learning'
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('study_materials', [
            'mode' => 'quiz'
        ]);
    }

    public function test_can_upload_presentation_file_directly(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->create('kuliah_ai.pptx', 2048, 'application/vnd.openxmlformats-officedocument.presentationml.presentation');

        $response = $this->post('/ai-generator', [
            'mode' => 'flashcards',
            'presentation_file' => $file
        ]);

        $response->assertStatus(302);
        $this->assertDatabaseHas('study_materials', [
            'filename' => 'kuliah_ai.pptx',
            'mode' => 'flashcards'
        ]);
    }

    public function test_can_delete_study_material(): void
    {
        $material = \App\Models\StudyMaterial::create([
            'title' => 'Materi Temporary',
            'filename' => 'temp.pptx',
            'mode' => 'summary',
            'data' => []
        ]);

        $response = $this->delete("/ai-generator/{$material->id}");
        $response->assertStatus(302);
        $this->assertDatabaseMissing('study_materials', [
            'id' => $material->id
        ]);
    }

    public function test_can_refresh_quiz_with_new_questions(): void
    {
        $material = \App\Models\StudyMaterial::create([
            'title' => 'Test Materi Quiz',
            'filename' => 'test.pptx',
            'mode' => 'quiz',
            'data' => [
                'quiz' => [
                    [
                        'question' => 'Soal 1',
                        'options' => ['A', 'B', 'C', 'D'],
                        'correct' => 0,
                        'explanation' => 'Penjelasan 1'
                    ],
                    [
                        'question' => 'Soal 2',
                        'options' => ['Option 1', 'Option 2', 'Option 3', 'Option 4'],
                        'correct' => 1,
                        'explanation' => 'Penjelasan 2'
                    ]
                ]
            ]
        ]);

        $response = $this->post("/ai-generator/{$material->id}/refresh-quiz");
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $material->refresh();
        $this->assertNotEmpty($material->data['quiz']);
    }

    public function test_ai_chat_answers_schedule_question()
    {
        $response = $this->postJson('/ai-chat', [
            'message' => 'ada jadwal apa hari ini'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'reply', 'source']);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('reply'));
    }

    public function test_ai_chat_handles_empty_message()
    {
        $response = $this->postJson('/ai-chat', [
            'message' => ''
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('success'));
    }

    public function test_ai_chat_answers_academic_question()
    {
        $response = $this->postJson('/ai-chat', [
            'message' => 'Bantu jelaskan konsep OOP dan binary search di Java'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'reply', 'source']);
        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('reply'));
    }
}
