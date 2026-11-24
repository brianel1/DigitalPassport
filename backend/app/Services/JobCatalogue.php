<?php

namespace App\Services;

use App\Models\JobListing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class JobCatalogue
{
    public function eligibleArray(array $job, string $source): bool
    {
        $country = strtoupper(data_get($job,'raw_payload.country','') ?? '');
        if ($country && !in_array($country,['MY','MALAYSIA'],true)) return false;
        $place = ($job['location'] ?? '').' '.($job['title'] ?? '');
        if (preg_match('/singapore[ -]based|based in singapore|singapura|hong kong|united states|united kingdom|australia only|us only|usa only|uk only/i',$place)) return false;
        if ($source === 'remotive') return (bool)preg_match('/malaysia|worldwide|anywhere|global|\bapac\b|\basia\b|south.?east asia/i',$job['location'] ?? '');
        return $country === 'MY' || $country === 'MALAYSIA' || !preg_match('/\bsingapore\b|\bindonesia\b|\bthailand\b|\bphilippines\b|\bindia\b/i',$job['location'] ?? '');
    }

    public function eligible(JobListing $job): bool { return $this->eligibleArray($job->toArray(), $job->source); }
    public function localPriority(JobListing $job): int { return $job->source === 'remotive' ? 0 : 1; }

    public function matchesQuery(JobListing $job, ?string $query): bool
    {
        $normalise = function (string $value): string {
            $value = mb_strtolower(preg_replace('/[\s._-]+/u',' ', $value));
            return str_replace(['software engineer','fullstack','front end','back end'], ['software developer','full stack','frontend','backend'], $value);
        };
        $text = $normalise($job->title.' '.implode(' ', $job->required_skills ?? []));
        $groups = array_filter(array_map('trim',explode(',',$query ?? '')));
        if (!$groups) return true;
        foreach ($groups as $group) {
            $words = array_values(array_filter(explode(' ',$normalise($group)), fn ($word)=>$word!=='' && !in_array($word,['job','jobs','kerja','vacancy','vacancies','in','malaysia'],true)));
            if (!$words) return true;
            if (collect($words)->every(fn ($word)=>collect(self::SYNONYMS[$word] ?? [$word])->contains(fn ($term)=>preg_match('/(?<![\pL\pN])'.preg_quote($term,'/').'(?![\pL\pN])/iu',$text)))) return true;
        }
        return false;
    }

    /** Role words that employers use interchangeably, so "software developer" also finds Web or PHP Developer roles. */
    private const SYNONYMS = [
        'developer' => ['developer', 'engineer', 'programmer', 'dev'],
        'engineer' => ['engineer', 'developer'],
        'programmer' => ['programmer', 'developer'],
        'software' => ['software', 'web', 'full stack', 'frontend', 'backend', 'application', 'mobile', 'php', 'java', 'javascript', 'python', 'laravel', 'react', 'vue', '.net', 'it'],
        'web' => ['web', 'frontend', 'full stack', 'wordpress', 'website'],
        'it' => ['it', 'information technology', 'technical support', 'system', 'systems', 'network', 'helpdesk'],
        'data' => ['data', 'analyst', 'analytics', 'bi', 'database'],
        'intern' => ['intern', 'internship', 'industrial training', 'latihan industri', 'trainee', 'praktikal', 'pelatih'],
        'internship' => ['intern', 'internship', 'industrial training', 'latihan industri', 'trainee', 'praktikal', 'pelatih'],
        'admin' => ['admin', 'administrative', 'administration', 'clerk', 'pentadbiran'],
        'accounting' => ['accounting', 'accountant', 'account', 'accounts', 'finance', 'akaun'],
        'marketing' => ['marketing', 'digital marketing', 'social media', 'brand', 'pemasaran'],
    ];

    public function key(JobListing $job): string
    {
        if ($shared = data_get($job->raw_payload, 'shared_listing_id')) return $shared;
        // Be conservative: same employer/title/location, not merely a similar role title.
        $normalise = fn ($value) => trim(preg_replace('/[^\pL\pN]+/u', ' ', mb_strtolower($value ?? '')));
        return hash('sha256',$normalise($job->company).'|'.$normalise($job->title).'|'.$normalise($job->location));
    }

    public function unique(Collection $jobs): Collection
    {
        // Prefer a full description over a summary when the same vacancy occurs more than once.
        return $jobs->sortByDesc(fn ($job) => (data_get($job->raw_payload,'requirements_complete',true) ? 100 : 0)
            + ($job->source === 'ricebowl' ? 2 : 0))->unique(fn ($job) => $this->key($job))->values();
    }

    public function current(): Collection
    {
        return $this->unique(JobListing::where('fetched_at','>=',now()->subDays(14))
            ->where(fn ($q)=>$q->whereNull('expires_at')->orWhere('expires_at','>',now()))
            ->where(fn ($q)=>$q->whereNull('link_status')->orWhereNotIn('link_status',[404,410]))
            ->get()->filter(fn ($job)=>$this->eligible($job)));
    }

    public function summary(): array
    {
        // Loading and de-duplicating the whole catalogue is slow; reuse it until listings change.
        $version = JobListing::query()->selectRaw('count(*) as total, max(updated_at) as changed')->first();
        return Cache::remember('job-catalogue-summary:'.md5($version?->total.'|'.$version?->changed), now()->addHour(), function () {
            $jobs = $this->current();
            return ['total'=>$jobs->count(), 'sources'=>$jobs->groupBy('source')->map(fn ($rows,$source)=>['source'=>$source,'count'=>$rows->count()])->values()->all(),
                'lastUpdatedAt'=>$jobs->max('fetched_at')?->toISOString()];
        });
    }
}
