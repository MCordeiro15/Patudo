# PATUDO - ESTRUTURA DO PROJETO

---

## BACK-END (PHP/Laravel)

### BACK-END - OWNER

#### Controllers - API

- [app/Http/Controllers/API/AnimalController.php](app/Http/Controllers/API/AnimalController.php) — CRUD de animais de estimação (criar, listar, ver, atualizar, eliminar)
- [app/Http/Controllers/API/AnimalBookingController.php](app/Http/Controllers/API/AnimalBookingController.php) — Controller vazio para relacionamentos animal-reserva (futuro desenvolvimento)
- [app/Http/Controllers/API/BookingController.php](app/Http/Controllers/API/BookingController.php) — Gestão de reservas: criar, listar, ver e alterar estado (pending → approved/rejected/completed/cancelled)
- [app/Http/Controllers/API/PostController.php](app/Http/Controllers/API/PostController.php) — CRUD de posts de petsitters com upload de fotos (até 5 imagens)
- [app/Http/Controllers/API/UserController.php](app/Http/Controllers/API/UserController.php) — CRUD de utilizadores com funcionalidade de suspensão de conta
- [app/Http/Controllers/API/SupportTicketsController.php](app/Http/Controllers/API/SupportTicketsController.php) — Gestão de tickets de suporte: criar reclamações, claim por admin, fechar tickets

#### Controllers - Settings

- [app/Http/Controllers/Settings/ProfileController.php](app/Http/Controllers/Settings/ProfileController.php) — Página de edição do perfil do utilizador (nome, email) e eliminação de conta
- [app/Http/Controllers/Settings/SecurityController.php](app/Http/Controllers/Settings/SecurityController.php) — Página de segurança: alterar password, 2FA e passkeys

#### Models

- [app/Models/Animal.php](app/Models/Animal.php) — Modelo de animal de estimação com dados como espécie, raça, tamanho, necessidades especiais e peso
- [app/Models/AnimalBooking.php](app/Models/AnimalBooking.php) — Tabela intermédia entre animais e reservas (many-to-many)
- [app/Models/Booking.php](app/Models/Booking.php) — Modelo de reserva com datas, estado, preço total e relações com user, petsitter, animais e review
- [app/Models/Post.php](app/Models/Post.php) — Modelo de post de petsitter com título, descrição e array de fotos
- [app/Models/SupportTicket.php](app/Models/SupportTicket.php) — Modelo de ticket de suporte com tipo (complaint/help), estado e relações com criador e reportado
- [app/Models/User.php](app/Models/User.php) — Modelo principal de utilizador com autenticação, 2FA, passkeys, roles (admin/petsitter) e relações

#### API Resources

- [app/Http/Resources/AnimalResource.php](app/Http/Resources/AnimalResource.php) — Transforma o modelo Animal em JSON para a API
- [app/Http/Resources/AnimalBookingResource.php](app/Http/Resources/AnimalBookingResource.php) — Transforma o modelo AnimalBooking em JSON para a API
- [app/Http/Resources/BookingResource.php](app/Http/Resources/BookingResource.php) — Transforma o modelo Booking em JSON para a API
- [app/Http/Resources/PostResource.php](app/Http/Resources/PostResource.php) — Transforma o modelo Post em JSON para a API
- [app/Http/Resources/UserResource.php](app/Http/Resources/UserResource.php) — Transforma o modelo User em JSON para a API
- [app/Http/Resources/SupportTicketsResource.php](app/Http/Resources/SupportTicketsResource.php) — Transforma o modelo SupportTicket em JSON para a API

#### Policies

- [app/Policies/AnimalPolicy.php](app/Policies/AnimalPolicy.php) — Permissões para ações em animais (view, create, update, delete)
- [app/Policies/BookingPolicy.php](app/Policies/BookingPolicy.php) — Permissões para ações em reservas, incluindo updateStatus
- [app/Policies/PostPolicy.php](app/Policies/PostPolicy.php) — Permissões para ações em posts
- [app/Policies/UserPolicy.php](app/Policies/UserPolicy.php) — Permissões para ações em utilizadores, incluindo suspend

#### Requests

- [app/Http/Requests/Settings/ProfileUpdateRequest.php](app/Http/Requests/Settings/ProfileUpdateRequest.php) — Validação para atualização do perfil (nome, email)
- [app/Http/Requests/Settings/ProfileDeleteRequest.php](app/Http/Requests/Settings/ProfileDeleteRequest.php) — Validação para eliminação da conta (confirmação de password)
- [app/Http/Requests/Settings/PasswordUpdateRequest.php](app/Http/Requests/Settings/PasswordUpdateRequest.php) — Validação para alteração de password
- [app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php](app/Http/Requests/Settings/TwoFactorAuthenticationRequest.php) — Validação para ativar/desativar 2FA

