<?php

namespace App\Console\Commands\Users;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ResetActiveStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'online:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //$user = Auth::user();
        $onlineUsers = User::where('online_status',1)->get();
        if ($onlineUsers){
            collect($onlineUsers)->map(function($onlineUser){
                if (!Cache::has('online-status'.$onlineUser->id)){
                    $onlineUser->update(['online_status' => 0]);
                }
            });
        }
        // else {
        //     User::where('online_status',1)->update(['online_status' => 0]);
        // }
        // return 0;
    }
}
