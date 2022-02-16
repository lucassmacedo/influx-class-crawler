<?php

namespace App\Commands;

use Facebook\WebDriver\WebDriverBy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use League\HTMLToMarkdown\HtmlConverter;
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


        $links = file(storage_path('links.txt'), FILE_IGNORE_NEW_LINES);
        // Open a file in write mode ('w')
        $fp = fopen(storage_path('blog/data.csv'), 'w');

//        $progessBar = $this->output->createProgressBar(count($links));

        foreach ($links as $key => $link) {

//            $progessBar->advance();

            $html = file_get_contents($link);
            $html = new Crawler($html);

            $conv = new HtmlConverter(array('strip_tags' => true));
            dd($conv->convert($html->filter('.post')->html()));

            $title = $html->filter('title')->text();

            $html = $html->filter('.post-content');


            $data = [];
            $html->filter('audio')->each(function ($node) use (&$data, $link) {
                $data[] = $node->attr('src');
            });


            foreach ($data as $datum) {

                $mp3_url = "https://blog.influx.com.br/" . $datum;
                $name = str_replace('/', '', strrchr($mp3_url, "/"));

                $folder = str_replace('/', '', strrchr($link, "/"));
                $filename = storage_path(sprintf('blog/%s/%s', $folder, $name));

                $this->forceDir(storage_path(sprintf('blog/%s', $folder)));

                if (!file_exists($filename)) {
                    file_put_contents($filename, file_get_contents($mp3_url));;
                    sleep(1);
                }

                $csv['title'] = $title;
                $csv['name'] = $name;
                $csv['post_url'] = $link;
                $csv['audio_url'] = $mp3_url;
                $csv['audio_file'] = $folder;
                fputcsv($fp, $csv);


                $this->info(sprintf("%s - %s", $name, $filename));
            }
        }

//        $progessBar->finish();
        fclose($fp);
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
