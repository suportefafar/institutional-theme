# Revisão para publicação pública

Data: 2026-11-04. Projeto: `institutional-theme`.

## Escopo e resultado

O projeto foi revisto em código PHP/JavaScript/CSS, testes, documentação, exemplos e recursos gráficos. Gitleaks 8.30.1 foi executado com os valores ocultos nos relatórios; nenhuma credencial, chave privada ou token foi detectado nos arquivos preparados para publicação. O histórico Git novo passa pela mesma verificação antes do envio. A identidade de commit utiliza o endereço institucional `noreply` já usado no repositório remoto.

A revisão manual verificou atribuições de senhas/tokens, URLs com credenciais, chaves de acesso, identificadores de delegação, caminhos locais, endereços internos, fixtures e metadados das imagens.

## Ajustes realizados

- IDs administrativos e validade de delegação foram retirados do código. A delegação agora exige configuração privada explícita e fica desligada por padrão.
- O endpoint interno de reservas passou para ambiente/opção privada do WordPress.
- Foram removidos o relatório histórico de auditoria da instalação, exemplos legados sem uso e código de certificados inativo que continha referências de infraestrutura e processamento antigo de senhas.
- A documentação foi reescrita sem caminhos pessoais ou configurações reais do ambiente.
- `.gitignore` exclui arquivos de ambiente, logs, backups e artefatos locais. Credenciais continuam fora do tema.
- O repositório novo não incorpora o histórico Git do projeto anterior.

## Conteúdo público preservado

As imagens não contêm EXIF, GPS ou comentários de autoria/caminhos locais nos campos verificados. O screenshot é o fornecido para apresentação do tema. Contatos institucionais já publicados no formulário de consentimento e links públicos para modelos de documentos foram mantidos. Fixtures usam dados sintéticos e endereços de teste.

Banco de dados, uploads, candidaturas, perguntas privadas, configurações operacionais e relatórios brutos do scanner não acompanham este repositório. Os resultados descrevem a revisão estática do conteúdo publicado; não constituem uma auditoria completa dos plugins ou da infraestrutura de execução.
