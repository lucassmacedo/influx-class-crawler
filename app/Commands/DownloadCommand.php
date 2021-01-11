<?php

namespace App\Commands;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

use Facebook\WebDriver\Remote\RemoteWebDriver;

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
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        // Chromedriver (if started using --port=4444 as above)
        $serverUrl = 'http://localhost:4444';
        $driver = RemoteWebDriver::create($serverUrl, DesiredCapabilities::chrome());


        $this->login($driver);
        $this->get_modules_list($driver);


    }

    /**
     * @param RemoteWebDriver $driver
     */
    private function login(RemoteWebDriver $driver)
    {
        $driver->get(sprintf("https://%s.club.hotmart.com", env('DOMAIN')));
        // Find search element by its id, write 'PHP' inside and submit
        $driver->findElement(WebDriverBy::name('login'))->sendKeys(env('LOGIN'));
        $driver->findElement(WebDriverBy::name('password'))->sendKeys(ENV('PASSWORD'));
        $driver->findElement(WebDriverBy::className('btn-login'))->click();
    }

    /**
     * @param RemoteWebDriver $driver
     */
    private function get_modules_list(RemoteWebDriver $driver)
    {
        // Find search element by its id, write 'PHP' inside and submit
        $driver->wait(10, 1000);

        file_put_contents('test.html', $driver->getPageSource());

    }// Declare own callable function which could be passed to `$driver->wait()->until()`.

    function jqueryAjaxFinished(): callable
    {
        return static function ($driver): bool {
            return $driver->executeScript('return jQuery.active === 0;');
        };
    }
}
