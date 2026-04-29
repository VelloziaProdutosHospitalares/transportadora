<?php

namespace Tests\Unit;

use App\Http\Requests\StorePedidoRequest;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class StorePedidoRequestValorMoedaTest extends TestCase
{
    private function normalizarValor(mixed $entrada): mixed
    {
        $method = new ReflectionMethod(StorePedidoRequest::class, 'normalizarValorMonetarioParaValidacao');
        $method->setAccessible(true);

        return $method->invoke(null, $entrada);
    }

    #[Test]
    public function formato_brasileiro_com_milhar_e_centavos_tipo_prefill_serpro(): void
    {
        $this->assertSame(1800.0, $this->normalizarValor('1.800,00'));
    }

    #[Test]
    public function apenas_centavos_e_milhar_sem_centavos_como_ponto(): void
    {
        $this->assertSame(10.52, $this->normalizarValor('10,52'));
        $this->assertSame(1800.0, $this->normalizarValor('1.800'));
    }

    #[Test]
    public function apenas_digitos_e_decimal_estilo_internacional(): void
    {
        $this->assertSame(424.91, $this->normalizarValor('424,91'));
        $this->assertSame(424.91, $this->normalizarValor('424.91'));
        $this->assertSame(1000.0, $this->normalizarValor('1000'));
    }

    #[Test]
    public function prefixo_rs_opcional(): void
    {
        $this->assertSame(250.4, $this->normalizarValor('R$ 250,40'));
        $this->assertSame(250.4, $this->normalizarValor('R$250,40'));
    }
}
