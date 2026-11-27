# Preparação da VPS e publicação do Keep Focus

Preparar o checkout ativo da VPS para o repositório `VtsAshDev/keep-focus-app`, validar `https://keep-focus.luvcode.tech` e publicar a entrega de Hábitos quando ela estiver aprovada na `main`. A operação usa o checkout, o SQLite e o projeto do Docker Compose já em produção.

## Estado em 27 de novembro de 2026

- O registro DNS `A` de `keep-focus.luvcode.tech` resolve para `187.77.60.230`. HTTPS, OAuth e a configuração efetiva do servidor precisam ser conferidos a partir da VPS.
- A `main` do GitHub está em `a3a00df`. O último pipeline dessa branch registrou sucesso no CI e no job de deploy. Confirme na VPS o SHA em uso. [Execução na main](https://github.com/VtsAshDev/keep-focus-app/actions/runs/39827115331)
- Hábitos e a alteração do pipeline para ler `VPS_PROJECT_PATH` fazem parte da `KT-21`. Confirme a integração da entrega na `main` e o CI no GitHub antes de publicar. Pint e 206 testes Pest passaram localmente. O PHPStan encontrou um erro interno no Windows.

## Preparar o checkout e proteger os dados

1. Identifique o checkout realmente usado pelo contêiner Laravel, o usuário de deploy, o SHA implantado, a porta do Sail e o caminho efetivo do SQLite. Confira `git status` e o bind mount. Se houver alterações locais ou commits divergentes, apure a origem antes de liberar o próximo deploy. O job atual executa `git reset --hard origin/main` e pode sobrescrever alterações no checkout.
2. Registre o SHA, a URL anterior do `origin` sem credenciais, o caminho e o estado dos serviços. Faça cópias protegidas do `.env` e da configuração Nginx pertinente antes de editá-los. Crie um snapshot do SQLite com `.backup` ou a API de backup do SQLite e verifique `PRAGMA quick_check` na cópia. Guarde o backup com acesso restrito e use o destino externo já adotado pelo dono, se estiver disponível. [SQLite Online Backup API](https://sqlite.org/backup.html)
3. Atualize o `origin` do checkout ativo preservando o protocolo de autenticação. Use `https://github.com/VtsAshDev/keep-focus-app.git` para HTTPS ou `git@github.com:VtsAshDev/keep-focus-app.git` para SSH. Valide com `git ls-remote origin refs/heads/main` como o usuário que executa o deploy.
4. Configure `VPS_PROJECT_PATH` nas variáveis do GitHub Actions com o caminho absoluto confirmado. Enquanto a versão atual do workflow estiver na `main`, o deploy ainda usa o caminho fixo do checkout anterior. Mantenha a pasta e o projeto Compose em uso durante esta transição. Se a conta da VPS não puder editar a variável no GitHub, informe ao dono o valor exato antes do merge.

## Validar domínio, TLS e configuração do app

1. Confira se o DNS aponta para a VPS e se o virtual host Nginx encaminha ao Laravel na porta efetiva do Sail. Aproveite a configuração ativa quando ela estiver correta. Se precisar ajustar o site, valide com `nginx -t` antes de recarregar. Use o certificado válido do domínio ou emita um após confirmar que o tráfego público chega ao host correto. Teste o redirecionamento HTTP para HTTPS.
2. Confirme no Google Cloud Console que o cliente atual autoriza `https://keep-focus.luvcode.tech/auth/google/callback` e a origem `https://keep-focus.luvcode.tech`. Mantenha as regras do host anterior enquanto ele for usado. Se a confirmação depender do dono, continue os preparativos e deixe a troca da URL e do callback do Laravel para depois da confirmação.
3. Com HTTPS e o novo callback liberados, confira no Laravel os valores efetivos de `APP_NAME=Keep Focus`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://keep-focus.luvcode.tech`, locale `pt_BR`, fuso `America/Sao_Paulo` e `SESSION_SECURE_COOKIE=true`. Ajuste os valores divergentes e reconstrua o cache pelo Sail. Preserve `APP_KEY`, as credenciais OAuth, a porta ativa e o caminho do SQLite. Confira se a rota de login envia o novo `redirect_uri`. Exiba apenas valores não sensíveis e reserve o login completo e a instalação do PWA para uma sessão autenticada no celular.

## Publicar Hábitos

O próximo deploy deve vir de um PR revisado com Pint, PHPStan e Pest verdes. Quando o código estiver na `main`, acompanhe o pipeline desse commit. Se a `KT-21` ainda estiver só no computador de desenvolvimento, conclua os preparativos possíveis na VPS e registre a publicação como pendente. O fluxo da VPS usa a `main` e o coordenador do desenvolvimento abre e integra o PR.

Antes da publicação, confirme novamente o caminho, o checkout limpo e o backup. Depois do job, compare o SHA implantado com o commit aprovado. Pelo Sail, confira o status das migrations, incluindo a de Hábitos quando a entrega estiver no ar. Confira o manifesto, os assets, as rotas `/hoje` e `/habitos` e os erros novos no log. Valide os suplementos migrados por contagens agregadas ou com uma conta autorizada. Os registros novos podem alterar as contagens desde a migração.

## Aceite e recuperação

- O checkout alcança a `main` pela URL nova. O SHA implantado corresponde ao pipeline aprovado, e o caminho do próximo deploy está configurado.
- HTTPS e o manifesto do Keep Focus respondem no domínio. Em uma sessão autenticada no celular, o login Google leva ao Hoje, o PWA instala e o fluxo de treino permite iniciar, concluir série com timer, retomar, finalizar e marcar um suplemento.
- Após publicar Hábitos, criar um hábito, marcá-lo, recarregar a tela e conferir a persistência. Desmarcar e arquivar devem refletir no Hoje.
- Se a alteração de configuração causar regressão, restaure a configuração envolvida a partir da cópia, valide o Nginx e reconstrua o cache do Laravel. Se falhar o deploy de código, registre a etapa e o erro e recupere a versão anterior pelo fluxo da `main`. A restauração do SQLite exige aprovação do dono se puder descartar gravações posteriores ao backup.

No fechamento, informe o checkout, o SHA antes e depois, o caminho e a verificação do backup, os ajustes feitos, os testes concluídos e as pendências com o responsável. [Roteiro de deploy](../deploy.md)
