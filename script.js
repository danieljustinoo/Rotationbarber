document.addEventListener("DOMContentLoaded", () => {
    // Animações iniciais
    const heroText = document.querySelector(".hero-text");
    if (heroText) heroText.classList.add("fade-in");

    const icons = document.querySelectorAll(".icons a");
    icons.forEach((icon, index) => {
        setTimeout(() => {
            icon.classList.add("fade-in");
        }, index * 300);
    });

    const header = document.querySelector("header");
    if (header) {
        window.addEventListener("scroll", () => {
            if (window.scrollY > 50) {
                header.classList.add("active");
            } else {
                header.classList.remove("active");
            }
        });
    }

    // Identificar a página atual
    const currentPage = window.location.pathname.split('/').pop() || 'rotationbarber.html';

    // Lógica específica por página
    if (currentPage === 'rotationbarber.html') {
        // Apenas vincular o botão "Marcar Agora" na página inicial
        document.querySelectorAll('.btn-mark-now').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';
                if (isLoggedIn) {
                    console.log("Usuário logado, redirecionando para reservations.html...");
                    window.location.href = 'reservations.html';
                } else {
                    console.log("Usuário não logado, redirecionando para login...");
                    redirectToLogin();
                }
            });
        });
    } else if (currentPage === 'reservations.html') {
        // Verificação obrigatória de login na página de reservas
        const isLoggedIn = sessionStorage.getItem('isLoggedIn') === 'true';
        if (!isLoggedIn) {
            window.location.href = 'login.html';
            return;
        }

        // Inicializar intlTelInput
        const phoneInput = document.querySelector("#phone");
        if (phoneInput) {
            window.intlTelInput(phoneInput, {
                initialCountry: "pt",
                separateDialCode: true,
                preferredCountries: ["pt", "br", "es", "fr"],
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
            });
        }

        // Fetch user data
        fetch('get-user-data.php', {
            method: 'GET',
            credentials: 'include'
        })
        .then(response => {
            if (!response.ok) throw new Error('Resposta do servidor inválida');
            return response.json();
        })
        .then(data => {
            console.log('Resposta do servidor:', data);
            if (data.success && data.user) {
                window.userData = data.user;
                populateClientForm();
            } else {
                alert('Por favor, faça login para agendar.');
                sessionStorage.removeItem('isLoggedIn');
                window.location.href = 'login.html';
            }
            renderCalendar();
        })
        .catch(error => {
            console.error('Erro ao verificar sessão:', error);
            alert('Erro ao verificar sessão. Tente novamente.');
            sessionStorage.removeItem('isLoggedIn');
            window.location.href = 'login.html';
        });

        const clientForm = document.getElementById("clientForm");
        if (clientForm) clientForm.addEventListener("input", updateClientFormValidation);
    }

    // Scroll suave para links de âncora
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            const targetId = this.getAttribute("href").substring(1);
            const targetElement = document.getElementById(targetId);
            const headerHeight = header ? header.offsetHeight : 0;

            const offsetAdjustments = {
                'home': 0,
                'about': -140,
                'servicos': -50,
                'pricing': -75,
                'gallery': -39,
                'team': -60
            };

            if (targetElement) {
                const extraOffset = offsetAdjustments[targetId] || 10;
                window.scrollTo({
                    top: targetElement.offsetTop - headerHeight - extraOffset,
                    behavior: "smooth"
                });
            } else {
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        });
    });

    // Filtro de preços
    const filterBtns = document.querySelectorAll("[data-filter-btn]");
    const filterItems = document.querySelectorAll("[data-filter]");
    let lastClickedFilterBtn = document.querySelector("[data-filter-btn='shaving']");

    const filter = function () {
        if (lastClickedFilterBtn) lastClickedFilterBtn.classList.remove("active");
        this.classList.add("active");
        lastClickedFilterBtn = this;

        filterItems.forEach(item => {
            if (this.dataset.filterBtn === item.dataset.filter || this.dataset.filterBtn === "all") {
                item.style.display = "block";
                item.classList.add("active");
            } else {
                item.style.display = "none";
                item.classList.remove("active");
            }
        });
    };

    if (lastClickedFilterBtn) filter.call(lastClickedFilterBtn);
    filterBtns.forEach(btn => btn.addEventListener("click", filter));

    // Efeito de fundo na seção Team
    const teamSection = document.querySelector(".team");
    const containers = document.querySelectorAll(".person .container");
    const colorMap = {
        "#e58f65": "#66463a",
        "#eda276": "#d17a4e",
        "#b38062": "#8f5a47"
    };

    if (teamSection && containers.length) {
        containers.forEach(container => {
            container.addEventListener("mouseenter", () => {
                const person = container.closest(".person");
                const originalColor = getComputedStyle(person).getPropertyValue("--color").trim();
                const darkColor = colorMap[originalColor] || originalColor;
                teamSection.style.background = darkColor;
            });
            container.addEventListener("mouseleave", () => {
                teamSection.style.background = "rgb(155, 107, 77)";
            });
        });
    }

    // Botão voltar ao topo
    const backTopBtn = document.querySelector("[data-back-top-btn]");
    if (backTopBtn) {
        let lastScrollY = window.scrollY;
        let hideTimeout;

        const showButton = () => {
            backTopBtn.classList.add("active");
            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(() => backTopBtn.classList.remove("active"), 2000);
        };

        const hideButton = () => {
            backTopBtn.classList.remove("active");
            clearTimeout(hideTimeout);
        };

        window.addEventListener("scroll", () => {
            const currentScrollY = window.scrollY;
            if (currentScrollY > 100) {
                if (currentScrollY > lastScrollY) showButton();
                else hideButton();
            } else hideButton();
            lastScrollY = currentScrollY;
        });

        backTopBtn.addEventListener("click", (e) => {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    }

    // Controle do modal da galeria
    const galleryModal = document.getElementById('galleryModal');
    const openGalleryModalBtn = document.getElementById('openGalleryModal');
    if (galleryModal && openGalleryModalBtn) {
        const closeGalleryModal = galleryModal.querySelector('.close-modal');

        function openGalleryModal() {
            galleryModal.classList.add('active');
            document.body.classList.add('modal-open');
        }

        openGalleryModalBtn.addEventListener('click', (e) => {
            e.preventDefault();
            openGalleryModal();
        });

        document.querySelectorAll('.gallery-card .card-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                openGalleryModal();
            });
        });

        closeGalleryModal.addEventListener('click', () => {
            galleryModal.classList.remove('active');
            document.body.classList.remove('modal-open');
        });

        galleryModal.addEventListener('click', (e) => {
            if (e.target === galleryModal) {
                galleryModal.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        });
    }

    // Animações de scroll
    const aboutContent = document.querySelector('.about-content');
    const teamMembers = document.querySelectorAll('.person');
    if (aboutContent || teamMembers.length) {
        function checkVisibility() {
            const windowHeight = window.innerHeight || document.documentElement.clientHeight;

            if (aboutContent) {
                const aboutRect = aboutContent.getBoundingClientRect();
                if (aboutRect.top <= windowHeight * 0.8 && aboutRect.bottom >= 0) {
                    aboutContent.classList.add('animate-in');
                }
            }

            if (teamSection) {
                const teamRect = teamSection.getBoundingClientRect();
                if (teamRect.top <= windowHeight * 0.8 && teamRect.bottom >= 0) {
                    teamMembers.forEach((member, index) => {
                        setTimeout(() => member.classList.add('animate-in'), index * 200);
                    });
                }
            }
        }

        window.addEventListener('scroll', checkVisibility);
        checkVisibility();
    }

    // Controle dos modais de serviços
    document.querySelectorAll('.read-more-btn').forEach(button => {
        button.addEventListener('click', () => {
            const modalId = button.getAttribute('data-modal');
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('active');
                document.body.classList.add('modal-open');
            }
        });
    });

    document.querySelectorAll('.close-modal').forEach(closeBtn => {
        closeBtn.addEventListener('click', () => {
            const modal = closeBtn.closest('.modal');
            modal.classList.remove('active');
            document.body.classList.remove('modal-open');
        });
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
                document.body.classList.remove('modal-open');
            }
        });
    });
});

// Função de redirecionamento para login
function redirectToLogin() {
    window.location.href = 'login.html';
}

// Configuração do Swiper (fora do DOMContentLoaded para garantir que seja global)
var swiper = new Swiper(".slider", {
    grabCursor: true,
    slidesPerView: 2,
    spaceBetween: 30,
    speed: 800,
    pagination: { el: ".swiper-pagination", clickable: true },
    autoplay: { delay: 3000, disableOnInteraction: false },
    effect: "slide",
    loop: true,
    navigation: { nextEl: ".swiper-button-next", prevEl: ".swiper-button-prev" }
});

// Funções adicionais
function redirectToBio(name) {
    window.location.href = `barber-bio.html?name=${name}`;
}

const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get('status');
const message = urlParams.get('message'); // Optional: Use the message parameter if provided

if (status) {
    if (status === 'success') {
        alert(message || 'Subscrição realizada com sucesso! Verifique seu email.');
    } else if (status === 'error') {
        alert(message || 'Houve um erro ao enviar o email. Tente novamente mais tarde.');
    } else if (status === 'invalid') {
        alert(message || 'Por favor, insira um email válido.');
    } else if (status === 'already_subscribed') {
        alert(message || 'Este email já está subscrito à nossa newsletter.');
    }
}
