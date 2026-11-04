# Relatório de revisão de conteúdo e SEO — 4 de novembro de 2026

## Escopo processado

- 1.689 posts publicados.
- 788 páginas publicadas.
- 314 conteúdos receberam simplificações textuais conservadoras.
- 611 títulos tiveram espaços, travessões ou indicação de semestre padronizados.
- 2.470 conteúdos receberam uma descrição individual para a metatag `description`.
- 1.932 imagens receberam texto alternativo na biblioteca de mídia; as 1.021 imagens presentes no HTML publicado também foram verificadas e não possuem `alt` ausente ou vazio.
- 165 textos alternativos inicialmente derivados de nomes de arquivo ou nomes crus de logomarca foram refinados com o contexto da página de origem.
- 49 conteúdos com tabelas foram preservados.

O processo alterou somente nós de texto, títulos e atributos `alt`. A validação recusou mudanças que modificassem a estrutura HTML. Links, destinos, imagens, tabelas, shortcodes e demais elementos existentes foram mantidos. `post_modified` e `post_modified_gmt` foram preservados.

## Conteúdos pulados por possível inconsistência

Estes conteúdos não receberam as mudanças em massa e precisam de conferência editorial:

- [Seleção 2022 — PPGCA](https://www.farmacia.ufmg.br/selecao-ppgca/selecao-2022/): um link rotulado como resultado final aponta para um arquivo chamado resultado preliminar.
- [Resultado preliminar PAME](https://www.farmacia.ufmg.br/ppgact/resultado-preliminar-pame/): o texto parece contar o prazo de recurso do resultado preliminar a partir da divulgação do resultado final.
- [Resultado preliminar PADO](https://www.farmacia.ufmg.br/ppgact/resultado-preliminar-pado/): o texto parece contar o prazo de recurso do resultado preliminar a partir da divulgação do resultado final.
- [Resultado preliminar PADO — segundo semestre de 2024](https://www.farmacia.ufmg.br/ppgca/resultado-preliminar-pado-2a-semestre-de-2024/): o texto parece contar o prazo de recurso do resultado preliminar a partir da divulgação do resultado final.
- [Seleção 2026 — PPGCA](https://www.farmacia.ufmg.br/selecao-ppgca/selecao-2026/): um link rotulado como resultado final aponta para um arquivo chamado resultado preliminar.
- [Edital ALM nº 05/2026 — PMG](https://www.farmacia.ufmg.br/alm/edital-alm-no-05-2026-processo-seletivo-pmg-graduacao/): o link rotulado como resultado preliminar aponta para um arquivo chamado resultado final.
- [Resultado preliminar do processo seletivo PPGMAF 2026/2](https://www.farmacia.ufmg.br/ppgmaf/processo-seletivo-mestrado-e-doutorado-2026-2-ppgmaf-resultado-preliminar/): o texto parece contar o prazo de recurso do resultado preliminar a partir da divulgação do resultado final.

## Imagens sem contexto suficiente

Vinte e nove anexos permaneceram sem texto alternativo porque possuem nome genérico e não estão associados a uma página de origem. Descrever seu conteúdo sem examinar as imagens seria impreciso. IDs: 965, 1082, 1085, 1086, 1087, 1088, 1089, 1090, 1091, 1092, 1094, 1095, 1096, 1097, 1098, 3585, 3586, 3587, 3588, 14841, 14842, 18017, 23532, 25092, 25093, 25094, 25095, 25096 e 31539.

## Links internos já quebrados

A auditoria consultou 4.194 destinos internos. Foram encontradas 90 referências quebradas em 58 conteúdos. Os links não foram removidos ou trocados. Os grupos mais frequentes são arquivos antigos ausentes em `wp-content/uploads`, referências à página removida `/ofertadisciplinas/` e páginas antigas de serviços ou equipamentos que retornam 404. A lista detalhada foi mantida no servidor durante a auditoria em `/tmp/fafar-broken-links.tsv`.

## Verificações

- Uma segunda execução completa não encontrou texto, título, descrição ou texto alternativo pendente entre os itens processáveis.
- Nenhuma das 2.470 datas de modificação comparadas mudou.
- O HTML público exibe os títulos padronizados e as descrições individuais.
- As páginas de teste e áreas operacionais continuam com `noindex` e fora do sitemap.