#### Migrations - Owner

- [database/migrations/2026_10_22_223620_create_animals_table.php](database/migrations/2026_10_22_223620_create_animals_table.php) — Cria a tabela animals com campos básicos (nome, espécie, raça, tamanho)
- [database/migrations/2026_11_12_172855_add_new_columns_to_animals_table.php](database/migrations/2026_11_12_172855_add_new_columns_to_animals_table.php) — Adiciona colunas extras à tabela animals (sexo, foto, peso)
- [database/migrations/2026_10_23_093300_create_bookings_table.php](database/migrations/2026_10_23_093300_create_bookings_table.php) — Cria a tabela bookings com datas, estado e preço
- [database/migrations/2026_10_23_152031_create_animal_bookings_table.php](database/migrations/2026_10_23_152031_create_animal_bookings_table.php) — Cria a tabela intermédia animal_bookings (many-to-many)
- [database/migrations/2026_10_23_100211_create_posts_table.php](database/migrations/2026_10_23_100211_create_posts_table.php) — Cria a tabela posts com descrição e fotos
- [database/migrations/2026_11_12_130603_add_title_to_posts_table.php](database/migrations/2026_11_12_130603_add_title_to_posts_table.php) — Adiciona a coluna title à tabela posts
- [database/migrations/2026_10_22_225825_create_support_tickets_table.php](database/migrations/2026_10_22_225825_create_support_tickets_table.php) — Cria a tabela support_tickets com tipo, estado e relações

#### Factories

- [database/factories/AnimalFactory.php](database/factories/AnimalFactory.php) — Gera dados fictícios para animais nos testes
- [database/factories/UserFactory.php](database/factories/UserFactory.php) — Gera dados fictícios para utilizadores nos testes

#### Tests - Owner

- [tests/Feature/AnimalTest.php](tests/Feature/AnimalTest.php) — Testes da API de animais (CRUD, permissões)
- [tests/Feature/UserTest.php](tests/Feature/UserTest.php) — Testes da API de utilizadores (CRUD, suspensão)
- [tests/Feature/SupportTicketTest.php](tests/Feature/SupportTicketTest.php) — Testes da API de tickets de suporte

---

### BACK-END - PETSITTER

#### Controllers - API

- [app/Http/Controllers/API/PetsitterController.php](app/Http/Controllers/API/PetsitterController.php) — CRUD de perfis de petsitter, aprovação/rejeição, atualização de dias de disponibilidade
- [app/Http/Controllers/API/PetsitterAvailabilityController.php](app/Http/Controllers/API/PetsitterAvailabilityController.php) — Gestão do calendário de disponibilidade: listar, ativar/desativar datas específicas
- [app/Http/Controllers/API/ReviewController.php](app/Http/Controllers/API/ReviewController.php) — Controller vazio para avaliações de petsitters (futuro desenvolvimento)
- [app/Http/Controllers/API/FavouriteController.php](app/Http/Controllers/API/FavouriteController.php) — Adicionar/remover petsitters dos favoritos do utilizador

#### Controllers - Web

- [app/Http/Controllers/PetsitterProfileController.php](app/Http/Controllers/PetsitterProfileController.php) — Renderiza a página de perfil do petsitter via Inertia (próprio ou de outro)

#### Models

- [app/Models/Petsitter.php](app/Models/Petsitter.php) — Perfil do petsitter com biografia, experiência, preço, tamanhos aceites, estado de aprovação
- [app/Models/PetsitterAvailability.php](app/Models/PetsitterAvailability.php) — Datas de disponibilidade individual de cada petsitter
- [app/Models/Review.php](app/Models/Review.php) — Avaliações (estrelas e comentário) de owners sobre petsitters
- [app/Models/Favourite.php](app/Models/Favourite.php) — Tabela intermédia de favoritos entre utilizadores e petsitters

#### API Resources

- [app/Http/Resources/PetsitterResource.php](app/Http/Resources/PetsitterResource.php) — Transforma o modelo Petsitter em JSON para a API
- [app/Http/Resources/PetsitterAvailabilityResource.php](app/Http/Resources/PetsitterAvailabilityResource.php) — Transforma o modelo PetsitterAvailability em JSON para a API
- [app/Http/Resources/ReviewResource.php](app/Http/Resources/ReviewResource.php) — Transforma o modelo Review em JSON para a API
- [app/Http/Resources/FavouriteResource.php](app/Http/Resources/FavouriteResource.php) — Transforma o modelo Favourite em JSON para a API

