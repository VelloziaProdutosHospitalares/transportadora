<?php

namespace App\Support;

use App\DTOs\OctalogOrderData;
use App\Models\Company;
use App\Models\Pedido;

final class PedidoOctalogOrderAssembler
{
    /**
     * Remonta o payload de envio Octalog a partir do pedido gravado e do snapshot do destinatário.
     */
    public static function toOctalogOrderData(Pedido $pedido, Company $company): ?OctalogOrderData
    {
        $snap = $pedido->destinatario_snapshot;
        if (! is_array($snap) || $snap === []) {
            return null;
        }

        $name = isset($snap['recipient_name']) ? trim((string) $snap['recipient_name']) : '';
        if ($name === '') {
            return null;
        }

        $docDest = isset($snap['document']) ? trim((string) $snap['document']) : '';
        $telDest = isset($snap['phone']) ? trim((string) $snap['phone']) : '';
        $emailDest = isset($snap['email']) ? trim((string) $snap['email']) : '';

        $cepDigits = isset($snap['postal_code']) ? preg_replace('/\D+/', '', (string) $snap['postal_code']) : '';
        $street = isset($snap['street']) ? trim((string) $snap['street']) : '';
        $number = isset($snap['number']) ? trim((string) $snap['number']) : '';
        $district = isset($snap['district']) ? trim((string) $snap['district']) : '';
        $city = isset($snap['city']) ? trim((string) $snap['city']) : '';
        $state = isset($snap['state']) ? strtoupper(trim((string) $snap['state'])) : '';

        $dadosFiscais = [
            'ChaveNotaFiscal' => $pedido->chave_nf,
            'NumeroNotaFiscal' => (string) $pedido->numero_nf,
            'SerieNotaFiscal' => (string) $pedido->serie_nf,
            'ValorTotalReais' => (float) $pedido->valor_total,
        ];

        $destinatario = [
            'Nome' => $name,
            'Documento' => $docDest !== '' ? $docDest : null,
            'InscricaoEstadual' => null,
            'Endereco' => $street,
            'Numero' => $number,
            'Bairro' => $district,
            'Cidade' => $city,
            'PontoReferencia' => '',
            'Complemento' => trim((string) ($snap['complement'] ?? '')) !== '' ? trim((string) $snap['complement']) : '',
            'CEP' => $cepDigits,
            'UF' => $state,
            'Telefone' => $telDest,
            'Email' => $emailDest !== '' ? $emailDest : null,
        ];

        $remetente = [
            'RazaoSocial' => $company->legal_name,
            'CNPJ' => preg_replace('/\D+/', '', (string) $company->cnpj),
            'InscricaoEstadual' => $company->state_registration,
            'Endereco' => $company->street,
            'Numero' => $company->number,
            'Bairro' => $company->district,
            'Cidade' => $company->city,
            'CEP' => preg_replace('/\D+/', '', (string) $company->postal_code),
            'UF' => strtoupper((string) $company->state),
            'Telefone' => $company->phone,
            'Email' => $company->email,
        ];

        return new OctalogOrderData(
            pedido: $pedido->numero_pedido,
            idPrazoEntrega: (int) $pedido->id_prazo_entrega,
            totalVolumes: max(1, (int) $pedido->total_volumes),
            dataVenda: null,
            dadosFiscais: $dadosFiscais,
            remetente: $remetente,
            destinatario: $destinatario,
        );
    }
}
