<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\ShippingLabel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShippingLabelController extends Controller
{
    public function index(Company $company): View
    {
        $labels = ShippingLabel::query()
            ->whereHas('pedido', static fn ($q) => $q->where('company_id', $company->id))
            ->with('pedido')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('etiquetas.index', compact('company', 'labels'));
    }

    public function markPrinted(Company $company, ShippingLabel $shippingLabel): RedirectResponse
    {
        $shippingLabel->loadMissing('pedido');
        $pedido = $shippingLabel->pedido;

        if ($pedido === null || $pedido->company_id !== $company->id) {
            throw new HttpException(404);
        }

        if ($shippingLabel->printed_at === null) {
            $shippingLabel->update(['printed_at' => now()]);
        }

        return back()->with('success', 'Etiqueta marcada como impressa.');
    }
}
