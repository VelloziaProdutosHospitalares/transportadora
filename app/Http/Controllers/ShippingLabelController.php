<?php

namespace App\Http\Controllers;

use App\Models\ShippingLabel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ShippingLabelController extends Controller
{
    public function index(): View
    {
        $labels = ShippingLabel::query()
            ->with('pedido')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('etiquetas.index', compact('labels'));
    }

    public function markPrinted(ShippingLabel $shippingLabel): RedirectResponse
    {
        if ($shippingLabel->printed_at === null) {
            $shippingLabel->update(['printed_at' => now()]);
        }

        return back()->with('success', 'Etiqueta marcada como impressa.');
    }
}