#### Policies

- [app/Policies/PetsitterPolicy.php](app/Policies/PetsitterPolicy.php) — Permissões para ações em petsitters (view, create, update, delete, approve)

#### Migrations - Petsitter

- [database/migrations/2026_10_23_093219_create_petsitters_table.php](database/migrations/2026_10_23_093219_create_petsitters_table.php) — Cria a tabela petsitters com biografia, experiência, preço, localização
- [database/migrations/2026_11_11_182448_add_availability_days_to_petsitters_table.php](database/migrations/2026_11_11_182448_add_availability_days_to_petsitters_table.php) — Adiciona coluna availability_days (JSON) à tabela petsitters
- [database/migrations/2026_11_12_111533_add_new_columns_to_petsitters_table.php](database/migrations/2026_11_12_111533_add_new_columns_to_petsitters_table.php) — Adiciona colunas extras (sexo, documentos de identificação)
- [database/migrations/2026_11_12_111830_add_is_petsitter_to_users_table.php](database/migrations/2026_11_12_111830_add_is_petsitter_to_users_table.php) — Adiciona coluna is_petsitter à tabela users
- [database/migrations/2026_10_23_125745_create_petsitter_availabilities_table.php](database/migrations/2026_10_23_125745_create_petsitter_availabilities_table.php) — Cria a tabela petsitter_availabilities com datas individuais
- [database/migrations/2026_10_23_153435_create_reviews_table.php](database/migrations/2026_10_23_153435_create_reviews_table.php) — Cria a tabela reviews com estrelas e comentário
- [database/migrations/2026_10_23_152443_create_favourites_table.php](database/migrations/2026_10_23_152443_create_favourites_table.php) — Cria a tabela favourites (many-to-many users-petsitters)

#### Factories

- [database/factories/PetsitterFactory.php](database/factories/PetsitterFactory.php) — Gera dados fictícios para petsitters nos testes
- [database/factories/PetsitterAvailabilityFactory.php](database/factories/PetsitterAvailabilityFactory.php) — Gera dados fictícios de disponibilidade nos testes

#### Seeders

- [database/seeders/PetsitterSeeder.php](database/seeders/PetsitterSeeder.php) — Preenche a base de dados com petsitters de exemplo

#### Tests - Petsitter

- [tests/Feature/PetsitterTest.php](tests/Feature/PetsitterTest.php) — Testes da API de petsitters (CRUD, aprovação)
- [tests/Feature/PetsitterAvailabilityDaysTest.php](tests/Feature/PetsitterAvailabilityDaysTest.php) — Testes dos dias de disponibilidade da semana
- [tests/Feature/PetsitterCalendarTest.php](tests/Feature/PetsitterCalendarTest.php) — Testes do calendário de disponibilidade por datas
- [tests/Feature/PetsitterProfilePageTest.php](tests/Feature/PetsitterProfilePageTest.php) — Testes da página de perfil do petsitter
- [tests/Feature/FavouriteTest.php](tests/Feature/FavouriteTest.php) — Testes da funcionalidade de favoritos

---

### BACK-END - COMUM / GERAL

#### Controllers

- [app/Http/Controllers/Controller.php](app/Http/Controllers/Controller.php) — Controller base de todos os controllers da aplicação
- [app/Http/Controllers/API/AuthController.php](app/Http/Controllers/API/AuthController.php) — Autenticação via API: login, logout, obter utilizador autenticado

#### Middleware

- [app/Http/Middleware/HandleAppearance.php](app/Http/Middleware/HandleAppearance.php) — Controla o tema de aparência (claro/escuro) do utilizador
- [app/Http/Middleware/HandleInertiaRequests.php](app/Http/Middleware/HandleInertiaRequests.php) — Configura os dados partilhados enviados para o front-end via Inertia

#### Actions

- [app/Actions/Fortify/CreateNewUser.php](app/Actions/Fortify/CreateNewUser.php) — Lógica personalizada para criação de novo utilizador no registo
- [app/Actions/Fortify/ResetUserPassword.php](app/Actions/Fortify/ResetUserPassword.php) — Lógica personalizada para redefinição de password

#### Concerns

- [app/Concerns/PasswordValidationRules.php](app/Concerns/PasswordValidationRules.php) — Regras reutilizáveis de validação de password
- [app/Concerns/ProfileValidationRules.php](app/Concerns/ProfileValidationRules.php) — Regras reutilizáveis de validação de perfil

