<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustHosts as Middleware;

class TrustHosts extends Middleware
{
    public function hosts()
    {
        return [
            '.*\.ngrok-free\.dev',
            '.*\.ngrok-free\.app',
            '.*\.ngrok\.io',
            $this->allSubdomainsOfApplicationUrl(),
        ];
    }
}