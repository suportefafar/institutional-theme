<?php

require_once __DIR__ . '/dynamic-ui.php';

/** Resolve a configuração existente ou o banco padrão da stack Olympus. */
function fafar_intranet_database_name() {
    $name = defined( 'INTRANET_DB_NAME' ) ? INTRANET_DB_NAME : ( getenv( 'INTRANET_DB_NAME' ) ?: 'intranet' );
    return is_string( $name ) && preg_match( '/^[a-zA-Z0-9_]+$/', $name ) ? $name : null;
}

/** Uma falha externa não deve interromper a página pública. */
function fafar_intranet_results( $query ) {
    global $wpdb;
    $previous = $wpdb->suppress_errors();
    try {
        $rows = $wpdb->get_results( $query, ARRAY_A );
        return $wpdb->last_error ? null : $rows;
    } finally {
        $wpdb->suppress_errors( $previous );
    }
}

function fafar_intranet_unavailable() {
    echo fafar_dynamic_notice( 'As informações estão temporariamente indisponíveis. Tente novamente mais tarde.' );
}


function get_people_from_intranet($args = [])
{
    global $wpdb;
    $database = fafar_intranet_database_name();
    if ( ! $database ) { fafar_intranet_unavailable(); return; }
    $category = '';
    foreach ( $args['meta_query'] ?? array() as $condition ) {
        if ( is_array( $condition ) && 'public_servant_bond_category' === ( $condition['key'] ?? '' ) ) { $category = $condition['value']; }
    }
    // Consulta isolada: não troca as tabelas globais nem mistura caches de usuários dos dois sites.
    $query = $wpdb->prepare(
        "SELECT u.display_name, u.user_email, ext.meta_value AS workplace_extension, caps.meta_value AS capabilities
         FROM `{$database}`.wp_users u
         INNER JOIN `{$database}`.wp_usermeta status ON status.user_id=u.ID AND status.meta_key='bond_status' AND status.meta_value='ATIVO'
         INNER JOIN `{$database}`.wp_usermeta bond ON bond.user_id=u.ID AND bond.meta_key='public_servant_bond_category' AND bond.meta_value=%s
         LEFT JOIN `{$database}`.wp_usermeta ext ON ext.user_id=u.ID AND ext.meta_key='workplace_extension'
         LEFT JOIN `{$database}`.wp_usermeta caps ON caps.user_id=u.ID AND caps.meta_key='wp_capabilities'
         ORDER BY u.display_name", $category
    );
    $users = fafar_dynamic_query( $query );
    $option = fafar_dynamic_query( "SELECT option_value FROM `{$database}`.wp_options WHERE option_name='wp_user_roles' LIMIT 1", true );
    if ( false === $users || false === $option ) { fafar_intranet_unavailable(); return; }
    $roles = $option ? unserialize( $option->option_value, array( 'allowed_classes' => false ) ) : array();
    $people = array();
    foreach ( $users as $user ) {
        $caps = $user->capabilities ? unserialize( $user->capabilities, array( 'allowed_classes' => false ) ) : array();
        $caps = is_array( $caps ) ? $caps : array();
        if ( ! empty( $args['capability'] ) && empty( $caps[ $args['capability'] ] ) ) { continue; }
        $label = '';
        foreach ( $caps as $capability => $enabled ) {
            if ( $enabled && isset( $roles[ $capability ]['name'] ) ) { $label = $roles[ $capability ]['name']; break; }
        }
        $people[] = array( 'display_name' => $user->display_name, 'roles' => array( $label ), 'user_email' => $user->user_email, 'workplace_extension' => $user->workplace_extension );
    }
    people_content_handler( $people );
}

function people_content_handler($people)
{
    echo '<p class="dynamic-intro">Consulte os contatos e filtre por nome, setor ou e-mail.</p>
        <form class="dynamic-filters" data-table-filter="dynamic-people-results" role="search" aria-label="Pesquisar contatos">
            <div class="dynamic-field"><label for="dynamic-people-query">Nome, setor ou e-mail</label><input class="ffds-form-control" id="dynamic-people-query" type="search" data-filter-column="*" placeholder="Digite para encontrar um contato"></div>
            <button class="ffds-btn ffds-btn-primary" type="submit">Pesquisar</button>
        </form><p class="dynamic-results" data-filter-status="dynamic-people-results" aria-live="polite"></p>';
    $rows = '';
    foreach ( $people as $person ) {
        $email = sanitize_email( $person['user_email'] );
        $rows .= '<tr data-filter-row><th scope="row">' . esc_html( $person['display_name'] ) . '</th><td>' . esc_html( $person['roles'][0] ?? '' ) . '</td><td><a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a></td><td class="dynamic-nowrap">' . esc_html( $person['workplace_extension'] ) . '</td></tr>';
    }
    fafar_dynamic_table( array( 'Nome', 'Setor', 'E-mail', 'Ramal' ), $rows, 'Diretório de contatos', 'dynamic-people-results', 'dynamic-people-table' );
}