#### Providers

- [app/Providers/AppServiceProvider.php](app/Providers/AppServiceProvider.php) — Configuração geral do serviço da aplicação
- [app/Providers/FortifyServiceProvider.php](app/Providers/FortifyServiceProvider.php) — Configuração do Laravel Fortify (autenticação, 2FA, passkeys)

#### Migrations - Comum

- [database/migrations/0001_01_01_000000_create_users_table.php](database/migrations/0001_01_01_000000_create_users_table.php) — Cria a tabela users com campos básicos de autenticação
- [database/migrations/0001_01_01_000001_create_cache_table.php](database/migrations/0001_01_01_000001_create_cache_table.php) — Cria a tabela cache para armazenamento temporário
- [database/migrations/0001_01_01_000002_create_jobs_table.php](database/migrations/0001_01_01_000002_create_jobs_table.php) — Cria a tabela jobs para filas de tarefas em background
- [database/migrations/2024_01_01_000000_create_passkeys_table.php](database/migrations/2024_01_01_000000_create_passkeys_table.php) — Cria a tabela passkeys para autenticação biométrica
- [database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php](database/migrations/2025_08_14_170933_add_two_factor_columns_to_users_table.php) — Adiciona colunas de 2FA à tabela users
- [database/migrations/2026_11_02_123912_create_personal_access_tokens_table.php](database/migrations/2026_11_02_123912_create_personal_access_tokens_table.php) — Cria a tabela de tokens API do Laravel Sanctum

#### Seeders

- [database/seeders/DatabaseSeeder.php](database/DatabaseSeeder.php) — Ponto de entrada principal para seeders, orquestra a população da base de dados

#### Routes

- [routes/api.php](routes/api.php) — Rotas da API REST (animais, reservas, petsitters, favoritos, etc.)
- [routes/web.php](routes/web.php) — Rotas web principais (homepage, perfil, autenticação)
- [routes/settings.php](routes/settings.php) — Rotas das páginas de definições (perfil, segurança, aparência)
- [routes/console.php](routes/console.php) — Rotas para comandos de console (tasks agendadas)

#### Config

- [config/app.php](config/app.php) — Configuração geral da aplicação (nome, timezone, fuso horário)
- [config/auth.php](config/auth.php) — Configuração de autenticação (guardas, providers)
- [config/cache.php](config/cache.php) — Configuração do sistema de cache
- [config/database.php](config/database.php) — Configuração das conexões com a base de dados
- [config/filesystems.php](config/filesystems.php) — Configuração de armazenamento de ficheiros (local, S3, etc.)
- [config/fortify.php](config/fortify.php) — Configuração do Laravel Fortify (features de autenticação)
- [config/inertia.php](config/inertia.php) — Configuração do Inertia.js (bridge entre Laravel e Vue)
- [config/logging.php](config/logging.php) — Configuração dos canais de log
- [config/mail.php](config/mail.php) — Configuração de envio de emails
- [config/queue.php](config/queue.php) — Configuração das filas de tarefas
- [config/sanctum.php](config/sanctum.php) — Configuração da autenticação API (tokens)
- [config/services.php](config/services.php) — Credenciais de serviços externos
- [config/session.php](config/session.php) — Configuração das sessões do utilizador

#### Tests - Comum

- [tests/Feature/AuthTest.php](tests/Feature/AuthTest.php) — Testes gerais de autenticação
- [tests/Feature/DashboardTest.php](tests/Feature/DashboardTest.php) — Testes da página do dashboard
- [tests/Feature/ExampleTest.php](tests/Feature/ExampleTest.php) — Teste de exemplo básico
- [tests/Feature/Settings/ProfileUpdateTest.php](tests/Feature/Settings/ProfileUpdateTest.php) — Testes de atualização do perfil
- [tests/Feature/Settings/SecurityTest.php](tests/Feature/Settings/SecurityTest.php) — Testes das definições de segurança
- [tests/Feature/Auth/AuthenticationTest.php](tests/Feature/Auth/AuthenticationTest.php) — Testes de login e logout
- [tests/Feature/Auth/EmailVerificationTest.php](tests/Feature/Auth/EmailVerificationTest.php) — Testes de verificação de email
- [tests/Feature/Auth/PasswordConfirmationTest.php](tests/Feature/Auth/PasswordConfirmationTest.php) — Testes de confirmação de password
- [tests/Feature/Auth/PasswordResetTest.php](tests/Feature/Auth/PasswordResetTest.php) — Testes de redefinição de password
- [tests/Feature/Auth/RegistrationTest.php](tests/Feature/Auth/RegistrationTest.php) — Testes de registo de novo utilizador
- [tests/Feature/Auth/TwoFactorChallengeTest.php](tests/Feature/Auth/TwoFactorChallengeTest.php) — Testes do desafio de autenticação 2FA
- [tests/Feature/Auth/VerificationNotificationTest.php](tests/Feature/Auth/VerificationNotificationTest.php) — Testes de notificações de verificação
- [tests/Unit/ExampleTest.php](tests/Unit/ExampleTest.php) — Teste unitário de exemplo
- [tests/Pest.php](tests/Pest.php) — Configuração do framework Pest (test runner)
- [tests/TestCase.php](tests/TestCase.php) — Classe base para todos os testes

