<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OctalogPedidosWebhookConfigController;
use App\Http\Controllers\OctalogSacWebhookConfigController;
use App\Http\Controllers\PedidoConsultaOctalogController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PedidoSacTicketController;
use App\Http\Controllers\SerproNfeConsultaController;
use App\Http\Controllers\ShippingLabelController;
use App\Models\Pedido;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('empresas.index');
});

Route::permanentRedirect('empresa', '/empresas');
Route::redirect('empresa/logo', '/empresas');

Route::post('pedidos/consulta-nfe-serpro', SerproNfeConsultaController::class)
    ->name('pedidos.consulta-nfe-serpro');

Route::redirect('/pedidos', '/empresas');
Route::redirect('/pedidos/create', '/empresas');
Route::redirect('/pedidos/consulta-octalog', '/empresas');

Route::get('/pedidos/{pedido}', function (Pedido $pedido) {
    return redirect()->route('empresas.pedidos.show', [$pedido->company, $pedido]);
})->name('legacy.pedidos.show');

Route::get('/pedidos/{pedido}/sac/ticket/create', function (Pedido $pedido) {
    return redirect()->route('empresas.pedidos.sac.ticket.create', [$pedido->company, $pedido]);
});

Route::get('/pedidos/{pedido}/sac/ticket/cancel', function (Pedido $pedido) {
    return redirect()->route('empresas.pedidos.sac.ticket.cancel.create', [$pedido->company, $pedido]);
});

Route::redirect('/etiquetas', '/empresas');

Route::prefix('empresas')->group(function () {
    Route::get('/', [CompanyController::class, 'index'])->name('empresas.index');
    Route::get('/criar', [CompanyController::class, 'create'])->name('empresas.create');
    Route::post('/', [CompanyController::class, 'store'])->name('empresas.store');

    Route::prefix('{company}')
        ->name('empresas.')
        ->scopeBindings()
        ->group(function () {
            Route::get('editar', [CompanyController::class, 'edit'])->name('edit');
            Route::put('/', [CompanyController::class, 'update'])->name('update');
            Route::get('logo', [CompanyController::class, 'showLogo'])->name('logo');

            Route::get('consulta-octalog', [PedidoConsultaOctalogController::class, 'create'])
                ->name('consulta_octalog.create');
            Route::post('consulta-octalog', [PedidoConsultaOctalogController::class, 'store'])
                ->name('consulta_octalog.store');

            Route::get('pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
            Route::get('pedidos/criar', [PedidoController::class, 'create'])->name('pedidos.create');
            Route::post('pedidos', [PedidoController::class, 'store'])->name('pedidos.store');
            Route::post(
                'pedidos/reenviar-em-massa-octalog',
                [PedidoController::class, 'bulkResendToOctalog'],
            )->name('pedidos.bulk_resend_octalog');
            Route::get('pedidos/{pedido}', [PedidoController::class, 'show'])->name('pedidos.show');
            Route::post('pedidos/{pedido}/reenviar-octalog', [PedidoController::class, 'resendToOctalog'])
                ->name('pedidos.resend_octalog');

            Route::get('etiquetas', [ShippingLabelController::class, 'index'])->name('etiquetas.index');
            Route::post(
                'etiquetas/{shippingLabel}/marcar-impressa',
                [ShippingLabelController::class, 'markPrinted'],
            )->name('etiquetas.mark-printed');

            Route::get(
                'pedidos/{pedido}/sac/ticket/criar',
                [PedidoSacTicketController::class, 'create'],
            )->name('pedidos.sac.ticket.create');
            Route::post(
                'pedidos/{pedido}/sac/ticket',
                [PedidoSacTicketController::class, 'store'],
            )->name('pedidos.sac.ticket.store');
            Route::get(
                'pedidos/{pedido}/sac/ticket/cancelar',
                [PedidoSacTicketController::class, 'cancelCreate'],
            )->name('pedidos.sac.ticket.cancel.create');
            Route::delete(
                'pedidos/{pedido}/sac/ticket',
                [PedidoSacTicketController::class, 'cancel'],
            )->name('pedidos.sac.ticket.cancel');
        });
});

Route::get('octalog/sac/webhook', [OctalogSacWebhookConfigController::class, 'index'])
    ->name('octalog.sac.webhook.index');
Route::post('octalog/sac/webhook', [OctalogSacWebhookConfigController::class, 'update'])
    ->name('octalog.sac.webhook.update');
Route::post('octalog/sac/webhook/consultar', [OctalogSacWebhookConfigController::class, 'consultar'])
    ->name('octalog.sac.webhook.consultar');

Route::get('octalog/pedidos/webhook', [OctalogPedidosWebhookConfigController::class, 'index'])
    ->name('octalog.pedidos.webhook.index');
Route::post('octalog/pedidos/webhook', [OctalogPedidosWebhookConfigController::class, 'update'])
    ->name('octalog.pedidos.webhook.update');
Route::post('octalog/pedidos/webhook/consultar', [OctalogPedidosWebhookConfigController::class, 'consultar'])
    ->name('octalog.pedidos.webhook.consultar');
