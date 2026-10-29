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
        $this->assertStringContainsString('The Physics of Malware', $material->title);
        $this->assertGreaterThanOrEqual(20, $material->slides_count);
        $this->assertArrayHasKey('summary', $material->data);
        $this->assertStringContainsString('Shannon Entropy', $material->data['summary']['executive_summary'] . implode(' ', $material->data['summary']['key_takeaways']));
    }
}
