<?php

namespace App\Commands;

use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\DomCrawler\Crawler;

class DownloadBlog extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'download-blog';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Display an inspiring quote';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $url = 'https://blog.influx.com.br/ingles/o-que-sao-os-conversation-fillers-e-como-usa-los-em-ingles';

        $html = file_get_contents($url);
        $html = new Crawler($html);
        $html = $html->filter('.post-content');

        $data = [];
        $html->filter('p')->each(function ($node) use (&$data, $url) {

            $text_1 = $node->filter('em')->count() > 0 ? $node->filter('em')->html() : null;
            $text_2 = $node->filter('strong')->count() > 0 ? $node->filter('strong')->html() : null;
            $audio = $node->nextAll()->attr('src');

            $html = $node->html();

            if (preg_match('/(?:<strong.*?>(.*?)<em><u.*?>(.*?)<\/em>)/', $html, $matches)) {
                $text_1 = strip_tags($matches[1]);
                $text_2 = strip_tags($matches[2]);
            }

            if (strlen($text_1) > 0 and strlen($text_2) > 0 and !is_null($audio)) {

                $mp3_url = "https://blog.influx.com.br/" . $audio;
                $name = str_replace('/', '', strrchr($mp3_url, "/"));

                $folder = str_replace('/', '', strrchr($url, "/"));
                $filename = storage_path(sprintf('blog/%s/%s', $folder, $name));

                $this->forceDir(storage_path(sprintf('blog/%s', $folder)));

                if (!file_exists($filename)) {
                    file_put_contents($filename, file_get_contents($mp3_url));;
                    sleep(1);
                }


                $data[] = [
                    'english'    => $this->replaceToB($text_2),
                    'portuguese' => $this->replaceToB($text_1),
                    'audio'      => $audio
                ];

            }
        });

        // Open a file in write mode ('w')
        $fp = fopen(storage_path('blog/test.csv'), 'w');

        // Loop through file pointer and a line
        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);

        dd($data);
    }

    public function replaceToB($text)
    {
        return str_replace(['<u>', '</u>'], ['<b>', '</b>'], $text);
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
