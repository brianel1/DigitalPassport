<?php
namespace App\Http\Controllers;

use App\Models\JobListing;
use App\Services\JobLinkChecker;
use App\Services\JobSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SystemStatusController extends Controller
{
    public function show(Request $request)
    {
        abort_unless($request->user()->role==='admin',403);
        $ai=Cache::remember('admin-ai-health',10,function(){
            $model=config('services.ollama.model');
            $status=['configured'=>filled($model),'reachable'=>false,'modelAvailable'=>false,'model'=>$model];
            if(!filled($model))return $status;
            try {
                $response=Http::timeout(3)->get(rtrim(config('services.ollama.url'),'/').'/api/tags')->throw();
                $status['reachable']=true;
                $status['modelAvailable']=collect($response->json('models',[]))->contains(fn($m)=>in_array($m['name']??'entry',[$model,$model.':latest'],true));
            } catch(\Throwable) {}
            return $status;
        });
        $catalogue=app(\App\Services\JobCatalogue::class)->summary();
        $providers=collect(app(JobSyncService::class)->providers())->map(fn($provider)=>[
            'source'=>$provider->source(), 'configured'=>$provider->isConfigured(),
            'method'=>$provider instanceof \App\Services\JobProviders\PublicHtmlJobProvider ? 'Public web pages · no API key' : 'Existing feed integration',
            'freshListings'=>collect($catalogue['sources'])->firstWhere('source',$provider->source())['count'] ?? 0,
            'lastSync'=>DB::table('job_sync_runs')->where('source',$provider->source())->latest('id')->first(),
        ]);
        return response()->json([
            'ai'=>$ai,'providers'=>$providers,'syncHistory'=>DB::table('job_sync_runs')->latest('id')->limit(30)->get(),
            'recentEvents'=>DB::table('system_events')->select('id','kind','status','message','created_at')->latest('id')->limit(30)->get(),
            'exportFailures'=>DB::table('system_events')->where('kind','export')->where('status','failed')->where('created_at','>=',now()->subDays(7))->count(),
            'unavailableLinks'=>JobListing::whereIn('link_status',[404,410])->count(),
            'catalogue'=>$catalogue,
            'ocrAvailable'=>app(\App\Services\CvOcr::class)->available(),
        ]);
    }

    public function sync(Request $request,JobSyncService $sync,JobLinkChecker $links)
    {
        abort_unless($request->user()->role==='admin',403);
        $lock=Cache::lock('provider-sync',600);abort_unless($lock->get(),409,'A provider sync is already running.');
        try {set_time_limit(600);$results=$sync->sync();$count=$links->check(10);return response()->json(['providers'=>$results,'linksChecked'=>$count]);}
        finally{$lock->release();}
    }
}