function parse_weekday_to_name($number)
{

    if (!$number)
        return 'N/A';

    if (!is_numeric($number))
        return $number;

    return array(
        0 => 'Dom',
        1 => 'Seg',
        2 => 'Ter',
        3 => 'Qua',
        4 => 'Qui',
        5 => 'Sex',
        6 => 'Sáb',
    )[(int)$number] ?? '--';
}

function mapa_salas_content_handler()
{
    global $wpdb;
    $intranet_db = fafar_intranet_database_name();
    if ( ! $intranet_db ) { fafar_intranet_unavailable(); return; }

    // Calcular início do semestre atual
    $mes_atual = (int)date('m');
    $ano_atual = (int)date('Y');

    // Semestre 1: Fev a Jul (inicia em 01/02)
    // Semestre 2: Ago a Jan (inicia em 01/08)
    if ($mes_atual >= 2 && $mes_atual <= 7) {
        $inicio_semestre = $ano_atual . '-02-01';
    }
    else {
        $ano_inicio = ($mes_atual < 2) ? $ano_atual - 1 : $ano_atual;
        $inicio_semestre = $ano_inicio . '-08-01';
    }

    $timestamp_inicio = get_timestamp_by_date($inicio_semestre);

    $reservas = fafar_intranet_results("SELECT * FROM `" . $intranet_db . "`.`wp_fafar_cf7crud_submissions` WHERE `object_name` = 'reservation' AND `is_active` = '1'");
    if ( null === $reservas ) { fafar_intranet_unavailable(); return; }

    // Decodifica os payloads antes do filtro para usar a data real da reserva.
    foreach ($reservas as &$reserva) {
        $reserva['data'] = json_decode($reserva['data'], true);

        if (!is_array($reserva['data'])) {
            $reserva['data'] = [];
        }
    }
    unset($reserva);

    // Filtrar apenas reservas feitas para o semestre atual
    $reservas = array_filter($reservas, function ($reserva) use ($timestamp_inicio) {
        $data_execucao = $reserva['data']['date'] ?? $reserva['created_at'] ?? null;

        if (!$data_execucao)
            return true; // Se não houver data, mantém para não quebrar

        return strtotime($data_execucao) >= $timestamp_inicio;
    });

    $objetos_referenciados = [];

    foreach ($reservas as $reserva) {
        $disciplina_id = $reserva['data']['class_subject'][0] ?? null;
        $sala_id = $reserva['data']['place'][0] ?? null;

        if ($disciplina_id) {
            $objetos_referenciados[(string)$disciplina_id] = null;
        }

        if ($sala_id) {
            $objetos_referenciados[(string)$sala_id] = null;
        }
    }

    // Busca disciplinas e salas em lote, preservando os IDs alfanuméricos.
    if ($objetos_referenciados) {
        $placeholders = implode(', ', array_fill(0, count($objetos_referenciados), '%s'));
        $query = $wpdb->prepare(
            "SELECT `id`, `data` FROM `" . $intranet_db . "`.`wp_fafar_cf7crud_submissions` WHERE `id` IN ({$placeholders})",
            array_keys($objetos_referenciados)
        );
        $objetos = fafar_intranet_results($query);
        if ( null === $objetos ) { fafar_intranet_unavailable(); return; }

        foreach ($objetos as $objeto) {
            $data = json_decode($objeto['data'], true);
            $objetos_referenciados[(string)$objeto['id']] = is_array($data) ? $data : null;
        }
    }

    $line_objs = [];

    foreach ($reservas as $reserva) {

        if (empty($reserva['data']['class_subject'][0]) || empty($reserva['data']['place'][0]))
            continue;

        $disciplina_id = (string)$reserva['data']['class_subject'][0];
        $sala_id = (string)$reserva['data']['place'][0];
        $disciplina = $objetos_referenciados[$disciplina_id] ?? null;
        $sala = $objetos_referenciados[$sala_id] ?? null;

        if (!$disciplina || !$sala)
            continue;



        $dias_da_semana = implode(', ', array_map(function ($weekday) {
            return parse_weekday_to_name($weekday); }, (array)($reserva['data']['weekdays'] ?? [])));

        $codigo_disciplina = $disciplina['code'] ?? '';
        $nome_disciplina = $disciplina['name_of_subject'] ?? '';
        $turma = $disciplina['group'] ?? '';
        $desc = trim($codigo_disciplina . ' ' . $nome_disciplina . ($turma !== '' ? ' (' . $turma . ')' : ''));

        if (
        !empty($reserva['data']['desc']) &&
        count(explode(' ', $reserva['data']['desc'])) > 1
        ) {
            $code = explode(' ', $reserva['data']['desc'])[0];
            $groups = explode(' ', $reserva['data']['desc'])[1];
            $desc = trim($code . ' ' . $nome_disciplina . ' ' . $groups);
        }

        $line_objs[] = array(
            'desc_reserva' => $desc,
            'desc_sala' => esc_html( $sala['number'] ?? '' ) . ' <br />Bloco: ' . esc_html( $sala['block'] ?? '' ) . ' <br />Andar: ' . esc_html( $sala['floor'] ?? '' ) . 'º',
            'dias_semana' => $dias_da_semana,
            'hora_inicio' => $reserva['data']['start_time'] ?? '',
            'hora_fim' => $reserva['data']['end_time'] ?? '',
        );

    }

    // Ordenar pelo código da disciplina
    usort($line_objs, function ($a, $b) {
        return $a['desc_reserva'] <=> $b['desc_reserva']; // Sort ascending
    });

    $lines = "";

    foreach ($line_objs as $line_obj) {

        $lines .= '<tr data-filter-row>' .
            '<th scope="row">' . esc_html( $line_obj['desc_reserva'] ) . '</th>' .
            '<td>' . $line_obj['desc_sala'] . '</td>' .
            '<td>' . esc_html( $line_obj['dias_semana'] ) . '</td>' .
            '<td>' . esc_html( $line_obj['hora_inicio'] ) . '</td>' .
            '<td>' . esc_html( $line_obj['hora_fim'] ) . '</td>' .
            '</tr>';

    }


    $html = '<div class="room-map dynamic-tool">
                <p class="dynamic-intro">Consulte as reservas de salas do semestre por código ou nome da disciplina.</p>
                <form class="dynamic-filters" data-table-filter="tbody_mapa_sala" role="search" aria-label="Pesquisar no mapa de salas">
                    <div class="dynamic-field"><label for="input_mapa_sala">Código ou nome da disciplina</label><input class="ffds-form-control" id="input_mapa_sala" type="search" placeholder="Ex.: ACT007" data-filter-column="0"></div>
                    <button class="ffds-btn ffds-btn-primary" id="button_mapa_sala" type="submit">Pesquisar</button>
                </form><p class="dynamic-results" data-filter-status="tbody_mapa_sala" aria-live="polite"></p>
                <div class="table-responsive dynamic-table-scroll room-map-scroll" role="region" aria-label="Mapa de salas por disciplina" tabindex="0">
                    <table class="table dynamic-table room-map-table">
                        <colgroup><col><col class="room-map-room"><col class="room-map-days"><col class="room-map-time"><col class="room-map-time"></colgroup>
                        <thead>
                            <tr>
                                <th scope="col">Disciplina</th>
                                <th scope="col">Sala</th>
                                <th scope="col">Dia da semana</th>
                                <th scope="col">Início</th>
                                <th scope="col">Fim</th>
                            </tr>
                        </thead>
                        <tbody id="tbody_mapa_sala">' .
        $lines . '<tr data-filter-empty' . ( $lines ? ' hidden' : '' ) . '><td colspan="5" class="dynamic-empty">Nenhuma reserva encontrada.</td></tr>' .
        '</tbody>
                    </table>
                </div>
            </div>';

    echo $html;

}

