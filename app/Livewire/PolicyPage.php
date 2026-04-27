<?php

namespace App\Livewire;

use App\Models\Setting;
use Illuminate\Support\Str;
use Livewire\Component;

class PolicyPage extends Component
{
    public function render()
    {
        $policyContent = Setting::get('policy_content');

        return view('livewire.policy-page', [
            'policyHtml' => filled($policyContent)
                ? Str::markdown($policyContent, [
                    'renderer' => [
                        'soft_break' => '<br />',
                    ],
                ])
                : null,
            'policyUpdatedAt' => Setting::get('policy_updated_at'),
        ]);
    }
}
