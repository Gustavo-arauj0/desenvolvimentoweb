# Guia de Instalação e Configuração - EcoSwap Backend

## Pré-requisitos

- **PHP 7.4 ou superior** com extensões:
  - PDO
  - PDO_MySQL  
  - mbstring
  - json
- **MySQL 5.7 ou superior**
- **Servidor Web** (Apache ou Nginx)

## Estrutura de Diretórios

Organize os arquivos da seguinte forma:

```
/htdocs/ecoswap/
├── index.html                    # Página inicial (frontend)
├── login.html                    # Página de login
├── cadastro.html                 # Página de cadastro
├── dashboard.html                # Dashboard do usuário
├── profile.html                  # Perfil do usuário
├── items.html                    # Gerenciamento de itens
├── admin-dashboard.html          # Dashboard administrativo
├── admin-users.html              # Gerenciamento de usuários
├── admin-profile.html            # Perfil do admin
├── css/                          # Estilos CSS
├── js/                           # Scripts JavaScript
├── images/                       # Imagens do sistema
└── backend/                      # Arquivos PHP do backend
    ├── config/
    │   └── database.php
    ├── classes/
    │   ├── Database.php
    │   ├── User.php
    │   ├── Item.php
    │   └── Auth.php
    ├── repositories/
    │   ├── UserRepository.php
    │   └── ItemRepository.php
    ├── controllers/
    │   ├── AuthController.php
    │   ├── UserController.php
    │   ├── ItemController.php
    │   └── AdminController.php
    ├── includes/
    │   ├── functions.php
    │   └── session.php
    ├── sql/
    │   └── bd_ecoswap.sql
    ├── process_login.php
    ├── process_register.php
    ├── logout.php
    ├── get_data.php
    ├── save_data.php
    └── api.php
```

## Passo 1: Configuração do Banco de Dados

1. **Criar o banco de dados:**
   ```sql
   CREATE DATABASE ecoswap CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
   ```

2. **Executar o script SQL:**
   - Importe o arquivo `backend/sql/bd_ecoswap.sql` no MySQL
   - Ou execute o conteúdo do arquivo no seu cliente MySQL

3. **Configurar conexão:**
   - Edite `backend/config/database.php`
   - Ajuste as credenciais do banco:
   ```php
   private const HOST = 'localhost';           // Seu host MySQL
   private const DB_NAME = 'ecoswap';          // Nome do banco
   private const USERNAME = 'root';            // Usuário MySQL
   private const PASSWORD = '';                // Senha MySQL
   ```

## Passo 2: Configuração dos Arquivos PHP

1. **Copiar arquivos backend:**
   - Coloque todos os arquivos PHP na estrutura de diretórios mostrada acima

2. **Ajustar permissões:**
   ```bash
   chmod 755 backend/
   chmod 644 backend/*.php
   chmod 644 backend/*/*.php
   ```

3. **Configurar PHP:**
   - Certifique-se que as extensões PDO e PDO_MySQL estão habilitadas
   - Ajuste `php.ini` se necessário:
   ```ini
   extension=pdo
   extension=pdo_mysql
   session.cookie_httponly = 1
   session.use_only_cookies = 1
   ```

## Passo 3: Integração Frontend-Backend

### Para Login (login.html):

Modifique o JavaScript do formulário de login:

```javascript
// No arquivo js/auth.js ou diretamente no login.html

document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('backend/process_login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.user.tipo_usuario === 'admin') {
                window.location.href = 'admin-dashboard.html';
            } else {
                window.location.href = 'dashboard.html';
            }
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro de conexão');
    });
});
```

### Para Cadastro (cadastro.html):

```javascript
// No arquivo js/auth.js ou diretamente no cadastro.html

document.getElementById('cadastroForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('backend/process_register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', 'Cadastro realizado com sucesso!');
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro de conexão');
    });
});
```

### Para buscar dados (exemplo - categorias):

```javascript
// Função para carregar categorias
function loadCategories() {
    fetch('backend/get_data.php?action=categories')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const select = document.getElementById('category');
            select.innerHTML = '<option value="">Selecione...</option>';
            
            data.data.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.nome;
                select.appendChild(option);
            });
        }
    })
    .catch(error => console.error('Erro ao carregar categorias:', error));
}
```

