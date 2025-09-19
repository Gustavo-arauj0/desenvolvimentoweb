// EcoSwap - Sistema de Trocas Sustentáveis
// Funcionalidades principais da aplicação

// Utilitários de segurança
const SecurityUtils = {
  sanitizeHTML: function(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
  },
  
  sanitizeImageURL: function(url) {
    if (!url) return '/images/placeholder.jpg';
    
    // Se for uma URL relativa (não começa com http/https), retornar como está
    if (url.startsWith('/') || url.startsWith('./') || url.startsWith('images/')) {
      return url;
    }
    
    try {
      const validUrl = new URL(url);
      // Permitir apenas HTTP/HTTPS
      if (validUrl.protocol === 'http:' || validUrl.protocol === 'https:') {
        return validUrl.href;
      }
    } catch (e) {
      return '/images/placeholder.jpg';
    }
    return '/images/placeholder.jpg';
  },

  sanitizeInput: function(input) {
    if (!input) return '';
    return input.trim().replace(/[<>]/g, '');
  }
};

class EcoSwapApp {
  constructor() {
    console.log("Construtor EcoSwapApp executado");
    this.currentUser = null;
    this.items = [];
    this.users = [];
    this.categories = [
      "Eletrônicos",
      "Roupas",
      "Casa e Jardim",
      "Livros",
      "Esportes",
      "Música",
      "Brinquedos",
      "Veículos",
      "Beleza",
      "Outros",
    ];
    this.init();
    console.log("EcoSwapApp inicialização completa");
  }

  init() {
    this.loadFromStorage();
    this.initEventListeners();
    this.checkAuthentication();
    this.populateInitialData();
  }

  // Gerenciamento de dados locais
  loadFromStorage() {
    this.users = JSON.parse(localStorage.getItem("ecoswap_users") || "[]");
    this.items = JSON.parse(localStorage.getItem("ecoswap_items") || "[]");
    this.currentUser = JSON.parse(
      localStorage.getItem("ecoswap_current_user") || "null"
    );
  }

  saveToStorage() {
    localStorage.setItem("ecoswap_users", JSON.stringify(this.users));
    localStorage.setItem("ecoswap_items", JSON.stringify(this.items));
    localStorage.setItem(
      "ecoswap_current_user",
      JSON.stringify(this.currentUser)
    );
  }

  // Limpar dados existentes
  clearAllData() {
    localStorage.removeItem("ecoswap_users");
    localStorage.removeItem("ecoswap_items");
    localStorage.removeItem("ecoswap_current_user");
    this.users = [];
    this.items = [];
    this.currentUser = null;
    this.populateInitialData();
  }

  // Dados iniciais para demonstração
  populateInitialData() {
    if (this.users.length === 0) {
      const adminUser = {
        id: "admin-1",
        name: "Administrador",
        email: "admin@ecoswap.com",
        password: "admin123",
        type: "admin",
        location: "Uberlândia, MG",
        phone: "(34) 99999-9999",
        createdAt: new Date().toISOString(),
      };

      const demoUsers = [
        {
          id: "user-1",
          name: "Maria Silva",
          email: "maria@email.com",
          password: "123456",
          type: "user",
          location: "Uberlândia, MG",
          phone: "(34) 98888-8888",
          createdAt: new Date().toISOString(),
        },
        {
          id: "user-2",
          name: "João Santos",
          email: "joao@email.com",
          password: "123456",
          type: "user",
          location: "Uberlândia, MG",
          phone: "(34) 97777-7777",
          createdAt: new Date().toISOString(),
        },
      ];

      this.users = [adminUser, ...demoUsers];
    }

    if (this.items.length === 0) {
      const demoItems = [
        {
          id: "item-1",
          title: "Smartphone Samsung Galaxy",
          description:
            "Celular em ótimo estado, pouco uso. Tela intacta, bateria excelente.",
          category: "Eletrônicos",
          condition: "Muito Bom",
          images: [
            "images/celular.jpg",
          ],
          userId: "user-1",
          status: "available",
          createdAt: new Date().toISOString(),
        },
        {
          id: "item-2",
          title: 'Livro "O Hobbit"',
          description:
            "Livro em bom estado, algumas páginas amareladas mas texto perfeitamente legível.",
          category: "Livros e Mídias",
          condition: "Bom",
          images: [
            "images/livro.jpg",
          ],
          userId: "user-2",
          status: "available",
          createdAt: new Date().toISOString(),
        },
        {
          id: "item-3",
          title: "Bicicleta Caloi Mountain Bike",
          description:
            "Bicicleta em excelente estado, pneus novos, freios ajustados.",
          category: "Esportes",
          condition: "Excelente",
          images: [
            "images/bicicleta.jpg",
          ],
          userId: "user-1",
          status: "available",
          createdAt: new Date().toISOString(),
        },
      ];

      this.items = demoItems;
    }

    this.saveToStorage();
  }