#### Bootstrap

- [bootstrap/app.php](bootstrap/app.php) — Ponto de entrada do bootstrap da aplicação Laravel

#### Outros

- [artisan](artisan) — Interface de linha de comandos do Laravel
- [composer.json](composer.json) — Dependências PHP do projeto
- [phpunit.xml](phpunit.xml) — Configuração do PHPUnit para execução de testes
- [phpstan.neon](phpstan.neon) — Configuração do PHPStan (análise estática de código)
- [pint.json](pint.json) — Configuração do Laravel Pint (code style fixer)

---

## FRONT-END (Vue.js/TypeScript)

### FRONT-END - OWNER

#### Pages - Owner

- [resources/js/pages/Owner/Profile.vue](resources/js/pages/Owner/Profile.vue) — Página do perfil do owner: lista os animais, permite adicionar/editar e mostra as reservas
- [resources/js/pages/Homepage.vue](resources/js/pages/Homepage.vue) — Página inicial pública com banner, chamada para ação e links para login/registo

#### Modals - Owner

- [resources/js/pages/Modals/ModalAddPet.vue](resources/js/pages/Modals/ModalAddPet.vue) — Modal para adicionar um novo animal de estimação com formulário completo
- [resources/js/pages/Modals/ModalDeleteAnimal.vue](resources/js/pages/Modals/ModalDeleteAnimal.vue) — Modal de confirmação para eliminar um animal
- [resources/js/pages/Modals/ModalEditOwnerProfile.vue](resources/js/pages/Modals/ModalEditOwnerProfile.vue) — Modal para editar os dados do perfil do owner
- [resources/js/pages/Modals/ModalPetDetails.vue](resources/js/pages/Modals/ModalPetDetails.vue) — Modal que mostra os detalhes completos de um animal
- [resources/js/pages/Modals/ModalNewPost.vue](resources/js/pages/Modals/ModalNewPost.vue) — Modal para criar um novo post (para petsitters)

#### Components - Owner

- [resources/js/components/AnimalCard.vue](resources/js/components/AnimalCard.vue) — Componente de cartão que exibe as informações resumidas de um animal

#### Types - Owner

- [resources/js/types/animal.ts](resources/js/types/animal.ts) — Interfaces TypeScript para o tipo Animal
- [resources/js/types/post.ts](resources/js/types/post.ts) — Interfaces TypeScript para o tipo Post

---

### FRONT-END - PETSITTER

#### Pages - Petsitter

- [resources/js/pages/Petsitter/Profile.vue](resources/js/pages/Petsitter/Profile.vue) — Página de perfil do petsitter: mostra biografia, experiência, preço, disponibilidade e permite edição
- [resources/js/pages/Petsitter/Favorites.vue](resources/js/pages/Petsitter/Favorites.vue) — Página que lista os petsitters favoritos do utilizador
- [resources/js/pages/Petsitter/ProfileOwnerView.vue](resources/js/pages/Petsitter/ProfileOwnerView.vue) — Vista de perfil do petsitter para o owner (para reservar)
- [resources/js/pages/Petsitter/PublicSearchPetsitter.vue](resources/js/pages/Petsitter/PublicSearchPetsitter.vue) — Página pública de pesquisa de petsitters (sem autenticação)
- [resources/js/pages/Petsitter/SearchPetsitter.vue](resources/js/pages/Petsitter/SearchPetsitter.vue) — Página de pesquisa de petsitters com filtros (localização, preço, tamanho)

#### Modals - Petsitter

