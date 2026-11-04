# Segurança

Não publique vulnerabilidades com dados de pessoas, credenciais, tokens, links privados ou documentos de candidatos em issues públicas. Use um canal privado de contato do mantenedor. Caso ainda não exista um canal privado no repositório, não anexe dados sensíveis à issue.

O código usa configuração privada para credenciais e delegações administrativas. O administrador deve manter WordPress, plugins e infraestrutura atualizados e configurar autenticação, permissões e proteção dos uploads na instalação.

Antes de publicar novas alterações, revise os arquivos preparados para commit e execute um scanner de segredos, por exemplo:

```sh
gitleaks dir . --redact --no-banner
gitleaks git . --redact --no-banner
```

Não faça commit de arquivos `.env`, backups, dumps, logs, bancos de dados ou documentos recebidos. Se um segredo real for publicado, removê-lo de um arquivo não basta: revogue ou rotacione o segredo e trate sua presença no histórico.
