<?php

namespace App\Commands;

use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;

class DownloadCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'download';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    private $token;
    /**
     * @var \GuzzleHttp\Client
     */
    private $user;
    /**
     * @var mixed
     */
    private $audiobooks;
    /**
     * @var mixed
     */
    private $plusClass;
    /**
     * @var mixed
     */
    private $plusNoticies;

    public function __construct()
    {
        parent::__construct();
        $this->client = new \GuzzleHttp\Client();

        if (Cache::has('user')) {
            $this->user = Cache::get('user');
            $this->token = $this->user->data->bearer->token;
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $this->doLogin();
        $this->getPlusClass();
//        $this->getAudiobooks();
    }


    public function doLogin()
    {

        $this->info('.:: Realizando autenticação ::.');
//        if (cache()->has('token')) return true;

        try {
            Cache::remember('user', 1000, function () {
                $response = $this->client->post('https://api.portal.influx.morphy.com.br/auth', [
                    'json' => [
                        'login'    => env('LOGIN'),
                        'password' => env('PASSWORD')
                    ]
                ]);

                return json_decode($response->getBody()->getContents());
            });

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            dd($exception->getMessage());
        }

    }

    public function getAudiobooks()
    {
        try {
            $this->audiobooks = Cache::remember('audiobooks', 1000, function () {
                $response = $this->client->get('https://api.portal.influx.morphy.com.br/book/audiobooks', [
                    'headers' => [
                        'Accept'        => 'application/json, text/plain, */*',
                        'Authorization' => 'Bearer ' . $this->token,
                    ]
                ]);

                return json_decode($response->getBody()->getContents());
            });

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            dd($exception->getMessage());
        }

        foreach ($this->audiobooks->data as $item) {
            foreach ($item->lessons as $lesson) {

                foreach ($lesson->audios as $key => $audio) {
                    $this->info(sprintf(".:: Baixando %s - %s - %s ::.", $item->name, $lesson->name, $audio->name));
                    $this->forceDir(storage_path(sprintf('data/%s', $item->name)));

                    $mp3_url = sprintf("https://security.portal.influx.com.br/audiobook/?book=%s&lesson=%s", $audio->bookFile, $audio->lessonFile);
                    $filename = storage_path(sprintf('data/%s/%s - %s %s.mp3', $item->name, $lesson->name, $key + 1, $audio->name));

                    if (!file_exists($filename)) {
                        file_put_contents($filename, file_get_contents($mp3_url));;
                        sleep(1);
                    }
                }
            }
        }

    }

    public function getPlusClass()
    {

        try {
            $this->plusClass = Cache::remember('list-by-student', 1000, function () {
                $response = $this->client->get('https://api.books.influx.com.br/v1/mountain/list-by-student', [
                    'headers' => [
                        'Accept'        => 'application/json, text/plain, */*',
                        'Authorization' => 'Bearer ' . $this->token,
                    ]
                ]);

                return json_decode($response->getBody()->getContents());
            });


            foreach ($this->plusClass as $item) {

                $this->plusNoticies = Cache::remember('list-by-student-noticies', 1000, function () use ($item) {
                    $response = $this->client->get(sprintf("https://api.books.influx.com.br/v1/activity/list-by-book-with-student/%s", $item->id), [
                        'headers' => [
                            'Accept'        => 'application/json, text/plain, */*',
                            'Authorization' => 'Bearer ' . $this->token,
                        ]
                    ]);
                    return json_decode($response->getBody()->getContents());
                });

                dd($this->plusNoticies);
                foreach ($this->plusNoticies as $plusNoticy) {

                    if ($plusNoticy->presentationType == 'TYPE_IMAGE'
                        && preg_match("/(?:http(?:s)?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'<> #]+)/", $plusNoticy->text, $mathes)) {
                        $this->info(sprintf(".:: Gravando %s - %s  ::.", $item->bookOwner, $plusNoticy->title));
                        Storage::disk('local')->prepend("LinksYoutube.csv", sprintf("%s;%s;%s", $plusNoticy->title, $mathes[0], $plusNoticy->createdAt));
                    } else {
                        continue;
                    }

                }
            }
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            dd($exception->getMessage());
        }

    }

    function forceDir($dir)
    {
        if (!is_dir($dir)) {
            $dir_p = explode('/', $dir);
            for ($a = 1; $a <= count($dir_p); $a++) {
                @mkdir(implode('/', array_slice($dir_p, 0, $a)));
            }
        }
    }

}
