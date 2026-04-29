<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OctalogSacWebhookConfigController;
use App\Http\Controllers\PedidoConsultaOctalogController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\PedidoSacTicketController;
use App\Http\Controllers\SerproNfeConsultaController;
use App\Http\Controllers\ShippingLabelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('pedidos.index');
});

Route::post('pedidos/consulta-nfe-serpro', SerproNfeConsultaController::class)
    ->name('pedidos.consulta-nfe-serpro');

Route::get('pedidos/consulta-octalog', [PedidoConsultaOctalogController::class, 'create'])
    ->name('pedidos.consulta-octalog.create');
Route::post('pedidos/consulta-octalog', [PedidoConsultaOctalogController::class, 'store'])
    ->name('pedidos.consulta-octalog.store');

Route::resource('pedidos', PedidoController::class)->only(['index', 'create', 'store', 'show']);

Route::get('empresa', [CompanyController::class, 'edit'])->name('empresa.edit');
Route::get('empresa/logo', [CompanyController::class, 'showLogo'])->name('empresa.logo');
Route::post('empresa', [CompanyController::class, 'store'])->name('empresa.store');
Route::put('empresa', [CompanyController::class, 'update'])->name('empresa.update');

Route::get('etiquetas', [ShippingLabelController::class, 'index'])->name('etiquetas.index');
Route::post('etiquetas/{shippingLabel}/marcar-impressa', [ShippingLabelController::class, 'markPrinted'])
    ->name('etiquetas.mark-printed');

Route::get('octalog/sac/webhook', [OctalogSacWebhookConfigController::class, 'index'])
    ->name('octalog.sac.webhook.index');
Route::post('octalog/sac/webhook', [OctalogSacWebhookConfigController::class, 'update'])
    ->name('octalog.sac.webhook.update');
Route::post('octalog/sac/webhook/consultar', [OctalogSacWebhookConfigController::class, 'consultar'])
    ->name('octalog.sac.webhook.consultar');

Route::get('pedidos/{pedido}/sac/ticket/create', [PedidoSacTicketController::class, 'create'])
    ->name('pedidos.sac.ticket.create');
Route::post('pedidos/{pedido}/sac/ticket', [PedidoSacTicketController::class, 'store'])
    ->name('pedidos.sac.ticket.store');
Route::get('pedidos/{pedido}/sac/ticket/cancel', [PedidoSacTicketController::class, 'cancelCreate'])
    ->name('pedidos.sac.ticket.cancel.create');
Route::delete('pedidos/{pedido}/sac/ticket', [PedidoSacTicketController::class, 'cancel'])
    ->name('pedidos.sac.ticket.cancel');
