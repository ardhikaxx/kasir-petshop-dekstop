<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingRequest;
use App\Services\StoreSettingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function __construct(
        protected StoreSettingService $settingService
    ) {}

    /**
     * Display settings page.
     */
    public function index(): View
    {
        $settings = $this->settingService->getAll();

        return view('settings.index', compact('settings'));
    }

    /**
     * Update store settings.
     */
    public function update(SettingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('store_logo')) {
            $this->settingService->saveLogo($request->file('store_logo'));
            unset($data['store_logo']);
        }

        // Set tax_enabled properly as boolean string '1' or '0'
        $data['tax_enabled'] = $request->boolean('tax_enabled') ? '1' : '0';

        $this->settingService->updateMany($data);

        return redirect()->route('settings.index')
            ->with('success', 'Pengaturan toko berhasil diperbarui.');
    }
}
