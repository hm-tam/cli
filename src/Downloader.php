<?php

namespace Statamic\Installer\Console;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Symfony\Component\Console\Helper\ProgressBar;

trait Downloader
{
    protected $progressBar;

    /**
     * Download the temporary Zip to the given file.
     *
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function download($zipFile)
    {
        $this->output->writeln('<info>Downloading Statamic. Please wait...</info>');
        $this->output->writeln('Press Ctrl+C to cancel.');

        $client = new Client([
            'progress' => function ($downloadSize, $downloaded) {
                if ($downloadSize === 0) {
                    return;
                }

                if ($this->progressBar === null) {
                    $this->createProgressBar($downloadSize);
                }

                $this->progressBar->setProgress($downloaded);
            }
        ]);

        $request = new Request('GET', 'https://outpost.statamic.com/v2/get/2.5.11');

        $response = $client->send($request);

        file_put_contents($zipFile, $response->getBody());

        $this->output->writeln("\n<info>Download complete!</info>");

        return $this;
    }

    protected function createProgressBar($downloadSize)
    {
        ProgressBar::setPlaceholderFormatterDefinition('max', function (ProgressBar $bar) {
            return $this->formatBytes($bar->getMaxSteps());
        });

        ProgressBar::setPlaceholderFormatterDefinition('current', function (ProgressBar $bar) {
            return str_pad($this->formatBytes($bar->getProgress()), 11, ' ', STR_PAD_LEFT);
        });

        $this->progressBar = new ProgressBar($this->output, $downloadSize);
        $this->progressBar->setFormat('%current% / %max% %bar% %percent:3s%%');
        $this->progressBar->setRedrawFrequency(max(1, floor($downloadSize / 1000)));
        $this->progressBar->setBarWidth(60);

        $this->progressBar->start();
    }

    protected function formatBytes($bytes)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = $bytes ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return number_format($bytes, 2).' '.$units[$pow];
    }
}
