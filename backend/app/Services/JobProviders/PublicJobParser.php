<?php

namespace App\Services\JobProviders;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/** Reads data already present in public HTML; never evaluates page JavaScript. */
class PublicJobParser
{
    use ExtractsJobSkills;

    public function parse(string $source, string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($dom);
        return match ($source) {
            'hiredly' => $this->hiredly($xpath),
            'ricebowl', 'maukerja' => $this->ajobthing($xpath, $source),
            'jobstore' => $this->jobstore($xpath),
            'jobstreet' => $this->jobstreet($xpath),
            default => [],
        };
    }

    private function hiredly(DOMXPath $xpath): array
    {
        $node = $xpath->query('//script[@id="__NEXT_DATA__"]')->item(0);
        $data = json_decode($node?->textContent ?? '', true);
        $jobs = [];
        foreach (data_get($data, 'props.pageProps.jobs', []) as $row) {
            if (!is_array($row) || ($row['expired'] ?? false) || !($row['active'] ?? true) || empty($row['slug'])) continue;
            $skills = collect($row['skills'] ?? [])->pluck('name')->filter()->values()->all();
            $description = $this->text($row['gptSummary'] ?? '');
            // The source labels this summary as AI-generated; only its explicit skill tags are requirements.
            $jobs[] = $this->record((string) ($row['id'] ?? $row['slug']), $row['title'] ?? '', data_get($row, 'company.name', ''),
                implode(', ', array_unique(array_filter([$row['location'] ?? '', $row['stateRegion'] ?? '']))),
                $row['jobType'] ?? null, $description, 'https://my.hiredly.com/jobs/'.$row['slug'], $skills, [
                    'published_at' => $this->date($row['activeAt'] ?? null),
                    ...$this->salary($row['salary'] ?? '', 'MYR'),
                    'raw_payload' => ['collection_method'=>'public_html', 'requirements_complete'=>count($skills)>0,
                        'description_scope'=>'source_ai_summary', 'country'=>$this->country(($row['location'] ?? '').' '.($row['stateRegion'] ?? '').' '.($row['title'] ?? ''))],
                ]);
        }
        return $this->valid($jobs);
    }

    private function ajobthing(DOMXPath $xpath, string $source): array
    {
        $base = $source === 'ricebowl' ? 'https://www.ricebowl.my' : 'https://www.maukerja.my';
        $jobs = [];
        foreach ($xpath->query('//mkrb-job-card[@job-id]') as $card) {
            $locations = json_decode($card->getAttribute('locations'), true) ?: [];
            $location = implode(', ', array_column($locations, 'label'));
            $types = json_decode($card->getAttribute('job-types'), true) ?: [];
            $url = $card->getAttribute('href');
            if (!str_starts_with($url, '/')) continue;
            $description = $card->getAttribute('highlight');
            $jobs[] = $this->record($card->getAttribute('job-id'), $card->getAttribute('job-title'), $card->getAttribute('company'),
                $location, $types[0]['label'] ?? null, $description, $base.$url, [], [
                    'published_at' => $this->date($card->getAttribute('posted-at')),
                    ...$this->salary($card->getAttribute('salary')),
                    'raw_payload' => ['collection_method'=>'public_html','requirements_complete'=>false,'description_scope'=>'listing_summary',
                        'country'=>$this->country($location.' '.$card->getAttribute('job-title')), 'shared_listing_id'=>'ajobthing:'.$card->getAttribute('job-id')],
                ]);
        }
        return $this->valid($jobs);
    }

    private function jobstore(DOMXPath $xpath): array
    {
        $jobs = [];
        foreach ($xpath->query('//a[@data-id and contains(@href,"/my/job/")]') as $card) {
            $detail = $xpath->query('.//*[@data-full]', $card)->item(0);
            $row = json_decode($detail?->getAttribute('data-full') ?? '', true);
            if (!is_array($row)) continue;
            $description = $this->text($row['description'] ?? '');
            $salary = [];
            // Do not present a platform estimate, annual or hidden amount as an offered monthly salary.
            if (!($row['is_est_salary'] ?? false) && !($row['salary_hidden'] ?? false) && preg_match('/month/i', $row['salary_period'] ?? '')) {
                $salary = $this->salary(($row['salary_min'] ?? '').' - '.($row['salary_max'] ?? ''), $row['salary_currency'] ?? null);
            }
            $jobs[] = $this->record($card->getAttribute('data-id'), $row['title'] ?? '', $row['company'] ?? '',
                $row['area'] ?? $row['city_state_country'] ?? '', $row['type'] ?? null, $description,
                'https://www.jobstore.com'.$card->getAttribute('href'), $this->extractSkills(($row['title'] ?? '').' '.$description), [
                    ...$salary,
                    'raw_payload' => ['collection_method'=>'public_html','requirements_complete'=>strlen($description)>50,
                        'description_scope'=>'full_listing','country'=>$row['country'] ?? $this->country($row['area'] ?? '')],
                ]);
        }
        return $this->valid($jobs);
    }

