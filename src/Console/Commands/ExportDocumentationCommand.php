<?php

namespace Styde\Enlighten\Console\Commands;

use Illuminate\Console\Command;
use Styde\Enlighten\Console\DocumentationExporter;
use Styde\Enlighten\Models\Run;

class ExportDocumentationCommand extends Command
{
    protected $name = 'enlighten:export';

    protected $description = 'Export the documentation generated by Enlighten as static HTML files';

    /**
     * @var DocumentationExporter
     */
    private $exporter;

    public function __construct(DocumentationExporter $exporter)
    {
        parent::__construct();

        $this->exporter = $exporter;
    }

    public function handle()
    {
        $runs = $this->getLatestRuns();

        if ($runs->isEmpty()) {
            $this->line('');
            $this->warn('There are no runs available. Please setup `Enlighten` and run the tests first.');
            return;
        }

        $selectedRun = $runs->firstWhere('signature', $this->choice(
            "Please select the run you'd like to export",
            $runs->pluck('signature')->all(),
            $runs->first()->signature
        ));

        $baseDir = $this->ask('In which directory would you like to export the documentation?', config('enlighten.docs_base_dir'));

        $baseUrl = $this->ask("What's the base URL for this documentation going to be?", config('enlighten.docs_base_url'));

        $this->warn("Exporting the documentation for `{$selectedRun->signature}`...\n");

        $this->exporter->export($selectedRun, $baseDir, $baseUrl);

        $this->info("`{$selectedRun->signature}` run exported!");
    }

    protected function getLatestRuns()
    {
        return Run::query()
            ->latest()
            ->take(5)
            ->get();
    }
}