function separe_unique($arr)
{

    $arr_u = array();

    $inicio_semestre = get_timestamp_by_date('2024-09-23');
    foreach ($arr as $item) {

        if (((int)$item->inicio) < ($inicio_semestre * 1000))
            continue;

        if (((int)$item->frequencia) == 0)
            continue;

        $key_item = $item->sala . "." .
            $item->cod_disciplina . "." .
            $item->diasemana . "." .
            $item->horainicio . "." .
            $item->horafim;

        $val_item = $item->id . "." .
            $item->inicio . "." .
            $item->fim;

        if (!isset($arr_u[$key_item]))
            $arr_u[$key_item] = $val_item;

    }

    return $arr_u;
}

function obterObjetoPorID($id, $arr)
{

    foreach ($arr as $item) {

        if ($id === $item->id)
            return $item;

    }

    return false;

}

function get_timestamp_by_date($date)
{

    $d = date_create($date, new DateTimeZone('America/Sao_Paulo'));
    return $d->getTimestamp();

}

function intranet_fafar_api_get_hours_by_timestamp($timestamp)
{

    $d = date_create("now", new DateTimeZone('America/Sao_Paulo'));
    $d->setTimestamp((int)$timestamp);

    return $d->format('H:i');

}

function ementas_content_handler()
{
    echo '<p class="dynamic-intro">Acesse as ementas das disciplinas ou confira a autenticidade de um documento.</p><div class="dynamic-actions">';
    echo '<a class="ffds-btn ffds-btn-primary" href="' . esc_url( home_url( '/baixar-ementas/' ) ) . '">Baixar ementas</a>';
    echo '<a class="ffds-btn ffds-btn-secondary" href="' . esc_url( home_url( '/validarementa/' ) ) . '">Validar ementa</a></div>';
}

