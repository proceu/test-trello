<?php

namespace App;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TaskService
{
    public function syncTrello(array $cards)
    {
        foreach($cards as $card) {
            $cht = curl_init();
            curl_setopt($cht, CURLOPT_URL, 'https://api.trello.com/1/cards/'.$card->id.'/members?key=' . env('TRELLO_KEY') . '&token=' . env('TRELLO_TOKEN'));
            curl_setopt($cht, CURLOPT_RETURNTRANSFER, true);
            $responset = curl_exec($cht);
            curl_close($cht);
            $members = json_decode($responset);
            $task = Task::firstOrCreate([
                'trello_id' => $card->id,
            ],[
                'name'  =>  $card->name,
                'desc'  =>  $card->desc,
                'start' =>  \Carbon\Carbon::parse($card->start),
                'deadline' => \Carbon\Carbon::parse($card->due),
                'closed'   => $card->closed,
            ]);
            Log::error($task);
            if (count($members) > 0) {
                foreach ($members as $member) {
                    $userID = User::where('trello_username',$member->username)->first('id');
                    if ($userID) {
                        $task->members()->create([
                            'user_id'   =>  $userID
                        ]);
                    }
                }
            }
        }
    }
}
