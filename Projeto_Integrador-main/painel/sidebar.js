// Este evento garante que o script só vai rodar depois que todo o HTML da página for carregado.
// Isso previne erros de "elemento não encontrado".
document.addEventListener('DOMContentLoaded', () => {

    // --- LÓGICA DA SIDEBAR (agora centralizada aqui) ---
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('toggleBtn');
    const iconToggle = document.getElementById('iconToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    // Verifica se os elementos essenciais da sidebar existem antes de continuar.
    // Isso evita erros em páginas que talvez não tenham a sidebar (como a de login).
    if (!sidebar || !toggleBtn || !sidebarOverlay) {
        return; 
    }

    // Função para ABRIR a sidebar
    function abrirSidebar() {
        sidebar.classList.add('expanded');
        sidebar.classList.remove('collapsed');
        sidebarOverlay.classList.add('visible');
        localStorage.setItem('sidebarCollapsed', 'false');
        if (iconToggle) updateIcon();
    }

    // Função para FECHAR a sidebar
    function fecharSidebar() {
        sidebar.classList.add('collapsed');
        sidebar.classList.remove('expanded');
        sidebarOverlay.classList.remove('visible');
        localStorage.setItem('sidebarCollapsed', 'true');
        if (iconToggle) updateIcon();
    }

    // Função para atualizar o ícone do botão
    function updateIcon() {
        if (!iconToggle) return;
        // No celular, o ícone deve ser sempre o de menu quando fechado e de fechar quando aberto
        if (window.innerWidth <= 768) {
            iconToggle.textContent = sidebar.classList.contains('expanded') ? 'close' : 'menu';
        } else { // Comportamento para desktop
            iconToggle.textContent = sidebar.classList.contains('collapsed') ? 'menu' : 'chevron_left';
        }
    }

    // Evento de clique no botão principal
    toggleBtn.addEventListener('click', () => {
        if (sidebar.classList.contains('collapsed')) {
            abrirSidebar();
        } else {
            fecharSidebar();
        }
    });

    // Evento de clique no OVERLAY para fechar o menu
    sidebarOverlay.addEventListener('click', () => {
        fecharSidebar();
    });

    // Estado inicial da sidebar ao carregar a página
    // Apenas no desktop respeitamos o localStorage. No mobile, começa sempre fechada.
    if (window.innerWidth > 768) {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            fecharSidebar();
        } else {
            abrirSidebar();
        }
    } else {
        fecharSidebar(); // Força o estado fechado inicial no mobile
    }
    
    // Atualiza o ícone caso o tamanho da janela mude
    window.addEventListener('resize', updateIcon);

});
