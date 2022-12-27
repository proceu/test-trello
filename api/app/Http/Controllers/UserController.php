<?php

namespace App\Http\Controllers;

use App\AccountService;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class UserController extends Controller
{
    public function addTrelloUsername(Request $request)
    {
        $username = $request->only('trelloUsername');
        $user = $request->user();
        $user->update([
            'trello_username'   =>  $username['trelloUsername'],
        ]);

        return redirect()->route('dashboard');
    }

    public function syncTrelloGoogle(Request $request)
    {
        $user = $request->user();
        $googleAccountBuilder = $user->accounts()->where('driver','google');
        if (!$user->trello_username || $googleAccountBuilder->count() < 1 )
        {
            throw new RuntimeException('add trello username or Google account',422);
        }

        $tasks = Task::whereHas('members', function ($q) use ($user) {
            $q->where('user_id', $user->id);
            $q->whereNull('google_task_id');
        })->where('closed',false)->whereNotNull(['start','deadline'])->get();
        $accountService = new AccountService();
        $account = $googleAccountBuilder->first();
        foreach ($tasks as $task) {
            $accountService->createTask($task,$account);
        }

        return redirect()->route('dashboard');
    }
}
