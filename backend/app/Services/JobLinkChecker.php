<?php
namespace App\Services;
use App\Models\JobListing;
use Illuminate\Support\Facades\Http;
class JobLinkChecker
{
    public function check(int $limit=10): int
    {
        $checked=0;
        foreach(JobListing::where('fetched_at','>=',now()->subDays(14))->where(fn($q)=>$q->whereNull('link_checked_at')->orWhere('link_checked_at','<',now()->subDay()))->limit($limit)->get() as $listing) {
            $host=strtolower(parse_url($listing->application_url, PHP_URL_HOST)??'');
            // Only contact provider-owned domains; never fetch arbitrary URLs from a feed.
            if(parse_url($listing->application_url,PHP_URL_SCHEME)!=='https' || !preg_match('/(^|\.)(remotive\.com|jooble\.org|hiredly\.com|ricebowl\.my|maukerja\.my|jobstore\.com)$/',$host))continue;
            try {$response=Http::timeout(5)->withOptions(['allow_redirects'=>false])->head($listing->application_url);$listing->forceFill(['link_checked_at'=>now(),'link_status'=>$response->status()])->save();$checked++;}
            catch(\Throwable){$listing->forceFill(['link_checked_at'=>now(),'link_status'=>null])->save();}
        }
        return $checked;
    }
}
