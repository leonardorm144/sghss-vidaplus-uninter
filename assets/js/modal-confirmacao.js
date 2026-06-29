document.addEventListener('DOMContentLoaded', function () {
    function criarModalConfirmacao() {
        if (document.getElementById('sghssConfirmOverlay')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.id = 'sghssConfirmOverlay';
        overlay.className = 'sghss-confirm-overlay';
        overlay.innerHTML = `
            <div class="sghss-confirm-modal" role="dialog" aria-modal="true">
                <div class="sghss-confirm-icon" id="sghssConfirmIcon">?</div>

                <div class="sghss-confirm-content">
                    <h3 id="sghssConfirmTitle">Confirmar ação</h3>
                    <p id="sghssConfirmMessage">Deseja continuar?</p>
                </div>

                <div class="sghss-confirm-actions">
                    <button type="button" class="sghss-confirm-cancel" id="sghssConfirmCancel">
                        Cancelar
                    </button>

                    <button type="button" class="sghss-confirm-ok" id="sghssConfirmOk">
                        Confirmar
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);
    }

    function abrirConfirmacao(opcoes) {
        return new Promise(function (resolve) {
            criarModalConfirmacao();

            const overlay = document.getElementById('sghssConfirmOverlay');
            const modal = overlay.querySelector('.sghss-confirm-modal');
            const icon = document.getElementById('sghssConfirmIcon');
            const title = document.getElementById('sghssConfirmTitle');
            const message = document.getElementById('sghssConfirmMessage');
            const cancelButton = document.getElementById('sghssConfirmCancel');
            const okButton = document.getElementById('sghssConfirmOk');

            const tipo = opcoes.tipo || 'default';

            title.textContent = opcoes.titulo || 'Confirmar ação';
            message.textContent = opcoes.mensagem || 'Deseja continuar?';
            okButton.textContent = opcoes.textoConfirmar || 'Confirmar';
            cancelButton.textContent = opcoes.textoCancelar || 'Cancelar';

            modal.classList.remove('is-danger', 'is-success', 'is-warning');

            if (tipo === 'danger') {
                modal.classList.add('is-danger');
                icon.textContent = '!';
            } else if (tipo === 'success') {
                modal.classList.add('is-success');
                icon.textContent = '✓';
            } else if (tipo === 'warning') {
                modal.classList.add('is-warning');
                icon.textContent = '!';
            } else {
                icon.textContent = '?';
            }

            overlay.classList.add('show');

            function fechar(resultado) {
                overlay.classList.remove('show');

                okButton.removeEventListener('click', confirmar);
                cancelButton.removeEventListener('click', cancelar);
                overlay.removeEventListener('click', cliqueFora);
                document.removeEventListener('keydown', teclaEsc);

                resolve(resultado);
            }

            function confirmar() {
                fechar(true);
            }

            function cancelar() {
                fechar(false);
            }

            function cliqueFora(evento) {
                if (evento.target === overlay) {
                    fechar(false);
                }
            }

            function teclaEsc(evento) {
                if (evento.key === 'Escape') {
                    fechar(false);
                }
            }

            okButton.addEventListener('click', confirmar);
            cancelButton.addEventListener('click', cancelar);
            overlay.addEventListener('click', cliqueFora);
            document.addEventListener('keydown', teclaEsc);

            setTimeout(function () {
                okButton.focus();
            }, 100);
        });
    }

    document.querySelectorAll('form[data-confirm]').forEach(function (formulario) {
        formulario.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const confirmou = await abrirConfirmacao({
                titulo: formulario.dataset.confirmTitle,
                mensagem: formulario.dataset.confirm,
                textoConfirmar: formulario.dataset.confirmButton,
                textoCancelar: formulario.dataset.confirmCancel,
                tipo: formulario.dataset.confirmType
            });

            if (confirmou) {
                formulario.submit();
            }
        });
    });

    document.querySelectorAll('a[data-confirm]').forEach(function (link) {
        link.addEventListener('click', async function (evento) {
            evento.preventDefault();

            const confirmou = await abrirConfirmacao({
                titulo: link.dataset.confirmTitle,
                mensagem: link.dataset.confirm,
                textoConfirmar: link.dataset.confirmButton,
                textoCancelar: link.dataset.confirmCancel,
                tipo: link.dataset.confirmType
            });

            if (confirmou) {
                window.location.href = link.href;
            }
        });
    });
});