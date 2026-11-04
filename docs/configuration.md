# Configuração da instalação

Não inclua senhas, tokens, bancos, arquivos recebidos, diretórios privados ou dados de pessoas neste repositório.

| Configuração | Fonte | Comportamento padrão |
| --- | --- | --- |
| `HERMES_API_URL`, `HERMES_API_TOKEN` | Constantes em configuração privada do WordPress | Sem Hermes, `wp_mail` usa o transporte padrão; os fluxos que exigem Hermes retornam erro controlado. |
| `INTRANET_DB_NAME` | Constante ou variável de ambiente | Banco `intranet`, para compatibilidade com a stack institucional. |
| `INTRANET_AUDITORIUM_RESERVATION_URL` | Ambiente, ou opção `institutional_theme_auditorium_reservation_url` | Integração indisponível até ser configurada. |
| `CENEX_DELEGATED_USER_ID` | Ambiente, ou chave `delegated_user_id` da opção `institutional_theme_cenex_delegation` | Sem delegação. |
| `CENEX_TARGET_USER_ID` | Ambiente, ou chave `target_user_id` da mesma opção | Sem delegação. |
| `CENEX_DELEGATION_EXPIRES_AT` | Ambiente, ou chave `expires_at` da mesma opção | Sem delegação. |

A delegação só fica ativa quando os dois IDs são positivos e a validade está no futuro. Nenhum ID de uma instalação específica está embutido no código. Configurações privadas devem ser fornecidas pelo administrador da instalação.

Os slugs dos programas, post types e shortcodes públicos são mantidos para compatibilidade com os conteúdos já cadastrados. Dados de candidatos, perguntas, respostas privadas e uploads ficam no WordPress e não devem ser exportados junto com o código.