- [resources/js/pages/Modals/ModalEditPetsitterProfile.vue](resources/js/pages/Modals/ModalEditPetsitterProfile.vue) — Modal para editar o perfil do petsitter (biografia, preço, etc.)
- [resources/js/pages/Modals/ModalFormPetsitter.vue](resources/js/pages/Modals/ModalFormPetsitter.vue) — Modal com formulário para criar um novo perfil de petsitter
- [resources/js/pages/Modals/ModalReportPetsitter.vue](resources/js/pages/Modals/ModalReportPetsitter.vue) — Modal para reportar um petsitter (cria ticket de suporte)
- [resources/js/pages/Modals/ModalReviewPetsitter.vue](resources/js/pages/Modals/ModalReviewPetsitter.vue) — Modal para deixar uma avaliação (review) a um petsitter

#### Types - Petsitter

- [resources/js/types/petsitter.ts](resources/js/types/petsitter.ts) — Interfaces TypeScript para o tipo Petsitter

---

### FRONT-END - COMUM / GERAL

#### Pages - Autenticação

- [resources/js/pages/auth/Login.vue](resources/js/pages/auth/Login.vue) — Página de login com email e password
- [resources/js/pages/auth/Register.vue](resources/js/pages/auth/Register.vue) — Página de registo de novo utilizador
- [resources/js/pages/auth/ConfirmPassword.vue](resources/js/pages/auth/ConfirmPassword.vue) — Página para confirmar a password antes de ações sensíveis
- [resources/js/pages/auth/ForgotPassword.vue](resources/js/pages/auth/ForgotPassword.vue) — Página de "esqueci a password" com envio de email de redefinição
- [resources/js/pages/auth/ResetPassword.vue](resources/js/pages/auth/ResetPassword.vue) — Página para redefinir a password com token
- [resources/js/pages/auth/TwoFactorChallenge.vue](resources/js/pages/auth/TwoFactorChallenge.vue) — Página de verificação 2FA (código ou recovery code)
- [resources/js/pages/auth/VerifyEmail.vue](resources/js/pages/auth/VerifyEmail.vue) — Página de verificação de email com reenvio de link

#### Pages - Settings

- [resources/js/pages/settings/Profile.vue](resources/js/pages/settings/Profile.vue) — Página de definições do perfil: editar nome, email e eliminar conta
- [resources/js/pages/settings/Security.vue](resources/js/pages/settings/Security.vue) — Página de segurança: alterar password, ativar 2FA, gerir passkeys
- [resources/js/pages/settings/Appearance.vue](resources/js/pages/settings/Appearance.vue) — Página de aparência: escolher tema claro/escuro/sistema

#### Modals - Comum

- [resources/js/pages/Modals/ModalTests.vue](resources/js/pages/Modals/ModalTests.vue) — Modal de testes e debug

#### Layouts

- [resources/js/layouts/AppLayout.vue](resources/js/layouts/AppLayout.vue) — Layout principal da aplicação com header e sidebar
- [resources/js/layouts/AuthLayout.vue](resources/js/layouts/AuthLayout.vue) — Layout base para páginas de autenticação
- [resources/js/layouts/app/AppHeaderLayout.vue](resources/js/layouts/app/AppHeaderLayout.vue) — Layout com header no topo da aplicação
- [resources/js/layouts/app/AppSidebarLayout.vue](resources/js/layouts/app/AppSidebarLayout.vue) — Layout com sidebar lateral na aplicação
- [resources/js/layouts/auth/AuthCardLayout.vue](resources/js/layouts/auth/AuthCardLayout.vue) — Layout de autenticação com cartão centralizado
- [resources/js/layouts/auth/AuthSimpleLayout.vue](resources/js/layouts/auth/AuthSimpleLayout.vue) — Layout de autenticação simples e minimalista
- [resources/js/layouts/auth/AuthSplitLayout.vue](resources/js/layouts/auth/AuthSplitLayout.vue) — Layout de autenticação com divisão (imagem + formulário)
- [resources/js/layouts/settings/Layout.vue](resources/js/layouts/settings/Layout.vue) — Layout comum para as páginas de definições

#### Components - UI Principais

