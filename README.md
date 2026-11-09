# Institutional Theme

Tema WordPress independente para o site institucional da Faculdade de Farmácia da UFMG, com apresentação editorial responsiva e componentes compatíveis com o conteúdo institucional existente.

## Prévia da página inicial

Captura do ambiente de desenvolvimento, com postagens existentes no banco e imagens editoriais associadas aos destaques:

![Página inicial da FAFAR no ambiente de desenvolvimento](docs/homepage-preview.png)

## Instalação

Instale o projeto em `wp-content/themes/institutional-theme` e ative **Institutional Theme** em Aparência → Temas, ou execute:

```sh
wp theme activate institutional-theme
```

O tema não exige um tema pai. A interface é clara, com rodapé escuro, fontes locais, navegação por teclado e layouts adaptados ao celular. O domínio de tradução é `institutional-theme`.

## Estrutura

```text
functions.php           Bootstrap dos módulos
inc/setup.php           Recursos do tema, menus e áreas de widgets
inc/assets.php          Carregamento dos estilos e scripts
inc/template-tags.php   Funções de apresentação
inc/helpers.php         Seleção contextual de conteúdo
inc/banner.php          Banner institucional
inc/shortcodes.php      Shortcodes compatíveis com o conteúdo existente
inc/integrations.php    Integrações de reservas e e-mail
inc/dynamic-pages.php   Mapas, ementas e diretórios de contatos
inc/dynamic-ui.php      Componentes e registro das rotas dinâmicas
inc/modules/            PPGCF, PPGACT, Cemed, e-books e permissões
assets/                CSS, JavaScript, fontes e imagens locais
page-*.php              Templates específicos de páginas do WordPress
template-parts/         Conteúdo e cards reutilizáveis
tests/                 Testes de regressão e integração
docs/                  Documentação e revisão para publicação
```

Os templates `page-*.php` permanecem na raiz para seguir a hierarquia de templates do WordPress. A captura atual da home está em `docs/homepage-preview.png`; `screenshot.jpg` é a imagem de prévia cadastrada para o tema no WordPress.

## Recursos

- Cabeçalho com busca, menus contextuais e submenus com texto e seta em um único botão. Subitens laterais têm recuo e linha contínua âmbar.
- Rodapé com links institucionais, imagem da FAFAR, endereço e assinatura UFMG.
- Dezessete rotas dinâmicas compartilham formulários, tabelas, estados de indisponibilidade e um único script de filtragem. Não carregam Bootstrap. Conteúdo editorial legado e shortcodes de terceiros podem carregá-lo quando necessário.
- Comentários são exibidos e aceitos apenas em posts publicados do Cemed. Comentários antigos de outras páginas são ocultados, sem apagar registros. Pingbacks estão desativados.
- Formulários e calendários de inscrição dos programas de pós-graduação, perguntas e respostas do Cemed, consulta à intranet, biblioteca e integrações de e-mail preservados.

## Configuração privada

Credenciais e configuração operacional pertencem ao ambiente ou ao WordPress, não ao Git. Consulte [docs/configuration.md](docs/configuration.md). O tema não carrega arquivos `.env` por conta própria. `.gitignore` exclui configurações locais, logs, dependências e pacotes gerados.

Os dados e as opções do WordPress não acompanham o repositório. Na migração de uma instalação existente, copie as opções de menus e logo para o novo identificador antes de ativar o tema e preserve os widgets e módulos já configurados.

O catálogo legado depende de `tipo_ementas` e `ementas`. Na ausência dessas tabelas, downloads e validação exibem indisponibilidade sem expor SQL. A tabela `wp_ementas` usa outro esquema e não é substituta automática. A emissão de certificados permanece em manutenção.

## Testes

```sh
php -d zend.assertions=1 -d assert.exception=1 tests/security.php
php tests/dynamic-tools.php
php tests/intranet-map.php
php tests/comment-policy.php
php tests/cenex-configuration.php
php tests/ppgcf-registration-periods.php
php tests/ppgact-registration-periods.php
# Requer uma instalação WordPress com o tema ativo:
php tests/theme-runtime.php
```

A interface foi conferida por HTTP em 320, 390, 768, 1024 e 1440 px. Os testes de navegação e renderização não enviam inscrições, perguntas ou mensagens de e-mail.

## Licenças

Código do tema: GPL-3.0-or-later, conforme [LICENSE](LICENSE). Os materiais do commit inicial do repositório mantêm o aviso MIT em `LICENSES/initial-repository-MIT.txt`. Componentes do Farmácia UFMG Design System: MIT, com aviso em `assets/css/design-system/LICENSE`. Fontes: SIL OFL, com os textos em `assets/fonts/`. As imagens institucionais são recursos de identidade da FAFAR/UFMG; sua presença neste projeto não concede direitos sobre as marcas.

Consulte [SECURITY.md](SECURITY.md) e [docs/publication-review.md](docs/publication-review.md) para os cuidados de publicação.

## SEO e publicação

`inc/seo.php` adiciona descrições, contextualiza títulos de páginas selecionadas, exclui páginas operacionais e de teste do sitemap e solicita `noindex` para elas. A proteção de acesso continua a cargo das regras próprias de cada página. O módulo também converte URLs internas legadas em HTTP para HTTPS na saída e atualiza o link antigo da Farmácia Jr.

Na instalação de produção auditada, a mesma versão do arquivo foi instalada como `wp-content/mu-plugins/fafar-seo.php`, pois o tema ativo é publicado por uma versão fixada. O MU plugin é carregado primeiro e tem precedência sobre o módulo do tema; ao publicar mudanças futuras de SEO, atualize esse MU plugin pelo processo de implantação ou remova-o depois de publicar o tema atualizado. Manter as duas cópias evita conflito de execução, mas uma cópia antiga do MU plugin prevalecerá.
