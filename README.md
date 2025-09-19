# EcoSwap - Sistema de Trocas Sustentáveis

Sistema desenvolvido para a disciplina de Desenvolvimento Web I - UFU

## Equipe
- Gabriel Freitas Santos
- Gustavo de Araújo Santos  
- Gustavo Oliveira Tundisi

## Sobre o Sistema

O EcoSwap é uma plataforma web que facilita a troca de itens entre usuários, promovendo o consumo consciente e a economia circular. O sistema está alinhado com o ODS 12 - Consumo e Produção Responsáveis.

## Funcionalidades Implementadas

### Área Pública
- ✅ Página inicial com informações do sistema
- ✅ Catálogo público de itens disponíveis
- ✅ Sistema de busca de itens
- ✅ Páginas de login e cadastro
- ✅ Design responsivo com Bootstrap

### Área Privada
- ✅ Dashboard do usuário com estatísticas
- ✅ Gerenciamento de perfil (edição e exclusão de conta)
- ✅ CRUD completo de itens
- ✅ Listagem e filtros de itens
- ✅ Área administrativa para gerenciar usuários

### Recursos Técnicos
- ✅ Validação completa de formulários
- ✅ Autenticação com localStorage  
- ✅ Design responsivo
- ✅ Acessibilidade (ARIA, contraste, navegação por teclado)
- ✅ Funcionalidade de busca (pontuação extra)

## Como Testar

### Contas Pré-configuradas

**Administrador:**
- Email: `admin@ecoswap.com`
- Senha: `admin123`

**Usuários de Teste:**
- Email: `maria@email.com` / Senha: `123456`
- Email: `joao@email.com` / Senha: `123456`

### Navegação

1. **Área Pública**: Acesse `index.html` para ver o catálogo público
2. **Login**: Use as contas de teste na página `login.html`
3. **Cadastro**: Registre novos usuários em `cadastro.html`
4. **Dashboard**: Área privada com estatísticas do usuário
5. **Admin**: Acesse com conta admin para gerenciar usuários

## Estrutura de Arquivos

```
/
├── index.html              # Página inicial
├── login.html              # Login
├── cadastro.html           # Cadastro de usuários
├── dashboard.html          # Dashboard do usuário
├── profile.html            # Perfil do usuário
├── items.html              # Gerenciamento de itens
├── admin-dashboard.html    # Dashboard administrativo
├── admin-users.html        # Gerenciamento de usuários (admin)
├── css/
│   └── style.css          # Estilos customizados
└── js/
    ├── app.js             # Lógica principal da aplicação
    ├── auth.js            # Autenticação
    ├── dashboard.js       # Dashboard
    ├── profile.js         # Perfil
    ├── items.js           # Gerenciamento de itens
    ├── admin.js           # Funcionalidades admin
    └── public.js          # Páginas públicas
```

## Tecnologias Utilizadas

- **HTML5**: Estrutura semântica
- **CSS3**: Estilos customizados
- **Bootstrap 5**: Framework CSS responsivo
- **JavaScript**: Lógica da aplicação
- **jQuery**: Manipulação do DOM
- **LocalStorage**: Persistência de dados no navegador

## Funcionalidades de Destaque

### Sustentabilidade
- Dicas sustentáveis no dashboard
- Informações sobre ODS 12
- Contador de impacto ambiental

### Usabilidade
- Interface intuitiva e responsiva
- Validação em tempo real
- Mensagens de feedback claras
- Navegação por breadcrumbs

### Segurança
- Validação de dados no frontend
- Sanitização de entradas
- Controle de acesso por perfil

## Dados de Demonstração

O sistema vem com dados fictícios pré-carregados para demonstração, incluindo:
- 3 usuários de exemplo (incluindo admin)
- 3 itens de diferentes categorias
- Múltiplas categorias de produtos

## Observações Técnicas

- Todos os dados são armazenados localmente no navegador
- O sistema é totalmente funcional no frontend
- Validação robusta em todos os formulários  
- Design responsivo testado em diferentes dispositivos
- Acessibilidade implementada conforme WCAG