<?php

namespace App;


use App\Models\Task;
use App\Models\UserAccount;
use Carbon\Carbon;
use Google\Service\Calendar\EventDateTime;
use Laravel\Socialite\Facades\Socialite;

class AccountService
{
    public function createAccountCalendar(string $token,$account)
    {
        $client = new \Google\Client();
        $client->setAccessToken($token);
        $calendar = new \Google\Service\Calendar($client);
        $insCalendar = new \Google\Service\Calendar\Calendar();
        $insCalendar->setSummary('testWork1');
        $insCalendar->setTimeZone(config('app.timezone'));
        $insCalendar->setDescription('Test work calendar');
        $res = $calendar->calendars->insert($insCalendar);
        $account->calendar()->create([
            'google_id'     => $res->getId(),
        ]);
    }

    public function createTask(Task $task,UserAccount $account)
    {
        $client = new \Google_Client(config('services.google'));
        $res = Socialite::driver('google')->userFromToken($account->token);
        $client->setAccessToken($res->token);
        $calendarId = $account->calendar()->first()->google_id;
        $calendar = new \Google\Service\Calendar($client);
        $event = new \Google\Service\Calendar\Event();
        $event->setSummary($task->name);
        $event->setDescription($task->desc);
        $start = new EventDateTime();
        $start->setDate(Carbon::parse($task->start)->format('Y-m-d'));
        $start->setTimeZone('UTC');
        $event->setStart($start);
        $end =  new EventDateTime();
        $end->setDate(Carbon::parse($task->deadline)->format('Y-m-d'));
        $end->setTimeZone('UTC');
        $event->setEnd($end);
        $calendar->events->insert($calendarId,$event);
    }
}
