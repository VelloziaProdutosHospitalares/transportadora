<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function edit(): View
    {
        $company = Company::query()->first();

        return view('empresa.edit', compact('company'));
    }

    /**
     * Serve o arquivo da logo a partir de storage/app/public (não depende de symlink public/storage).
     */
    public function showLogo(): StreamedResponse
    {
        $company = Company::query()->first();
        $path = $company?->logo_path;

        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['logo_path'] = $this->storeLogo($request);

        Company::query()->create($data);

        return redirect()
            ->route('empresa.edit')
            ->with('success', 'Dados da empresa salvos com sucesso.');
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $company = Company::query()->firstOrFail();
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $this->storeLogo($request);
        } else {
            $data['logo_path'] = $company->logo_path;
        }

        $company->update($data);

        return redirect()
            ->route('empresa.edit')
            ->with('success', 'Dados da empresa atualizados com sucesso.');
    }

    private function storeLogo(StoreCompanyRequest|UpdateCompanyRequest $request): ?string
    {
        if (! $request->hasFile('logo')) {
            return null;
        }

        return $request->file('logo')?->store('company-logos', 'public');
    }
}
