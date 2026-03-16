<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Support\Facades\Gate;

class DomainController extends Controller
{
    public function index()
    {
        return view('domains.index');
    }

    public function create()
    {
        return view('domains.create');
    }

    public function edit(Domain $domain)
    {
        Gate::authorize('view', $domain);
        return view('domains.edit', ['domain' => $domain]);
    }

    public function history(Domain $domain)
    {
        Gate::authorize('view', $domain);
        return view('domains.history', ['domain' => $domain]);
    }
}
