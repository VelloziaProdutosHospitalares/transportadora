{{-- Busca endereço no ViaCEP ao completar 8 dígitos (debounce no input) e no blur; formata CEP 00000-000. --}}
@php
    /** @var array{cepId:string,streetId:string,districtId:string,cityId:string,stateId:string,complementId?:string|null} $viaCep */
    $viaCep = $viaCep ?? [];
@endphp
<script>
    (function (c) {
        'use strict';
        if (!c || !c.cepId) {
            return;
        }
        var cepEl = document.getElementById(c.cepId);
        if (!cepEl) {
            return;
        }
        var street = c.streetId ? document.getElementById(c.streetId) : null;
        var district = c.districtId ? document.getElementById(c.districtId) : null;
        var city = c.cityId ? document.getElementById(c.cityId) : null;
        var state = c.stateId ? document.getElementById(c.stateId) : null;
        var complement = c.complementId ? document.getElementById(c.complementId) : null;

        function digits(s) {
            return (s || '').replace(/\D/g, '');
        }

        function maskCep(d) {
            if (d.length !== 8) {
                return null;
            }

            return d.slice(0, 5) + '-' + d.slice(5);
        }

        function apply(data) {
            if (!data || data.erro) {
                return;
            }
            if (street) {
                street.value = data.logradouro || '';
            }
            if (district) {
                district.value = data.bairro || '';
            }
            if (city) {
                city.value = data.localidade || '';
            }
            if (state) {
                state.value = (data.uf || '').toUpperCase();
            }
            if (complement && data.complemento && !(complement.value || '').trim()) {
                complement.value = data.complemento;
            }
        }

        function fetchCep() {
            var d = digits(cepEl.value);
            if (d.length !== 8) {
                return;
            }
            fetch('https://viacep.com.br/ws/' + d + '/json/')
                .then(function (r) {
                    return r.json();
                })
                .then(apply)
                .catch(function () {});
        }

        var debounce = null;
        cepEl.addEventListener('blur', function () {
            var d = digits(cepEl.value);
            if (d.length === 8) {
                cepEl.value = maskCep(d);
            }
            fetchCep();
        });
        cepEl.addEventListener('input', function () {
            if (debounce) {
                clearTimeout(debounce);
            }
            debounce = setTimeout(function () {
                debounce = null;
                if (digits(cepEl.value).length === 8) {
                    fetchCep();
                }
            }, 450);
        });
    })(@json($viaCep));
</script>
