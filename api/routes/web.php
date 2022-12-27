<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/test', function () {
    $categories = array(

        'Film_all' => [
            'projektart_id' => [1],
            'board_id' => '6398667efb54f3012ba91860',
            'new_list_id' => '6398667efb54f3012ba91867',
        ],

        '2D' => [
            'projektart_id' => [7],
            'board_id' => '6398667efb54f3012ba91860',
            'new_list_id' => '63986be9e63ff6032908146b',
        ],

        '3D_all' => [
            'projektart_id' => [2],
            'board_id' => '6398667efb54f3012ba91860',
            'new_list_id' => '63986bed64bdd700ec604da4',
        ],
        'Sonstiges' => [
            'projektart_id' => [3, 4, 5, 6, 8],
            'board_id' => '6398667efb54f3012ba91860',
            'new_list_id' => '63986c34d37717018d39f249',
        ],

    );

    foreach ($categories as $categoryName => $category) {
        dump($categoryName);
        try {

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://api.trello.com/1/boards/' . $category['board_id'] . '/cards?key=' . env('TRELLO_KEY') . '&token=' . env('TRELLO_TOKEN'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            $cards = json_decode($response);
            dd($cards);
            foreach($cards as $card) {
                $cht = curl_init();
                curl_setopt($cht, CURLOPT_URL, 'https://api.trello.com/1/cards/'.$card->id.'/members?key=' . env('TRELLO_KEY') . '&token=' . env('TRELLO_TOKEN'));
                curl_setopt($cht, CURLOPT_RETURNTRANSFER, true);
                $responset = curl_exec($cht);
                curl_close($cht);
                $members = json_decode($responset);
                \App\Models\Task::firstOrCreate([
                    'trello_id' => $card->id,
                    ],[
                    'name'  =>  $card->name,
                    'desc'  =>  $card->desc,
                    'start' =>  \Carbon\Carbon::parse($card->start),
                    'deadline' => \Carbon\Carbon::parse($card->due),
                    'closed'   => $card->closed,
                ]);
                if (count($members) > 0) {

                }
            }
        } catch (Exception $exception) {
            dd($exception);
        }
    }
    dd('Finish');
});
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');
Route::post('/profile/update', [\App\Http\Controllers\UserController::class, 'addTrelloUsername'])->middleware(['auth'])->name('user.addTrelloUsername');
Route::get('/profile/sync', [\App\Http\Controllers\UserController::class, 'syncTrelloGoogle'])->middleware(['auth'])->name('user.syncGoogleTrello');

Route::name('oauth2.auth')->get('/oauth2/{provider}', [AccountController::class, 'auth'])->middleware('auth');
Route::name('oauth2.callback')->get('/oauth2/{provider}/callback', [AccountController::class, 'callback'])->middleware('auth');
require __DIR__ . '/auth.php';
