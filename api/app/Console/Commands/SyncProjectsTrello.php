<?php

namespace App\Console\Commands;

use App\Http\Requests\Request;
use App\TaskService;
use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Projekt;

class SyncProjectsTrello extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:trello';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync projects to trello cards';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        /**
         * Projektart-ID overview:
         *  1  Film
         *  2  3D
         *  3  Grafik
         *  4  Audio
         *  5  Web
         *  6  Intern
         *  7  2D
         *  8  Schnitt
         *  9  Foto
         * 10  Marketing
         */
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

            //$response = file_get_contents('https://api.trello.com/1/boards/' . $category['board_id'] . '/cards?key=' . env('TRELLO_KEY') . '&token=' . env('TRELLO_TOKEN'));

            $cards = json_decode($response);

            $trelloCards = array();

            //dump($cards);
            if (!$cards) {
                continue;
            }
            $taskService = new TaskService();
            $taskService->syncTrello($cards);
            foreach ($cards as $card) {
                $trelloCards[$card->id] = $card->name;
            }

            //$activeProjects = Projekt::where('projektart_id', 'like', '%' . $category['projektart_id'] . '%')->where('status', 'aktiv')->get()->pluck('name');
            //$archivedProjects = Projekt::where('projektart_id', 'like', '%' . $category['projektart_id'] . '%')->where('status', 'archiv')->get()->pluck('name');
            $activeProjects = Projekt::where('projektart_id', 'regexp', '"' . implode('"|"', $category['projektart_id']) . '"')->where('status', 'aktiv')->get()->pluck('name');
            $archivedProjects = Projekt::where('projektart_id', 'regexp', '"' . implode('"|"', $category['projektart_id']) . '"')->where('status', 'archiv')->get()->pluck('name');

            // Create projects
            $projectsToCreate = array();
            $activeProjects->each(function ($name) use ($trelloCards, &$projectsToCreate) {
                if (array_search($name, $trelloCards) === FALSE) {
                    $projectsToCreate[] = $name;
                }
            });


            foreach ($projectsToCreate as $projectName) {
                $client = new Client();
                $res = $client->request('POST', 'https://api.trello.com/1/cards', [
                    'form_params' => [
                        'key' => env('TRELLO_KEY'),
                        'token' => env('TRELLO_TOKEN'),
                        'idList' => $category['new_list_id'],
                        'name' => $projectName,
                    ]
                ]);

                if ($res->getStatusCode() == 200) {
                    dump($projectName . ' in Trello angelegt.');
                } else {
                    dump('Fehler beim Anlegen von ' . $projectName);
                }

                sleep(0.75);
            }
            if (count($projectsToCreate) == 0) {
                dump('Keine neuen Projekte.');
            }


            // Archive projects
            $projectsToArchive = array();
            $archivedProjects->each(function ($name) use ($trelloCards, &$projectsToArchive) {
                if (array_search($name, $trelloCards) !== FALSE) {
                    $projectsToArchive[] = array_search($name, $trelloCards);
                }
            });

            // dump($projectsToArchive);

            foreach ($projectsToArchive as $projectId) {
                $client = new Client();
                $res = $client->request('PUT', 'https://api.trello.com/1/cards/' . $projectId, [
                    'form_params' => [
                        'key' => env('TRELLO_KEY'),
                        'token' => env('TRELLO_TOKEN'),
                        'closed' => true,
                    ]
                ]);

                if ($res->getStatusCode() == 200) {
                    dump($projectId . ' in Trello archiviert.');
                } else {
                    dump('Fehler beim Archivieren von ' . $projectId);
                }

                sleep(0.75);
            }
            if (count($projectsToArchive) == 0) {
                dump('Keine Projekte zu archivieren.');
            }


            } catch (\Exception $e) {
                echo 'Caught exception: ',  $e->getMessage(), "\n";
            }

        }
    }
}