  // Autenticação
  login(email, password) {
    const user = this.users.find(
      (u) => u.email === email && u.password === password
    );
    if (user) {
      this.currentUser = user;
      this.saveToStorage();
      return { success: true, user: user };
    }
    return { success: false, message: "Email ou senha inválidos" };
  }

  register(userData) {
    // Verificar se email já existe
    if (this.users.find((u) => u.email === userData.email)) {
      return { success: false, message: "Este email já está cadastrado" };
    }

    const newUser = {
      id: "user-" + Date.now(),
      ...userData,
      type: "user",
      createdAt: new Date().toISOString(),
    };

    this.users.push(newUser);
    this.saveToStorage();
    return { success: true, user: newUser };
  }

  logout() {
    this.currentUser = null;
    localStorage.removeItem("ecoswap_current_user");
  }

  checkAuthentication() {
    const currentPath = window.location.pathname;
    const filename = currentPath.split("/").pop() || currentPath;

    // Definir tipos de página mais específicos
    const isAdminPage = filename.startsWith("admin-");
    const isUserPage = [
      "dashboard.html",
      "profile.html",
      "items.html",
    ].includes(filename);
    const isPrivatePage = isAdminPage || isUserPage;

    // Não fazer redirecionamentos automáticos se acabou de fazer login
    if (sessionStorage.getItem('justLoggedIn')) {
      console.log('Usuário acabou de fazer login, pulando verificação de autenticação do app.js');
      return;
    }

    // Redirecionar se não estiver autenticado
    if (isPrivatePage && !this.currentUser) {
      window.location.href = "login.html";
      return;
    }

    // Se estiver autenticado, verificar permissões
    if (this.currentUser) {
      // Admin tentando acessar página de usuário
      if (isUserPage && this.currentUser.type === "admin") {
        window.location.href = "admin-dashboard.html";
        return;
      }

      // Usuário comum tentando acessar página admin
      if (isAdminPage && this.currentUser.type !== "admin") {
        window.location.href = "dashboard.html";
        return;
      }
    }
  }

  // Gerenciamento de itens
  addItem(itemData) {
    const newItem = {
      id: "item-" + Date.now(),
      ...itemData,
      userId: this.currentUser.id,
      status: "available",
      createdAt: new Date().toISOString(),
    };

    this.items.push(newItem);
    this.saveToStorage();
    return { success: true, item: newItem };
  }

  updateItem(itemId, itemData) {
    const index = this.items.findIndex((item) => item.id === itemId);
    if (index !== -1) {
      this.items[index] = { ...this.items[index], ...itemData };
      this.saveToStorage();
      return { success: true };
    }
    return { success: false, message: "Item não encontrado" };
  }

  deleteItem(itemId) {
    const index = this.items.findIndex((item) => item.id === itemId);
    if (index !== -1) {
      this.items.splice(index, 1);
      this.saveToStorage();
      return { success: true };
    }
    return { success: false, message: "Item não encontrado" };
  }

  getItemById(itemId) {
    return this.items.find((item) => item.id === itemId);
  }

  getUserItems(userId = null) {
    const targetUserId = userId || this.currentUser?.id;
    return this.items.filter((item) => item.userId === targetUserId);
  }

  getAvailableItems() {
    return this.items.filter((item) => item.status === "available");
  }

  // Busca
  searchItems(query) {
    const searchTerm = query.toLowerCase();
    return this.items.filter(
      (item) =>
        item.status === "available" &&
        (item.title.toLowerCase().includes(searchTerm) ||
          item.description.toLowerCase().includes(searchTerm) ||
          item.category.toLowerCase().includes(searchTerm))
    );
  }

  // Gerenciamento de usuários (Admin)
  getAllUsers() {
    return this.users.filter((user) => user.type !== "admin");
  }

  deleteUser(userId) {
    // Remover usuário
    const userIndex = this.users.findIndex((user) => user.id === userId);
    if (userIndex !== -1) {
      this.users.splice(userIndex, 1);
    }

    // Remover itens do usuário
    this.items = this.items.filter((item) => item.userId !== userId);

    this.saveToStorage();
    return { success: true };
  }

  updateUserProfile(userData) {
    if (this.currentUser) {
      const userIndex = this.users.findIndex(
        (user) => user.id === this.currentUser.id
      );
      if (userIndex !== -1) {
        this.users[userIndex] = { ...this.users[userIndex], ...userData };
        this.currentUser = this.users[userIndex];
        this.saveToStorage();
        return { success: true };
      }
    }
    return { success: false, message: "Erro ao atualizar perfil" };
  }

