<?php
// Rebuild derived skill tags from original provider payloads, without fetching or changing user records.
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$extractor = new class { use App\Services\JobProviders\ExtractsJobSkills; public function read(string $text): array { return $this->extractSkills($text); } };
$changed=0;
foreach (App\Models\JobListing::whereIn('source',['remotive','jooble','jobstore','ricebowl','maukerja'])->cursor() as $listing) {
    $payload=$listing->raw_payload;
    if (!is_array($payload)) continue;
    $description=($payload['description_scope']??'')==='full_listing' ? $listing->description : ($payload['description']??$payload['snippet']??null);
    if (!is_string($description)) continue;
    $skills=$extractor->read($listing->title.' '.html_entity_decode(strip_tags($description)));
    if ($skills!==$listing->required_skills) { $listing->update(['required_skills'=>$skills]);$changed++; }
}
echo "Updated derived skill tags on {$changed} cached provider listings. User records unchanged.\n";
