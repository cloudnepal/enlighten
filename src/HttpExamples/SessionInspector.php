<?php

namespace Styde\Enlighten\HttpExamples;

use Illuminate\Session\Store as SessionStore;

class SessionInspector
{
    public function __construct(private readonly SessionStore $session)
    {
    }

    public function getData(): array
    {
        $session = $this->session->all();

        // Wrap the errors array in a collection so it can be
        // exported by calling the toArray method since the
        // error bags implement the Arrayable interface.
        if (! empty($session['errors'])) {
            $session['errors'] = collect($session['errors']->getBags());
        }

        return collect($session)->toArray();
    }
}