- [resources/js/components/AlertError.vue](resources/js/components/AlertError.vue) — Componente para exibir mensagens de erro
- [resources/js/components/AppContent.vue](resources/js/components/AppContent.vue) — Contentor principal do conteúdo da página
- [resources/js/components/AppHeader.vue](resources/js/components/AppHeader.vue) — Barra superior da aplicação com navegação
- [resources/js/components/AppLogo.vue](resources/js/components/AppLogo.vue) — Logo completo da aplicação Patudo
- [resources/js/components/AppLogoIcon.vue](resources/js/components/AppLogoIcon.vue) — Ícone do logo da aplicação
- [resources/js/components/AppShell.vue](resources/js/components/AppShell.vue) — Estrutura base que envolve toda a aplicação
- [resources/js/components/AppSidebar.vue](resources/js/components/AppSidebar.vue) — Barra lateral de navegação da aplicação
- [resources/js/components/AppSidebarHeader.vue](resources/js/components/AppSidebarHeader.vue) — Cabeçalho da barra lateral
- [resources/js/components/AppearanceTabs.vue](resources/js/components/AppearanceTabs.vue) — Abas para selecionar o tema de aparência
- [resources/js/components/Breadcrumbs.vue](resources/js/components/Breadcrumbs.vue) — Trilha de navegação (breadcrumbs)
- [resources/js/components/DeleteUser.vue](resources/js/components/DeleteUser.vue) — Componente para eliminar a conta do utilizador
- [resources/js/components/Heading.vue](resources/js/components/Heading.vue) — Componente de título de secção
- [resources/js/components/InputError.vue](resources/js/components/InputError.vue) — Exibe mensagens de erro de validação de formulários
- [resources/js/components/ManagePasskeys.vue](resources/js/components/ManagePasskeys.vue) — Componente para gerir chaves de passagem (passkeys)
- [resources/js/components/ManageTwoFactor.vue](resources/js/components/ManageTwoFactor.vue) — Componente para gerir autenticação 2FA
- [resources/js/components/NavFooter.vue](resources/js/components/NavFooter.vue) — Rodapé da barra de navegação lateral
- [resources/js/components/NavMain.vue](resources/js/components/NavMain.vue) — Menu principal de navegação
- [resources/js/components/NavUser.vue](resources/js/components/NavUser.vue) — Menu do utilizador no header (avatar, nome, logout)
- [resources/js/components/PasskeyItem.vue](resources/js/components/PasskeyItem.vue) — Item individual de uma passkey na lista
- [resources/js/components/PasskeyRegister.vue](resources/js/components/PasskeyRegister.vue) — Formulário para registar uma nova passkey
- [resources/js/components/PasskeyVerify.vue](resources/js/components/PasskeyVerify.vue) — Componente para verificar uma passkey
- [resources/js/components/PasswordInput.vue](resources/js/components/PasswordInput.vue) — Campo de input de password com toggle de visibilidade
- [resources/js/components/PlaceholderPattern.vue](resources/js/components/PlaceholderPattern.vue) — Padrão decorativo de fundo
- [resources/js/components/TextLink.vue](resources/js/components/TextLink.vue) — Link de texto estilizado
- [resources/js/components/TwoFactorRecoveryCodes.vue](resources/js/components/TwoFactorRecoveryCodes.vue) — Exibe e permite gerar códigos de recuperação 2FA
- [resources/js/components/TwoFactorSetupModal.vue](resources/js/components/TwoFactorSetupModal.vue) — Modal para configurar a autenticação 2FA
- [resources/js/components/UserInfo.vue](resources/js/components/UserInfo.vue) — Exibe informações do utilizador (avatar, nome, email)
- [resources/js/components/UserMenuContent.vue](resources/js/components/UserMenuContent.vue) — Conteúdo do menu dropdown do utilizador

#### Components - UI Library (Shadcn)