function baixar_ementas_content_handler()
{
    global $wpdb;
    echo '<div class="dynamic-toolbar"><p class="dynamic-intro">Selecione a versão curricular e procure pelo código da disciplina.</p><a href="' . esc_url( home_url( '/validarementa/' ) ) . '">Validar uma ementa →</a></div>';
    $versions = fafar_dynamic_query( 'SELECT * FROM tipo_ementas WHERE ativo = 1 ORDER BY id DESC' );
    $unavailable = false === $versions;
    $versions = $unavailable ? array() : $versions;
    $options = '';
    $rows = '';
    foreach ( $versions as $version ) {
        $options .= '<option value="' . esc_attr( $version->descricao ) . '">' . esc_html( $version->descricao ) . '</option>';
        $disciplines = fafar_dynamic_query( $wpdb->prepare( 'SELECT * FROM ementas WHERE versao = %d ORDER BY cod_disciplina, nome', $version->id ) );
        if ( false === $disciplines ) { $unavailable = true; break; }
        foreach ( $disciplines as $discipline ) {
            $url = esc_url( $discipline->arquivo_ementa );
            $action = $url ? '<a class="ffds-btn dynamic-download" href="' . $url . '" target="_blank" rel="noopener noreferrer">Baixar<span class="screen-reader-text"> ementa de ' . esc_html( $discipline->cod_disciplina ) . ' (abre em nova aba)</span></a>' : 'Documento indisponível';
            $rows .= '<tr data-filter-row><td>' . esc_html( $version->descricao ) . '</td><th scope="row">' . esc_html( $discipline->cod_disciplina ) . '</th><td>' . esc_html( $discipline->nome ) . '</td><td>' . $action . '</td></tr>';
        }
    }
    if ( $unavailable ) { $rows = ''; echo fafar_dynamic_notice( 'As ementas estão temporariamente indisponíveis. Tente novamente mais tarde.' ); }
    echo '<form class="dynamic-filters dynamic-filters-multiple" data-table-filter="tbody_baixar_ementas" role="search" aria-label="Filtrar ementas">
        <div class="dynamic-field"><label for="select_baixar_ementas">Versão curricular</label><select class="ffds-form-control" id="select_baixar_ementas" data-filter-column="0" data-filter-exact><option value="">Todas as versões</option>' . $options . '</select></div>
        <div class="dynamic-field"><label for="input_baixar_ementas">Código da disciplina</label><input class="ffds-form-control" id="input_baixar_ementas" type="search" data-filter-column="1" placeholder="Ex.: ACT007"></div>
        <button class="ffds-btn ffds-btn-primary" id="button_baixar_ementas" type="submit"' . ( $unavailable ? ' disabled' : '' ) . '>Filtrar</button>
        </form><p class="dynamic-results" data-filter-status="tbody_baixar_ementas" aria-live="polite"></p>';
    if ( ! $unavailable ) { fafar_dynamic_table( array( 'Versão', 'Código', 'Disciplina', 'Documento' ), $rows, 'Ementas disponíveis para download', 'tbody_baixar_ementas', 'dynamic-syllabus-table' ); }
}

