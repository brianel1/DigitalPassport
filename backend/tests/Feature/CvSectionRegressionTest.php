<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CvIntelligenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CvSectionRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unicode_headings_preserve_summary_grades_descriptions_and_other_sections(): void
    {
        config()->set('services.ollama.model','test-model');
        $text="Aina Demo\naina@example.test\nSUMMARY\nSeeking an internship in web development.\nEDUCATION\u{200b}\nDemo University\nBachelor of Computing\u{200b} 2023 - 2027\nCGPA 3.75\nPROJECT EXPERIENCE\nBooking System\nA campus laboratory application\n●\u{200b}Frontend: Built an accessible Vue interface.\n● Backend: Created booking endpoints with Laravel.\nWORK EXPERIENCE\nDemo Lab\nWeb Intern\u{200b} June - September 2026\n● Efficiency: Processed booking requests.\n● Training: Guided new team members.\nSKILLS\n● Programming: Python, Java, C\n● Tools: Git, Microsoft (Word, Excel, PowerPoint)\nACTIVITIES AND INVOLVEMENT\nWorkshop secretary, 2026\nACHIEVEMENTS\nDean's list\nPUBLICATIONS\nExample study, 2026\nLANGUAGE\nMalay (Native), English (Advanced)\nREFERENCES\nAvailable upon request";
        Http::fake(['*'=>Http::response(['response'=>json_encode([
            'education'=>[['institution'=>'Demo University','qualification'=>'Bachelor of Computing','period'=>'2023 - 2027']],
            'projects'=>[['title'=>'Booking System']],
            'experience'=>[['role'=>'Web Intern','company'=>'Demo Lab','period'=>'June - September 2026']],
            'certifications'=>[['title'=>'Invented qualification','issuer'=>'Demo Lab','issueDate'=>'2026']],
        ])])]);
        [$cv]=app(CvIntelligenceService::class)->structure($text,User::factory()->create());
        $this->assertSame('Seeking an internship in web development.',$cv['summary']);
        $this->assertSame('CGPA 3.75',$cv['education'][0]['details']);
        $this->assertStringContainsString('Frontend: Built an accessible Vue interface.',$cv['projects'][0]['description']);
        $this->assertStringContainsString('Backend: Created booking endpoints',$cv['projects'][0]['description']);
        $this->assertStringContainsString('Training: Guided new team members.',$cv['experience'][0]['description']);
        $this->assertSame(['Python','Java','C','Git','Microsoft (Word, Excel, PowerPoint)'],$cv['skills']);
        foreach(['Workshop secretary',"Dean's list",'Example study','English (Advanced)'] as $fact)$this->assertStringContainsString($fact,$cv['additional']);
        $this->assertSame([],$cv['certifications']);
    }

    public function test_multiple_roles_keep_their_own_descriptions_and_reject_cross_section_values(): void
    {
        config()->set('services.ollama.model','test-model');
        $text="Aina Demo\nEXPERIENCE\nFirst Lab\nIntern\nBuilt booking endpoints.\nSecond Lab\nAssistant\nHandled customer enquiries.\nPROJECTS\nPortfolio\nCreated an accessible website.";
        Http::fake(['*'=>Http::response(['response'=>json_encode(['experience'=>[
            ['role'=>'Intern','company'=>'First Lab','period'=>''],['role'=>'Assistant','company'=>'Second Lab','period'=>''],
            ['role'=>'Portfolio','company'=>'','period'=>''],
        ],'projects'=>[['title'=>'Portfolio']]])])]);
        [$cv]=app(CvIntelligenceService::class)->structure($text,User::factory()->create());
        $this->assertCount(2,$cv['experience']);
        $this->assertSame('Built booking endpoints.',$cv['experience'][0]['description']);
        $this->assertSame('Handled customer enquiries.',$cv['experience'][1]['description']);
        $this->assertSame('Created an accessible website.',$cv['projects'][0]['description']);
    }

    public function test_unavailable_model_retains_unstructured_sections_for_review(): void
    {
        config()->set('services.ollama.model',null);
        [$cv,$engine]=app(CvIntelligenceService::class)->structure("Aina Demo\nSUMMARY\nComputing student.\nEDUCATION\nDemo University\nEXPERIENCE\nWorked at a campus lab.",User::factory()->create());
        $this->assertSame('Computing student.',$cv['summary']);
        $this->assertStringContainsString('Demo University',$cv['additional']);
        $this->assertStringContainsString('Worked at a campus lab.',$cv['additional']);
        $this->assertSame('text-extraction-v1',$engine);
    }
}
