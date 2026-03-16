<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Resources\DomainCheckResource;
use App\Http\Resources\DomainResource;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DomainController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Auth::user()->domains()->with('latestCheck');

        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('domain', 'like', "%{$searchTerm}%");
            });
        }

        $domains = $query->latest()->paginate(15);

        return DomainResource::collection($domains)->response();
    }

    public function store(StoreDomainRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cleanDomain = $this->prepareHost($validated['domain']);

        $domain = Auth::user()->domains()->create(array_merge($validated, [
            'domain' => $cleanDomain,
            'host' => $cleanDomain,
            'scheme' => 'https', // Default to https
            'next_check_at' => now(),
        ]));

        return (new DomainResource($domain))->response()->setStatusCode(201);
    }

    public function show(Domain $domain): JsonResponse
    {
        Gate::authorize('view', $domain);
        $domain->load('latestCheck');
        return (new DomainResource($domain))->response();
    }

    public function update(UpdateDomainRequest $request, Domain $domain): JsonResponse
    {
        Gate::authorize('update', $domain);

        $validated = $request->validated();

        if (isset($validated['domain'])) {
            $cleanDomain = $this->prepareHost($validated['domain']);
            $validated['domain'] = $cleanDomain;
            $validated['host'] = $cleanDomain;
        }

        $domain->update($validated);

        return (new DomainResource($domain->fresh()))->response();
    }

    public function destroy(Domain $domain): JsonResponse
    {
        Gate::authorize('delete', $domain);
        $domain->delete();
        return response()->json(null, 204);
    }

    public function history(Domain $domain): JsonResponse
    {
        Gate::authorize('view', $domain);

        $checks = $domain->checks()->paginate(20);

        return DomainCheckResource::collection($checks)->response();
    }

    private function prepareHost(string $domain): string
    {
        // Remove scheme and path, leaving only the host
        $domain = preg_replace("~^(?:f|ht)tps?://~i", "", $domain);
        // Remove path
        $domain = explode('/', $domain)[0];
        return rtrim($domain);
    }
}
