document.addEventListener('DOMContentLoaded', function () {
    function somenteNumeros(valor) {
        return valor.replace(/\D/g, '');
    }

    function aplicarMascaraCPF(valor) {
        valor = somenteNumeros(valor).slice(0, 11);

        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d)/, '$1.$2');
        valor = valor.replace(/(\d{3})(\d{1,2})$/, '$1-$2');

        return valor;
    }

    function aplicarMascaraTelefone(valor) {
        valor = somenteNumeros(valor).slice(0, 11);

        if (valor.length <= 10) {
            valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
            valor = valor.replace(/(\d{4})(\d)/, '$1-$2');
        } else {
            valor = valor.replace(/(\d{2})(\d)/, '($1) $2');
            valor = valor.replace(/(\d{5})(\d)/, '$1-$2');
        }

        return valor;
    }

    function configurarMascara(campo, tipo) {
        if (!campo) {
            return;
        }

        campo.setAttribute('inputmode', 'numeric');

        if (tipo === 'cpf') {
            campo.setAttribute('maxlength', '14');
            campo.placeholder = campo.placeholder || '000.000.000-00';

            campo.addEventListener('input', function () {
                campo.value = aplicarMascaraCPF(campo.value);
            });

            campo.value = aplicarMascaraCPF(campo.value);
        }

        if (tipo === 'telefone') {
            campo.setAttribute('maxlength', '15');
            campo.placeholder = campo.placeholder || '(00) 00000-0000';

            campo.addEventListener('input', function () {
                campo.value = aplicarMascaraTelefone(campo.value);
            });

            campo.value = aplicarMascaraTelefone(campo.value);
        }
    }

    const camposCpf = document.querySelectorAll(
        'input[name="cpf"], input#cpf'
    );

    const camposTelefone = document.querySelectorAll(
        'input[name="telefone"], input#telefone, input[name="telefone_emergencia"], input#telefone_emergencia'
    );

    camposCpf.forEach(function (campo) {
        configurarMascara(campo, 'cpf');
    });

    camposTelefone.forEach(function (campo) {
        configurarMascara(campo, 'telefone');
    });
});