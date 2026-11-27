# Keep Focus

Keep Focus é um hub pessoal para acompanhar a rotina diária. Na tela **Hoje**, você marca seus hábitos, vê o treino sugerido e acompanha a meta de treinos e os suplementos.

- **Hábitos** permite cadastrar e arquivar hábitos diários e registrar a conclusão do dia.
- **Treino** reúne fichas em rodízio, séries pré-preenchidas, timer de descanso, histórico de carga e suplementação.
- O app usa Laravel 13, Livewire 4, Tailwind 4 e SQLite. É instalável como PWA e oferece login com Google ou e-mail.

## Desenvolvimento local

O ambiente do CI usa PHP 8.5, Composer 2 e Node.js 22. Em um checkout novo, crie o banco SQLite antes de preparar o app.

```bash
git clone https://github.com/VtsAshDev/keep-focus-app.git
cd keep-focus-app
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
composer setup
php artisan serve
```

Abra [http://localhost:8000](http://localhost:8000). Para usar o login com Google localmente, configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` e `GOOGLE_REDIRECT_URI` no `.env`.

Rode `composer ci:check` para executar Pint, PHPStan e Pest.

Consulte as [convenções do projeto](AGENTS.md), as [referências de interface](docs/design/README.md) e o [roteiro de deploy](docs/deploy.md).
