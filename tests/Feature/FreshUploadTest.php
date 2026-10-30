<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FreshUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_real_pdf_upload_extracts_and_generates_academic_data(): void
    {
        $realPdf = 'C:/Users/Atila/Downloads/Materi IF640-W05-The Physics of Malware_odd20262027.pdf';
        $this->assertFileExists($realPdf);

        $uploadedFile = new UploadedFile(
            $realPdf,
            'Materi IF640-W05-The Physics of Malware_odd20262027.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->post('/ai-generator', [
            'mode' => 'summary',
            'presentation_file' => $uploadedFile
        ]);

        $response->assertStatus(302);

        $material = \App\Models\StudyMaterial::latest('id')->first();
        $this->assertNotNull($material);
        $this->assertEquals('summary', $material->mode);
        $this->assertStringContainsStringIgnoringCase('The Physics of Malware', $material->title);
        $this->assertGreaterThanOrEqual(20, $material->slides_count);
        $this->assertArrayHasKey('summary', $material->data);
        $this->assertStringContainsString('Shannon Entropy', $material->data['summary']['executive_summary'] . implode(' ', $material->data['summary']['key_takeaways']));
    }

    public function test_fresh_penetration_testing_pdf_upload_extracts_and_generates_quiz(): void
    {
        $realPdf = base_path('storage/app/private/uploads/upload_1793337019_6ae426bb2dec5.pdf');
        $this->assertFileExists($realPdf);

        $uploadedFile = new UploadedFile(
            $realPdf,
            'Materi-IF676-M03-Penetration-Testing-Planning.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->post('/ai-generator', [
            'mode' => 'quiz',
            'presentation_file' => $uploadedFile
        ]);

        $response->assertStatus(302);

        $material = \App\Models\StudyMaterial::latest('id')->first();
        $this->assertNotNull($material);
        $this->assertEquals('quiz', $material->mode);
        $this->assertStringContainsStringIgnoringCase('Penetration Testing', $material->title);
        $this->assertStringNotContainsString('upload_', $material->title);
        $this->assertEquals(34, $material->slides_count);
        $this->assertArrayHasKey('quiz', $material->data);
        $this->assertGreaterThanOrEqual(4, count($material->data['quiz']));
        
        // Ensure quiz question is academic and directly from slides
        $q1 = $material->data['quiz'][0];
        $this->assertArrayHasKey('question', $q1);
        $this->assertArrayHasKey('options', $q1);
        $this->assertArrayHasKey('explanation', $q1);
        $this->assertStringNotContainsString('Tony Kusmana', $q1['question']);
    }

    public function test_fresh_arbitrary_new_course_upload_extracts_intelligent_study_pack(): void
    {
        $newCoursePdf = 'C:/Users/Atila/Downloads/CE542-System Administration-MidTerm-2627.pdf';
        $this->assertFileExists($newCoursePdf);

        $uploadedFile = new UploadedFile(
            $newCoursePdf,
            'CE542-System-Administration-MidTerm-2627.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->post('/ai-generator', [
            'mode' => 'quiz',
            'presentation_file' => $uploadedFile
        ]);

        $response->assertStatus(302);

        $material = \App\Models\StudyMaterial::latest('id')->first();
        $this->assertNotNull($material);
        $this->assertStringNotContainsString('upload_', $material->title);
        $this->assertStringNotContainsString('STUDY PROGRAM OF', $material->title);
        $this->assertStringContainsStringIgnoringCase('System Administration', $material->title);
        $this->assertArrayHasKey('quiz', $material->data);
        $this->assertGreaterThanOrEqual(3, count($material->data['quiz']));

        // Ensure questions never ask about admin noise
        foreach ($material->data['quiz'] as $q) {
            $this->assertStringNotContainsString('Dareen K. Halim', $q['question']);
            $this->assertStringNotContainsString('STUDY PROGRAM', $q['question']);
            $this->assertStringNotContainsString('FACULTY OF', $q['question']);
        }
    }
}
