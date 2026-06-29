document.addEventListener('DOMContentLoaded', function () {
    /*
     * No mobile a sidebar vira menu inferior.
     * Então não precisamos mexer no scroll dela.
     */
    if (window.innerWidth <= 768) {
        return;
    }

    const sidebar = document.querySelector('.sidebar');
    const sidebarMenu = document.querySelector('.sidebar-menu');
    const itemAtivo = document.querySelector('.sidebar-menu a.active');

    if (!sidebar || !sidebarMenu) {
        return;
    }

    /*
     * Detecta qual elemento realmente possui scroll.
     * Dependendo do CSS, pode ser .sidebar ou .sidebar-menu.
     */
    const areaScroll = sidebarMenu.scrollHeight > sidebarMenu.clientHeight + 5
        ? sidebarMenu
        : sidebar;

    const chaveScroll = 'sghss_sidebar_scroll_top';

    /*
     * Primeiro tenta restaurar o scroll salvo.
     */
    const scrollSalvo = sessionStorage.getItem(chaveScroll);

    if (scrollSalvo !== null) {
        areaScroll.scrollTop = parseInt(scrollSalvo, 10) || 0;
    }

    /*
     * Depois garante que o item ativo fique visível.
     * Isso ajuda quando acessa direto uma página, sem ter clicado no menu.
     */
    if (itemAtivo) {
        setTimeout(function () {
            const menuRect = areaScroll.getBoundingClientRect();
            const ativoRect = itemAtivo.getBoundingClientRect();

            const itemAcima = ativoRect.top < menuRect.top + 20;
            const itemAbaixo = ativoRect.bottom > menuRect.bottom - 20;

            if (itemAcima || itemAbaixo) {
                itemAtivo.scrollIntoView({
                    block: 'center',
                    inline: 'nearest'
                });
            }
        }, 80);
    }

    /*
     * Salva a posição sempre que o usuário rolar a sidebar.
     */
    areaScroll.addEventListener('scroll', function () {
        sessionStorage.setItem(chaveScroll, areaScroll.scrollTop);
    });

    /*
     * Salva também no clique dos menus, antes da página trocar.
     */
    const linksMenu = sidebarMenu.querySelectorAll('a');

    linksMenu.forEach(function (link) {
        link.addEventListener('click', function () {
            sessionStorage.setItem(chaveScroll, areaScroll.scrollTop);
        });
    });
});