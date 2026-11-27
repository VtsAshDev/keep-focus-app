# Deploy do Keep Focus

Domínio: **https://keep-focus.luvcode.tech**. A VPS recebe o código da `main` pelo GitHub Actions.

Consulte a [spec de preparação da VPS e publicação de Hábitos](specs/keep-focus-vps.md) para executar a transição do repositório.

## Repositório e checkout na VPS

- O endereço do repositório `keep-focus-app` é `https://github.com/VtsAshDev/keep-focus-app.git` para HTTPS ou `git@github.com:VtsAshDev/keep-focus-app.git` para SSH. Use o endereço correspondente ao atualizar o `origin` de um checkout existente.
- Antes do primeiro deploy com o novo nome, configure a variável de repositório `VPS_PROJECT_PATH` em **Settings → Secrets and variables → Actions → Variables** com o caminho absoluto do checkout ativo na VPS. Em novas instalações, o padrão é `/opt/projects/keep-focus`.
- Para usar os scripts com o checkout existente, exporte o mesmo `VPS_PROJECT_PATH` no shell que os executará. O caminho continua associado ao banco SQLite, ao `.env` e ao projeto do Docker Compose.

## Roteiro com os scripts

Os passos abaixo estão automatizados em dois scripts; o resto deste documento explica cada um.

1. **No WSL**, baixar e validar a cópia do banco (não altera a produção):
   ```bash
   scripts/validate-production-db.sh root@187.77.60.230
   ```
   Todas as linhas precisam terminar em ✔. Mande o resultado antes de seguir.
2. **À mão:** confira o DNS de `keep-focus` e as URLs no Google Cloud Console (seção "Google Cloud Console").
3. **Copiar o script para a VPS:**
   ```bash
   scp scripts/vps-launch.sh root@187.77.60.230:/root/
   ```
4. **Na VPS**, com o DNS já respondendo:
   ```bash
   bash /root/vps-launch.sh nginx     # site do Nginx + certificado SSL
   ```
5. **Na hora do lançamento**, na VPS:
   ```bash
   bash /root/vps-launch.sh env       # backup + .env do Keep Focus (use "env --new-key" para trocar o APP_KEY)
   ```
   Execute o pipeline da `main` na janela de lançamento e aguarde o deploy para aplicar a configuração em cache.
6. **Depois do deploy**, na VPS:
   ```bash
   bash /root/vps-launch.sh check     # migrations, resumo da cópia dos suplementos e HTTPS
   ```
7. Reinstalar o PWA no celular e testar o fluxo (seção "Depois do deploy").

Para voltar atrás: os backups do banco e do `.env` ficam em `/root/keep-focus-backups/` (gerados por `vps-launch.sh backup` e `env`) e em `database/backups/` (gerados pelo deploy).

## Backup e verificação dos suplementos migrados

1. **Backup do banco de produção.** Copiar `database/database.sqlite` da VPS para um lugar seguro fora do servidor.
2. **Validar a cópia dos suplementos (etapa 5)** com essa cópia, numa máquina local:
   - colocar o arquivo em `database/producao.sqlite` (ignorado pelo git);
   - dentro do container: `cp database/producao.sqlite /tmp/kf-prod.sqlite && DB_DATABASE=/tmp/kf-prod.sqlite php artisan migrate --force`;
   - comparar `suplementos` × `supplements` e `consumo_diario` × `supplement_logs`, e conferir o resumo `Legacy supplements copied` no `storage/logs/laravel.log` (registros ignorados por pertencerem a outro usuário aparecem em `skipped_foreign_logs`).
3. Confira a presença dos suplementos e registros no app publicado. A limpeza das tabelas antigas fica para uma migration própria após essa conferência.

## DNS, Nginx e SSL

1. **DNS:** registro `A` de `keep-focus.luvcode.tech` apontando para o IP da VPS.
2. **Nginx:** um `server` para `keep-focus.luvcode.tech` encaminha as requisições ao Sail na porta de `APP_PORT` (hoje `8082`). O app usa `X-Forwarded-Proto` para reconhecer HTTPS.

   ```nginx
   server {
       server_name keep-focus.luvcode.tech;

       location / {
           proxy_pass http://127.0.0.1:8082;
           proxy_set_header Host $host;
           proxy_set_header X-Real-IP $remote_addr;
           proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
           proxy_set_header X-Forwarded-Proto $scheme;
       }
   }
   ```

3. **SSL:** `certbot --nginx -d keep-focus.luvcode.tech`.
4. Opcionalmente, configure o host anterior para redirecionar ao Keep Focus (`return 301 https://keep-focus.luvcode.tech$request_uri;`).

## Google Cloud Console

No cliente OAuth usado hoje:

- **Origens JavaScript autorizadas:** adicionar `https://keep-focus.luvcode.tech`.
- **URIs de redirecionamento autorizados:** adicionar `https://keep-focus.luvcode.tech/auth/google/callback`.
- Manter as URLs antigas até o redirecionamento do domínio antigo estar no ar.

## `.env` da VPS

Alterar ou adicionar (manter `APP_PORT=8082`, `APP_SERVICE`, `APP_KEY` e as credenciais do Google que já existem na VPS):

```dotenv
APP_NAME="Keep Focus"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://keep-focus.luvcode.tech
APP_LOCALE=pt_BR
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=pt_BR
APP_TIMEZONE=America/Sao_Paulo
APP_VERSION=1.0.0

SESSION_SECURE_COOKIE=true

GOOGLE_REDIRECT_URI=https://keep-focus.luvcode.tech/auth/google/callback
```

O `APP_NAME` define o nome padrão do cookie de sessão. Se ele mudar, os usuários precisam entrar novamente. Preserve o `APP_KEY` existente durante a troca de nome e aplique o `.env` na janela de lançamento, quando o deploy reconstruir o cache de configuração.

## Merge e deploy

1. Abrir o PR para a `main` e esperar o CI verde.
2. Fazer o merge. O deploy da `main` roda sozinho e agora faz um backup do SQLite em `database/backups/` antes do `migrate` (guarda os 10 mais recentes).
3. Conferir na VPS o resumo `Legacy supplements copied` no log e abrir `/hoje`.

## Depois do deploy

- Para trocar do PWA instalado pelo domínio anterior, abra `https://keep-focus.luvcode.tech` no celular e instale pelo menu do navegador.
- Testar no celular: entrar com Google, iniciar um treino, concluir uma série (timer), fechar o app, retomar e finalizar; marcar um suplemento.
- A cada versão que mudar ícones, manifest ou a página offline, aumentar `CACHE_VERSION` em `public/sw.js`.