function validar_ementas_content_handler()
{
    global $wpdb;
    $codigo = isset( $_GET['codigo'] ) && is_string( $_GET['codigo'] ) ? sanitize_text_field( wp_unslash( $_GET['codigo'] ) ) : '';
    echo '<div class="dynamic-toolbar"><p class="dynamic-intro">Informe o código de autenticação que aparece no documento.</p><a href="' . esc_url( home_url( '/baixar-ementas/' ) ) . '">Baixar ementas →</a></div>';
    echo '<form class="dynamic-filters" method="get" action="' . esc_url( home_url( '/validarementa/' ) ) . '"><div class="dynamic-field"><label for="dynamic-syllabus-code">Código de autenticação</label><input class="ffds-form-control" id="dynamic-syllabus-code" name="codigo" value="' . esc_attr( $codigo ) . '" maxlength="255" required></div><button class="ffds-btn ffds-btn-primary" type="submit">Validar ementa</button></form>';
    if ( '' === $codigo ) { return; }
    $result = fafar_dynamic_query( $wpdb->prepare( 'SELECT * FROM ementas a JOIN tipo_ementas b ON a.versao = b.id WHERE cod_ementa = %s', $codigo ), true );
    if ( false === $result ) { echo fafar_dynamic_notice( 'A validação de ementas está temporariamente indisponível. Tente novamente mais tarde.' ); return; }
    if ( ! $result ) { echo fafar_dynamic_notice( 'Código de ementa inválido: ' . $codigo, 'error' ); return; }
    echo fafar_dynamic_notice( 'Ementa encontrada. Confira os dados abaixo.', 'success' );
    echo '<dl class="dynamic-details">';
    foreach ( array( 'Código' => $result->cod_disciplina, 'Nome' => $result->nome, 'Subnome' => $result->subnome, 'Versão curricular' => $result->descricao ) as $label => $value ) {
        echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
    }
    echo '</dl>';
    $url = esc_url( $result->arquivo_ementa );
    if ( $url ) { echo '<a class="ffds-btn ffds-btn-primary" href="' . $url . '" target="_blank" rel="noopener noreferrer">Abrir documento<span class="screen-reader-text"> (abre em nova aba)</span></a>'; }
}

function tecnicos_administrativos_content_handler()
{
    get_people_from_intranet([
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'TAE',
            ]
        ]
    ]);
}

function corpo_administrativo_act_content_handler()
{
    get_people_from_intranet([
        'capability' => 'act',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'TAE',
            ]
        ]
    ]);
}

function corpo_administrativo_alm_content_handler()
{
    get_people_from_intranet([
        'capability' => 'alm',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'TAE',
            ]
        ]
    ]);
}

function corpo_administrativo_fas_content_handler()
{
    get_people_from_intranet([
        'capability' => 'fas',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'TAE',
            ]
        ]
    ]);
}

function corpo_administrativo_pfa_content_handler()
{
    get_people_from_intranet([
        'capability' => 'pfa',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'TAE',
            ]
        ]
    ]);
}

function corpo_docente_act_content_handler()
{
    get_people_from_intranet([
        'capability' => 'act',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'DOCENTE',
            ]
        ]
    ]);
}

function corpo_docente_alm_content_handler()
{
    get_people_from_intranet([
        'capability' => 'alm',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'DOCENTE',
            ]
        ]
    ]);
}

function corpo_docente_fas_content_handler()
{
    get_people_from_intranet([
        'capability' => 'fas',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'DOCENTE',
            ]
        ]
    ]);
}

function corpo_docente_pfa_content_handler()
{
    get_people_from_intranet([
        'capability' => 'pfa',
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'DOCENTE',
            ]
        ]
    ]);
}

function corpo_docente_all_content_handler()
{
    get_people_from_intranet([
        'fields' => 'all',
        'orderby' => 'display_name',
        'meta_query' => [
            'relation' => 'AND',
            [
                'key' => 'bond_status',
                'value' => 'ATIVO',
            ],
            [
                'key' => 'public_servant_bond_category',
                'value' => 'DOCENTE',
            ]
        ]
    ]);
}

function dynamic_pages_handler()
{
    foreach ( fafar_dynamic_routes() as $slug => $handler ) {
        if ( is_page( $slug ) ) {
            echo '<section class="dynamic-page" aria-label="' . esc_attr( get_the_title() ) . '">';
            $handler();
            echo '</section>';
            return;
        }
    }
}

add_action( 'fafar_entry_content_before', 'dynamic_pages_handler' );
