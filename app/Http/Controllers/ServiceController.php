<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServiceRequest;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ServiceController extends Controller
{
    /**
     * Display pet shop services list.
     */
    public function index(): View
    {
        $services = Service::query()
            ->with('category')
            ->orderBy('name')
            ->paginate(15);

        return view('services.index', compact('services'));
    }

    /**
     * Show create service form.
     */
    public function create(): View
    {
        $categories = Category::all();
        $suggestedCode = 'SRV-'.str_pad((string) (Service::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT);

        return view('services.create', compact('categories', 'suggestedCode'));
    }

    /**
     * Store newly created service.
     */
    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::create($request->validated());

        return redirect()->route('services.index')
            ->with('success', "Layanan [{$service->name}] berhasil ditambahkan.");
    }

    /**
     * Show edit service form.
     */
    public function edit(Service $service): View
    {
        $categories = Category::all();

        return view('services.edit', compact('service', 'categories'));
    }

    /**
     * Update service.
     */
    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->validated());

        return redirect()->route('services.index')
            ->with('success', "Layanan [{$service->name}] berhasil diperbarui.");
    }

    /**
     * Delete service safely (soft delete).
     */
    public function destroy(Service $service): RedirectResponse
    {
        $name = $service->name;
        $service->delete();

        return redirect()->route('services.index')
            ->with('success', "Layanan [{$name}] berhasil dihapus.");
    }

    /**
     * Toggle service active status.
     */
    public function toggle(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);
        $stateLabel = $service->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Layanan [{$service->name}] berhasil {$stateLabel}.");
    }
}
