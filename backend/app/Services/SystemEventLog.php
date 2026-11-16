<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
class SystemEventLog
{
    public function record(string $kind, string $status, string $message): void
    {
        try { DB::table('system_events')->insert(['kind'=>$kind,'status'=>$status,'message'=>mb_substr($message,0,500),'user_id'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]); }
        catch (\Throwable) { /* Diagnostic logging must not prevent normal work. */ }
    }
}
