<?php

namespace Tests\Unit;

use App\Support\OctalogStatusAtividade;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OctalogStatusAtividadeTest extends TestCase
{
    #[Test]
    public function resolve_por_id_status_da_documentacao_octalog(): void
    {
        $label = OctalogStatusAtividade::labelFromResponseRow([
            'IDStatusAtividade' => 10,
        ]);

        $this->assertSame('Entrega Realizada', $label);
    }

    #[Test]
    public function resolve_por_id_status_como_na_resposta_de_teste_integration(): void
    {
        $label = OctalogStatusAtividade::labelFromResponseRow([
            'IDStatus' => 1,
            'Status' => 'Qualquer texto',
        ]);

        $this->assertSame('Integração Recebida', $label);
    }

    #[Test]
    public function fallback_para_texto_sem_id_conhecido(): void
    {
        $label = OctalogStatusAtividade::labelFromResponseRow([
            'Status' => 'Texto apenas',
        ]);

        $this->assertSame('Texto apenas', $label);
    }

    #[Test]
    public function id_desconhecido_retorna_id_numerico(): void
    {
        $label = OctalogStatusAtividade::labelFromResponseRow([
            'IDStatus' => 9999,
        ]);

        $this->assertSame('ID 9999', $label);
    }
}