- [resources/js/components/ui/alert/Alert.vue](resources/js/components/ui/alert/Alert.vue) — Componente de alerta da biblioteca Shadcn
- [resources/js/components/ui/alert/AlertDescription.vue](resources/js/components/ui/alert/AlertDescription.vue) — Descrição dentro de um alerta Shadcn
- [resources/js/components/ui/alert/AlertTitle.vue](resources/js/components/ui/alert/AlertTitle.vue) — Título dentro de um alerta Shadcn
- [resources/js/components/ui/alert/index.ts](resources/js/components/ui/alert/index.ts) — Exports do componente Alert
- [resources/js/components/ui/avatar/Avatar.vue](resources/js/components/ui/avatar/Avatar.vue) — Componente de avatar da biblioteca Shadcn
- [resources/js/components/ui/avatar/AvatarFallback.vue](resources/js/components/ui/avatar/AvatarFallback.vue) — Fallback (iniciais) quando a imagem do avatar falha
- [resources/js/components/ui/avatar/AvatarImage.vue](resources/js/components/ui/avatar/AvatarImage.vue) — Imagem do avatar Shadcn
- [resources/js/components/ui/avatar/index.ts](resources/js/components/ui/avatar/index.ts) — Exports do componente Avatar
- [resources/js/components/ui/badge/Badge.vue](resources/js/components/ui/badge/Badge.vue) — Componente de badge/etiqueta da biblioteca Shadcn
- [resources/js/components/ui/badge/index.ts](resources/js/components/ui/badge/index.ts) — Exports do componente Badge
- [resources/js/components/ui/breadcrumb/Breadcrumb.vue](resources/js/components/ui/breadcrumb/Breadcrumb.vue) — Componente de breadcrumb da biblioteca Shadcn
- [resources/js/components/ui/breadcrumb/BreadcrumbEllipsis.vue](resources/js/components/ui/breadcrumb/BreadcrumbEllipsis.vue) — Reticências no breadcrumb
- [resources/js/components/ui/breadcrumb/BreadcrumbItem.vue](resources/js/components/ui/breadcrumb/BreadcrumbItem.vue) — Item individual do breadcrumb
- [resources/js/components/ui/breadcrumb/BreadcrumbLink.vue](resources/js/components/ui/breadcrumb/BreadcrumbLink.vue) — Link dentro do breadcrumb
- [resources/js/components/ui/breadcrumb/BreadcrumbList.vue](resources/js/components/ui/breadcrumb/BreadcrumbList.vue) — Lista de itens do breadcrumb
- [resources/js/components/ui/breadcrumb/BreadcrumbPage.vue](resources/js/components/ui/breadcrumb/BreadcrumbPage.vue) — Página atual no breadcrumb (não clicável)
- [resources/js/components/ui/breadcrumb/BreadcrumbSeparator.vue](resources/js/components/ui/breadcrumb/BreadcrumbSeparator.vue) — Separador entre itens do breadcrumb
- [resources/js/components/ui/breadcrumb/index.ts](resources/js/components/ui/breadcrumb/index.ts) — Exports do componente Breadcrumb
- [resources/js/components/ui/button/Button.vue](resources/js/components/ui/button/Button.vue) — Componente de botão da biblioteca Shadcn
- [resources/js/components/ui/button/index.ts](resources/js/components/ui/button/index.ts) — Exports do componente Button

#### Composables

- [resources/js/composables/useAppearance.ts](resources/js/composables/useAppearance.ts) — Composable para gerir o tema de aparência (claro/escuro)
- [resources/js/composables/useAuth.ts](resources/js/composables/useAuth.ts) — Composable para funções de autenticação no front-end
- [resources/js/composables/useCurrentUrl.ts](resources/js/composables/useCurrentUrl.ts) — Composable para obter a URL atual
- [resources/js/composables/useInitials.ts](resources/js/composables/useInitials.ts) — Composable para extrair iniciais de um nome
- [resources/js/composables/useTwoFactorAuth.ts](resources/js/composables/useTwoFactorAuth.ts) — Composable para gerir autenticação 2FA no front-end

#### Types

- [resources/js/types/auth.ts](resources/js/types/auth.ts) — Interfaces TypeScript para autenticação
- [resources/js/types/global.d.ts](resources/js/types/global.d.ts) — Declarações de tipos globais do TypeScript
- [resources/js/types/index.ts](resources/js/types/index.ts) — Exports centrais de todos os tipos TypeScript
- [resources/js/types/navigation.ts](resources/js/types/navigation.ts) — Interfaces TypeScript para navegação e menus
- [resources/js/types/ui.ts](resources/js/types/ui.ts) — Interfaces TypeScript para componentes de UI
- [resources/js/types/vue-shims.d.ts](resources/js/types/vue-shims.d.ts) — Declarações para módulos .vue no TypeScript

#### Lib

- [resources/js/lib/flashToast.ts](resources/js/lib/flashToast.ts) — Utilitário para exibir notificações toast a partir de flash messages do Laravel
- [resources/js/lib/utils.ts](resources/js/lib/utils.ts) — Funções utilitárias gerais (cn, mergeClasses, etc.)

#### Entry Point

- [resources/js/app.ts](resources/js/app.ts) — Ponto de entrada principal da aplicação Vue.js com Inertia

#### CSS

- [resources/css/app.css](resources/css/app.css) — Estilos globais da aplicação (Tailwind, variáveis CSS)
- [resources/css/login.css](resources/css/login.css) — Estilos específicos da página de login
- [resources/css/register.css](resources/css/register.css) — Estilos específicos da página de registo

#### Configuração Frontend

- [package.json](package.json) — Dependências Node.js e scripts do front-end
- [vite.config.ts](vite.config.ts) — Configuração do Vite (bundler do front-end)
- [tsconfig.json](tsconfig.json) — Configuração do TypeScript
- [components.json](components.json) — Configuração da biblioteca de componentes Shadcn
- [pnpm-workspace.yaml](pnpm-workspace.yaml) — Configuração do pnpm workspace

---

*Fim do documento*
