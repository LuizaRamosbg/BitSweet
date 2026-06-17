# Flutter Frontend

Este diretório contém a implementação inicial do frontend em Flutter para o sistema de confeitaria.

## Como usar

1. Instale o Flutter no seu ambiente local.
2. Abra o terminal no diretório `frontend_flutter`.
3. Se o projeto ainda não estiver inicializado como projeto Flutter, execute:

```bash
flutter create .
```

4. Instale dependências:

```bash
flutter pub get
```

5. Execute o app:

```bash
flutter run
```

## API

A aplicação consome o backend em:

`http://localhost/PHP_confeitaria/api`

Se o backend estiver em outra URL, ajuste `ApiService.baseUrl` em `lib/services/api_service.dart`.

## Funcionalidades implementadas

- Listagem de receitas
- Detalhes de receita e ingredientes
- Cadastro de nova receita
- Listagem e cadastro de clientes
- Listagem e cadastro de encomendas
- Listagem e cadastro de compras