### Para salvar dados (exemplo - item):

```javascript
// Função para salvar item
function saveItem(itemData) {
    const formData = new FormData();
    formData.append('action', 'save_item');
    formData.append('title', itemData.title);
    formData.append('description', itemData.description);
    formData.append('category', itemData.category);
    formData.append('condition', itemData.condition);
    formData.append('imageUrl', itemData.imageUrl);
    
    if (itemData.id) {
        formData.append('itemId', itemData.id);
    }
    
    fetch('backend/save_data.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('success', data.message);
            loadUserItems(); // Recarregar lista
        } else {
            showAlert('danger', data.message);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro de conexão');
    });
}
```

## Passo 4: Configuração de Sessões

Adicione verificação de autenticação nas páginas que precisam de login:

```javascript
// Adicionar no início das páginas privadas (dashboard.html, profile.html, etc.)

function checkAuth() {
    fetch('backend/get_data.php?action=check_auth')
    .then(response => response.json())
    .then(data => {
        if (!data.data.logged_in) {
            window.location.href = 'login.html';
            return;
        }
        
        // Atualizar informações do usuário na interface
        if (data.data.user) {
            document.getElementById('userNameNav').textContent = data.data.user.name;
            
            // Se for admin e estiver em página admin
            if (data.data.is_admin && window.location.pathname.includes('admin')) {
                // Usuário autorizado para área admin
            } else if (!data.data.is_admin && window.location.pathname.includes('admin')) {
                // Redirecionar não-admin que tenta acessar área admin
                window.location.href = 'dashboard.html';
            }
        }
    })
    .catch(error => {
        console.error('Erro ao verificar autenticação:', error);
        window.location.href = 'login.html';
    });
}

// Executar verificação ao carregar a página
document.addEventListener('DOMContentLoaded', checkAuth);
```

## Passo 5: Configuração do Logout

```javascript
// Função de logout
function logout() {
    fetch('backend/logout.php', { method: 'POST' })
    .then(response => response.json())
    .then(data => {
        window.location.href = 'index.html';
    })
    .catch(error => {
        console.error('Erro no logout:', error);
        window.location.href = 'index.html';
    });
}

// Adicionar event listeners para botões de logout
document.addEventListener('DOMContentLoaded', function() {
    const logoutButtons = document.querySelectorAll('.logout-btn, #logoutLink');
    logoutButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            logout();
        });
    });
});
```

## Passo 6: Teste e Verificação

1. **Testar conexão com banco:**
   - Acesse `backend/get_data.php?action=categories`
   - Deve retornar JSON com as categorias

2. **Testar login:**
   - Use: admin@ecoswap.com / admin123 (admin)
   - Use: maria@email.com / 123456 (usuário)

3. **Verificar logs:**
   - Conferir logs de erro do PHP
   - Verificar console do navegador

## Possíveis Problemas e Soluções

### Erro de Conexão com Banco:
- Verificar credenciais em `config/database.php`
- Confirmar se MySQL está executando
- Verificar se banco `ecoswap` existe

### Erro 500 (Internal Server Error):
- Verificar logs de erro do PHP
- Confirmar se todas as classes estão sendo carregadas
- Verificar sintaxe PHP

### Sessões não funcionam:
- Verificar se sessões estão habilitadas no PHP
- Confirmar permissões de escrita no diretório de sessões
- Verificar configurações de cookies

### CORS (Cross-Origin):
Se acessando de domínio diferente, adicionar headers:
```php
// No início dos arquivos PHP que respondem AJAX
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');
```

## Contas de Teste Pré-configuradas

**Administrador:**
- Email: admin@ecoswap.com
- Senha: admin123

**Usuários:**
- Email: maria@email.com | Senha: 123456
- Email: joao@email.com | Senha: 123456

## URLs Importantes

- **Página inicial:** /ecoswap/index.html
- **Login:** /ecoswap/login.html  
- **Dashboard usuário:** /ecoswap/dashboard.html
- **Dashboard admin:** /ecoswap/admin-dashboard.html
- **API base:** /ecoswap/backend/

Com essa implementação, o sistema EcoSwap estará completamente funcional com backend PHP integrado ao frontend existente, atendendo todos os requisitos especificados no projeto.