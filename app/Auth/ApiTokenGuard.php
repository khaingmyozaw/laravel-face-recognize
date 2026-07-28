<?php

namespace App\Auth;

use Illuminate\Auth\RequestGuard;
use Illuminate\Http\Request;

class ApiTokenGuard extends RequestGuard
{
    public function setRequest(Request $request): static
    {
        $this->user = null;

        parent::setRequest($request);

        return $this;
    }
}
