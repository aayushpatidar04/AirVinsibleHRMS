<?php

namespace App\Console\Commands;

use App\Models\RegistrationForm;
use Illuminate\Console\Command;

class SyncSystemFields extends Command
{
    protected $signature = 'forms:sync-system-fields';
    protected $description = 'Add/update system fields on all existing forms';

    public function handle(): void
    {
        $forms = RegistrationForm::with('fields')->get();
        $count = 0;

        foreach ($forms as $form) {
            $form->syncSystemFields();
            $count++;
            $this->info("Synced: {$form->name}");
        }

        $this->info("Done! {$count} forms updated.");
    }
}