    private function jobstreet(DOMXPath $xpath): array
    {
        $jobs = [];
        foreach ($xpath->query('//article[.//*[@data-automation="jobTitle"]]') as $card) {
            $title = $xpath->query('.//*[@data-automation="jobTitle"]', $card)->item(0);
            $href = $title?->getAttribute('href') ?? '';
            if (!preg_match('~/job/(\d+)~', $href, $id)) continue;
            $get = fn ($key) => $this->text($xpath->query('.//*[@data-automation="'.$key.'"]', $card)->item(0)?->textContent ?? '');
            $jobs[] = $this->record($id[1], $title->textContent, $get('jobCompany'), $get('jobLocation'), $get('jobWorkType'),
                $get('jobShortDescription'), 'https://my.jobstreet.com/job/'.$id[1], [], [
                    ...$this->salary($get('jobSalary')),
                    'raw_payload'=>['collection_method'=>'public_html','requirements_complete'=>false,'description_scope'=>'listing_summary','country'=>$this->country($get('jobLocation').' '.$title->textContent)],
                ]);
        }
        return $this->valid($jobs);
    }

    /** Enrich a summary from the source's published JobPosting JSON-LD, without running scripts. */
    public function detail(string $html, array $job): array
    {
        if (!preg_match_all('~<script\b[^>]*type=["\']application/ld\+json["\'][^>]*>(.*?)</script>~is', $html, $scripts)) return $job;
        foreach ($scripts[1] as $script) {
            $data = json_decode($script, true);
            if (!is_array($data)) continue;
            $nodes = isset($data['@graph']) ? $data['@graph'] : (array_is_list($data) ? $data : [$data]);
            foreach ($nodes as $row) {
                if (!is_array($row) || ($row['@type'] ?? '') !== 'JobPosting') continue;
                $description = $this->text($row['description'] ?? '');
                if ($description === '') continue;
                $job['description'] = Str::limit($description, 10000, '');
                $job['required_skills'] = $this->extractSkills($job['title'].' '.$description);
                $job['published_at'] = $this->date($row['datePosted'] ?? null) ?? $job['published_at'];
                $job['expires_at'] = $this->date($row['validThrough'] ?? null);
                $country = data_get($row, 'jobLocation.address.addressCountry');
                if (is_string($country)) $job['raw_payload']['country'] = $country;
                $job['raw_payload']['requirements_complete'] = true;
                $job['raw_payload']['description_scope'] = 'full_listing';
                return $job;
            }
        }
        return $job;
    }

    private function record(string $id, string $title, string $company, ?string $location, ?string $type, string $description, string $url, array $skills, array $extra): array
    {
        return array_replace([
            'external_id'=>Str::limit($id,255,''),'title'=>Str::limit($this->text($title),255,''),'company'=>Str::limit($this->text($company),255,''),
            'location'=>Str::limit($this->text($location ?? ''),255,'') ?: null,
            'work_type'=>Str::lower(preg_replace('/[\s_]+/', '-', trim($type ?? ''))) ?: null,
            'description'=>Str::limit($this->text($description),10000,''),'required_skills'=>array_values(array_unique($skills)),
            'application_url'=>$url,'salary_min'=>null,'salary_max'=>null,'salary_currency'=>null,'published_at'=>null,'expires_at'=>null,'raw_payload'=>[],
        ],$extra);
    }

    private function valid(array $jobs): array { return array_values(array_filter($jobs, fn ($job) => $job['external_id'] !== '' && $job['title'] !== '' && $job['company'] !== '')); }
    private function text(string $value): string { return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(preg_replace('~<(?:br\b[^>]*|/(?:p|li|div|h[1-6]))>~i',' ',$value)), ENT_QUOTES | ENT_HTML5, 'UTF-8'))); }
    private function date(?string $value): ?string
    {
        if (!$value) return null;
        try { $date = ctype_digit($value) && strlen($value) >= 10 ? Carbon::createFromTimestamp((int)$value / (strlen($value)>10 ? 1000 : 1)) : Carbon::parse($value); return $date->toDateTimeString(); }
        catch (\Throwable) { return null; }
    }
    private function salary(string $value, ?string $currency = null): array
    {
        if (preg_match('/year|annum|hour|daily|day|estimate|competitive|negotiable/i', $value)) return [];
        $currency ??= preg_match('/MYR|\bRM\b/i',$value) ? 'MYR' : null;
        if (!$currency || !preg_match_all('/\d[\d,]*(?:\.\d+)?/', $value, $matches)) return [];
        $numbers = array_map(fn ($n) => (float)str_replace(',','',$n),$matches[0]);
        if (!$numbers || $numbers[0] <= 0) return [];
        return ['salary_min'=>$numbers[0], 'salary_max'=>$numbers[1] ?? $numbers[0], 'salary_currency'=>strtoupper($currency)];
    }
    private function country(string $location): ?string
    {
        if (preg_match('/singapore|singapura|hong kong|australia|indonesia|thailand|philippines|united states|united kingdom/i',$location)) return 'OTHER';
        return preg_match('/malaysia|kuala lumpur|selangor|penang|pulau pinang|melaka|malacca|johor|kedah|kelantan|terengganu|perak|perlis|pahang|sabah|sarawak|labuan|putrajaya|negeri sembilan/i',$location) ? 'MY' : null;
    }
}