  deleteAccount() {
    if (this.currentUser) {
      this.deleteUser(this.currentUser.id);
      this.logout();
      return { success: true };
    }
    return { success: false };
  }

  // Utilitários
  formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR");
  }

  getUserById(userId) {
    return this.users.find((user) => user.id === userId);
  }

  // Event listeners globais
  initEventListeners() {
    // Event listener para logout
    $(document).on("click", ".logout-btn", (e) => {
      e.preventDefault();
      if (confirm("Deseja realmente sair do sistema?")) {
        this.logout();
        window.location.href = "index.html";
      }
    });

    // Event listener para busca
    $(document).on("input", "#searchInput", (e) => {
      const query = e.target.value.trim();
      if (query.length >= 2) {
        this.performSearch(query);
      } else {
        $("#searchResults").empty().hide();
      }
    });

    // Fechar resultados de busca ao clicar fora
    $(document).on("click", (e) => {
      if (!$(e.target).closest(".search-form").length) {
        $("#searchResults").hide();
      }
    });
  }

  performSearch(query) {
    const results = this.searchItems(query);
    const $resultsContainer = $("#searchResults");

    if (results.length > 0) {
      let html = '<div class="search-results">';
      results.forEach((item) => {
        const user = this.getUserById(item.userId);
        const sanitizedTitle = SecurityUtils.sanitizeHTML(item.title);
        const sanitizedCategory = SecurityUtils.sanitizeHTML(item.category);
        const sanitizedLocation = SecurityUtils.sanitizeHTML(user ? user.location : "");
        const sanitizedImageUrl = SecurityUtils.sanitizeImageURL(item.images[0]);
        
        html += `
                    <div class="search-result-item" data-item-id="${SecurityUtils.sanitizeHTML(item.id)}">
                        <div class="d-flex align-items-center">
                            <img src="${sanitizedImageUrl}" alt="${sanitizedTitle}" class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <div>
                                <div class="fw-bold">${sanitizedTitle}</div>
                                <small class="text-muted">${sanitizedCategory} - ${sanitizedLocation}</small>
                            </div>
                        </div>
                    </div>
                `;
      });
      html += "</div>";
      $resultsContainer.html(html).show();
    } else {
      $resultsContainer
        .html(
          '<div class="search-results"><div class="search-result-item text-muted">Nenhum item encontrado</div></div>'
        )
        .show();
    }
  }
}

// Inicializar aplicação quando o documento estiver pronto
$(document).ready(() => {
  console.log("App.js iniciando...");
  
  // Não inicializar o EcoSwapApp em páginas que têm seu próprio sistema de autenticação
  const currentPath = window.location.pathname;
  const filename = currentPath.split("/").pop() || currentPath;
  const hasOwnAuthSystem = [
    'dashboard.html', 
    'login.html', 
    'cadastro.html',
    'profile.html',
    'items.html',
    'admin-dashboard.html',
    'admin-profile.html',
    'admin-users.html'
  ].includes(filename);
  
  if (!hasOwnAuthSystem) {
    window.ecoSwap = new EcoSwapApp();
    console.log("EcoSwap inicializado:", window.ecoSwap);
  } else {
    console.log("Página tem sistema de autenticação próprio, não inicializando EcoSwapApp");
  }
});

// Utilitários globais para validação de formulários
const FormValidator = {
  validateEmail: (email) => {
    // Regex mais rigorosa para email
    const regex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/;
    return regex.test(email) && email.length <= 254;
  },

  validatePassword: (password) => {
    // Mantendo 6 caracteres para compatibilidade com dados de teste
    return password.length >= 6;
  },

  validateRequired: (value) => {
    return value && value.trim().length > 0;
  },

  validatePhone: (phone) => {
    const regex = /^\(\d{2}\)\s\d{4,5}-\d{4}$/;
    return regex.test(phone);
  },

  validateName: (name) => {
    // Apenas letras, espaços e acentos
    const regex = /^[a-zA-ZÀ-ÿ\s]{2,50}$/;
    return regex.test(name.trim());
  },

  showError: (field, message) => {
    const $field = $(field);
    $field.removeClass("is-valid").addClass("is-invalid");
    $field.siblings(".invalid-feedback").remove();
    $field.after(`<div class="invalid-feedback">${message}</div>`);
  },

  showSuccess: (field) => {
    const $field = $(field);
    $field.removeClass("is-invalid").addClass("is-valid");
    $field.siblings(".invalid-feedback").remove();
  },

  clearValidation: (form) => {
    $(form).find(".is-valid, .is-invalid").removeClass("is-valid is-invalid");
    $(form).find(".invalid-feedback").remove();
  },
};
