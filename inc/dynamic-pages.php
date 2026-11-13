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
        "SELECT u.display_name, u.user_email, ext.meta_value AS workplace_extension, place.meta_value AS workplace_place, caps.meta_value AS capabilities
         FROM `{$database}`.wp_users u
         INNER JOIN `{$database}`.wp_usermeta status ON status.user_id=u.ID AND status.meta_key='bond_status' AND status.meta_value='ATIVO'
         INNER JOIN `{$database}`.wp_usermeta bond ON bond.user_id=u.ID AND bond.meta_key='public_servant_bond_category' AND bond.meta_value=%s
         LEFT JOIN `{$database}`.wp_usermeta ext ON ext.user_id=u.ID AND ext.meta_key='workplace_extension'
         LEFT JOIN `{$database}`.wp_usermeta place ON place.user_id=u.ID AND place.meta_key='workplace_place'
         LEFT JOIN `{$database}`.wp_usermeta caps ON caps.user_id=u.ID AND caps.meta_key='wp_capabilities'
         ORDER BY u.display_name", $category
    );
    $users = fafar_dynamic_query( $query );
    $option = fafar_dynamic_query( "SELECT option_value FROM `{$database}`.wp_options WHERE option_name='wp_user_roles' LIMIT 1", true );
    if ( false === $users || false === $option ) { fafar_intranet_unavailable(); return; }
    $roles = $option ? unserialize( $option->option_value, array( 'allowed_classes' => false ) ) : array();
    $people = array();
    $show_room = 'DOCENTE' === $category;
    $room_ids = array();
    foreach ( $users as $user ) {
        $caps = $user->capabilities ? unserialize( $user->capabilities, array( 'allowed_classes' => false ) ) : array();
        $caps = is_array( $caps ) ? $caps : array();
        if ( ! empty( $args['capability'] ) && empty( $caps[ $args['capability'] ] ) ) { continue; }
        $label = '';
        foreach ( $caps as $capability => $enabled ) {
            if ( $enabled && isset( $roles[ $capability ]['name'] ) ) { $label = $roles[ $capability ]['name']; break; }
        }
        if ( $show_room && $user->workplace_place ) { $room_ids[ $user->workplace_place ] = true; }
        $people[] = array( 'display_name' => $user->display_name, 'roles' => array( $label ), 'user_email' => $user->user_email, 'workplace_extension' => $user->workplace_extension, 'workplace_place' => $user->workplace_place );
    }
    $rooms = array();
    if ( $room_ids ) {
        $placeholders = implode( ', ', array_fill( 0, count( $room_ids ), '%s' ) );
        $room_query = $wpdb->prepare(
            "SELECT id, data FROM `{$database}`.wp_fafar_cf7crud_submissions WHERE object_name='place' AND id IN ({$placeholders})",
            array_keys( $room_ids )
        );
        $room_rows = fafar_intranet_results( $room_query );
        if ( null !== $room_rows ) {
            foreach ( $room_rows as $room ) {
                $data = json_decode( $room['data'], true );
                $rooms[ $room['id'] ] = is_array( $data ) ? ( $data['number'] ?? '' ) : '';
            }
        }
    }
    foreach ( $people as &$person ) {
        $person['room'] = $rooms[ $person['workplace_place'] ] ?? '';
    }
    unset( $person );
    people_content_handler( $people, $show_room );
}

function people_content_handler($people, $show_room = false)
{
    echo '<p class="dynamic-intro">Consulte os contatos e filtre por nome, setor ou e-mail.</p>
        <form class="dynamic-filters" data-table-filter="dynamic-people-results" role="search" aria-label="Pesquisar contatos">
            <div class="dynamic-field"><label for="dynamic-people-query">Nome, setor ou e-mail</label><input class="ffds-form-control" id="dynamic-people-query" type="search" data-filter-column="*" placeholder="Digite para encontrar um contato"></div>
            <button class="ffds-btn ffds-btn-primary" type="submit">Pesquisar</button>
        </form><p class="dynamic-results" data-filter-status="dynamic-people-results" aria-live="polite"></p>';
    $rows = '';
    foreach ( $people as $person ) {
        $email = sanitize_email( $person['user_email'] );
        $rows .= '<tr data-filter-row><th scope="row">' . esc_html( $person['display_name'] ) . '</th><td>' . esc_html( $person['roles'][0] ?? '' ) . '</td><td><a href="' . esc_url( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a></td>';
        if ( $show_room ) { $rows .= '<td class="dynamic-nowrap">' . esc_html( $person['room'] ?? '' ) . '</td>'; }
        $rows .= '<td class="dynamic-nowrap">' . esc_html( $person['workplace_extension'] ) . '</td></tr>';
    }
    $headings = $show_room ? array( 'Nome', 'Setor', 'E-mail', 'Sala', 'Ramal' ) : array( 'Nome', 'Setor', 'E-mail', 'Ramal' );
    fafar_dynamic_table( $headings, $rows, 'Diretório de contatos', 'dynamic-people-results', 'dynamic-people-table' );
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
    echo '<div class="dynamic-toolbar"><p class="dynamic-intro">Selecione a versão curricular e procure pelo código da disciplina.</p><a href="' . esc_url( home_url( '/validarementa/' ) ) . '">Validar uma ementa →</a></div>';
	$ementas = get_posts( array(
		'post_type' => 'fafar_ementa',
		'post_status' => 'publish',
		'posts_per_page' => -1,
		'orderby' => array( 'meta_value' => 'ASC', 'title' => 'ASC' ),
		'meta_key' => '_fafar_ementa_codigo',
	) );
	$versions = array();
    $options = '';
    $rows = '';
	foreach ( $ementas as $ementa ) {
		$version = get_post_meta( $ementa->ID, '_fafar_ementa_versao', true );
		$code = get_post_meta( $ementa->ID, '_fafar_ementa_codigo', true );
		$url = esc_url( get_post_meta( $ementa->ID, '_fafar_ementa_arquivo', true ) );
		if ( '' !== $version ) { $versions[ $version ] = $version; }
		$action = $url ? '<a class="ffds-btn dynamic-download" href="' . $url . '" target="_blank" rel="noopener noreferrer">Baixar<span class="screen-reader-text"> ementa de ' . esc_html( $code ) . ' (abre em nova aba)</span></a>' : 'Documento indisponível';
		$rows .= '<tr data-filter-row><td>' . esc_html( $version ) . '</td><th scope="row">' . esc_html( $code ) . '</th><td>' . esc_html( get_the_title( $ementa ) ) . '</td><td>' . $action . '</td></tr>';
    }
	natcasesort( $versions );
	foreach ( $versions as $version ) { $options .= '<option value="' . esc_attr( $version ) . '">' . esc_html( $version ) . '</option>'; }
    echo '<form class="dynamic-filters dynamic-filters-multiple" data-table-filter="tbody_baixar_ementas" role="search" aria-label="Filtrar ementas">
        <div class="dynamic-field"><label for="select_baixar_ementas">Versão curricular</label><select class="ffds-form-control" id="select_baixar_ementas" data-filter-column="0" data-filter-exact><option value="">Todas as versões</option>' . $options . '</select></div>
        <div class="dynamic-field"><label for="input_baixar_ementas">Código da disciplina</label><input class="ffds-form-control" id="input_baixar_ementas" type="search" data-filter-column="1" placeholder="Ex.: ACT007"></div>
        <button class="ffds-btn ffds-btn-primary" id="button_baixar_ementas" type="submit">Filtrar</button>
        </form><p class="dynamic-results" data-filter-status="tbody_baixar_ementas" aria-live="polite"></p>';
	fafar_dynamic_table( array( 'Versão', 'Código', 'Disciplina', 'Documento' ), $rows, 'Ementas disponíveis para download', 'tbody_baixar_ementas', 'dynamic-syllabus-table' );
}

function validar_ementas_content_handler()
{
    $codigo = isset( $_GET['codigo'] ) && is_string( $_GET['codigo'] ) ? sanitize_text_field( wp_unslash( $_GET['codigo'] ) ) : '';
    echo '<div class="dynamic-toolbar"><p class="dynamic-intro">Informe o código de autenticação que aparece no documento.</p><a href="' . esc_url( home_url( '/baixar-ementas/' ) ) . '">Baixar ementas →</a></div>';
    echo '<form class="dynamic-filters" method="get" action="' . esc_url( home_url( '/validarementa/' ) ) . '"><div class="dynamic-field"><label for="dynamic-syllabus-code">Código de autenticação</label><input class="ffds-form-control" id="dynamic-syllabus-code" name="codigo" value="' . esc_attr( $codigo ) . '" maxlength="255" required></div><button class="ffds-btn ffds-btn-primary" type="submit">Validar ementa</button></form>';
    if ( '' === $codigo ) { return; }
	$matches = get_posts( array(
		'post_type' => 'fafar_ementa',
		'post_status' => 'publish',
		'posts_per_page' => 1,
		'meta_key' => '_fafar_ementa_codigo_validacao',
		'meta_value' => $codigo,
	) );
	if ( ! $matches ) { echo fafar_dynamic_notice( 'Código de ementa inválido: ' . $codigo, 'error' ); return; }
	$result = $matches[0];
    echo fafar_dynamic_notice( 'Ementa encontrada. Confira os dados abaixo.', 'success' );
    echo '<dl class="dynamic-details">';
	foreach ( array( 'Código' => get_post_meta( $result->ID, '_fafar_ementa_codigo', true ), 'Nome' => get_the_title( $result ), 'Versão curricular' => get_post_meta( $result->ID, '_fafar_ementa_versao', true ) ) as $label => $value ) {
        echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd></div>';
    }
    echo '</dl>';
	$url = esc_url( get_post_meta( $result->ID, '_fafar_ementa_arquivo', true ) );
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

/** Protótipo dos perfis do PPGMAF com dados disponíveis na página e nos Lattes fornecidos. */
function corpo_docente_ppgmaf_content_handler()
{
    $faculty = array(
        'permanentes' => array(
            array( 'name' => 'Adriano Max Moreira Reis', 'unit' => 'PFA/FAFAR/UFMG', 'email' => 'amreis@farmacia.ufmg.br', 'lattes' => '4412547750108123', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4704960A4', 'bio' => 'Professor Titular da Faculdade de Farmácia da UFMG, doutor em Ciências pela USP e mestre em Ciências Farmacêuticas pela UFMG. Atua em farmacoterapia do idoso, farmacovigilância, utilização de medicamentos, segurança do paciente e assistência farmacêutica hospitalar e em serviços de saúde.' ),
            array( 'name' => 'Augusto Afonso Guerra Júnior', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'augustoguerramg@gmail.com', 'lattes' => '4998210231373901', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4791796Y6', 'bio' => 'Professor Associado da UFMG, doutor em Saúde Pública e mestre em Ciências Farmacêuticas. Pesquisa farmacoepidemiologia, farmacogenômica, farmacoeconomia, políticas de saúde e avaliação de tecnologias em saúde. Coordena o FarGenix e o CCATES/UFMG.' ),
            array( 'name' => 'Clarice Chemello', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'clachemello@gmail.com', 'lattes' => '4528500699816146', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4734396J3', 'bio' => 'Professora do Departamento de Farmácia Social da UFMG, doutora em Farmácia Assistencial pela Universidad de Granada e coordenadora do PPGMAF. Atua em Farmácia Clínica, Atenção Farmacêutica e Farmacogenética.' ),
            array( 'name' => 'Cristiane A. M. de Pádua', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'menezescristiane@hotmail.com', 'lattes' => '0999148325656924', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4777370J9', 'bio' => 'Professora Titular da Faculdade de Farmácia da UFMG, docente permanente e subcoordenadora do PPGMAF. Atua em Epidemiologia e Farmacoepidemiologia e coordena o Centro de Estudo do Medicamento (Cemed/UFMG).' ),
            array( 'name' => 'Cristina Mariano Ruas', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'crisruasufmg@gmail.com', 'lattes' => '3535918051912413', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4504288H7', 'bio' => 'Farmacêutica, mestre e doutora em Saúde Pública, é professora da Faculdade de Farmácia da UFMG e integra o PPGMAF. Atua em Saúde Coletiva, farmacoepidemiologia, farmacoeconomia e políticas de medicamentos, com pesquisas sobre uso, segurança, acesso e custos.' ),
            array( 'name' => 'Djenane Ramalho de Oliveira', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'djenane.oliveira@gmail.com', 'lattes' => '1148969228307246', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4703095E2', 'bio' => 'Professora Titular da UFMG, farmacêutica, PhD em Social and Administrative Pharmacy pela University of Minnesota e pós-doutora pela University of Edinburgh. Desenvolve atividades de ensino, pesquisa e extensão em cuidado farmacêutico e prática clínica.' ),
            array( 'name' => 'Edna Afonso Reis', 'unit' => 'EST/ICEX/UFMG', 'email' => 'edna@est.ufmg.br', 'lattes' => '3773191587995244', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4767418J6', 'bio' => 'Professora Associada do Departamento de Estatística do ICEx/UFMG. É graduada, mestre e doutora em Estatística e integra o PPGMAF desde 2012.' ),
            array( 'name' => 'Helian Nunes de Oliveira', 'unit' => 'MPS/MEDICINA/UFMG', 'email' => 'heliannunes@gmail.com', 'lattes' => '2872805590419095', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4127635J3', 'bio' => 'Médico psiquiatra, pesquisador e professor do Departamento de Medicina Preventiva e Social da UFMG. Atua em saúde mental, saúde pública e saúde coletiva e participa do PPGMAF.' ),
            array( 'name' => 'Juliana Alvares Teodoro', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'jualvares@gmail.com', 'lattes' => '8338204270737352', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4774457D1', 'bio' => 'Professora Associada do Departamento de Farmácia Social da UFMG. Doutora em Saúde Pública e mestre em Ciências Farmacêuticas pela UFMG. Atua em farmacoepidemiologia, avaliação de tecnologias em saúde e economia da saúde.' ),
            array( 'name' => 'Maria Auxiliadora P. Martins', 'unit' => 'PFA/FAFAR/UFMG', 'email' => 'auxiliadorapmartins@hotmail.com', 'lattes' => '4405925489665474', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4763246D0', 'bio' => 'Professora Associada do Departamento de Produtos Farmacêuticos da UFMG. Atua em Farmácia Hospitalar e coordena programas de residência em saúde no Hospital Risoleta Tolentino Neves.' ),
            array( 'name' => 'Mariana M. G. do Nascimento', 'unit' => 'PFA/FAFAR/UFMG', 'email' => 'marianamgn@yahoo.com.br', 'lattes' => '4157162836464120', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4736451D5', 'bio' => 'Professora Adjunta do Departamento de Produtos Farmacêuticos da UFMG. Farmacêutica, doutora em Ciências da Saúde e pós-doutora pelo PPGMAF. Atua em Farmácia Hospitalar, cuidado farmacêutico e assistência farmacêutica.' ),
            array( 'name' => 'Maria das Graças Braga', 'unit' => 'PFA/FAFAR/UFMG', 'email' => 'mgbceccato@gmail.com', 'lattes' => '1976803349720094', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4770029Y3', 'bio' => 'Professora Associada do Departamento de Farmácia Social da UFMG, doutora em Saúde Pública e mestre em Ciências Farmacêuticas. Atua em farmacoepidemiologia, saúde coletiva e assistência farmacêutica.' ),
            array( 'name' => 'Marina Guimarães Lima', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'marina.glima@gmail.com', 'lattes' => '8321059230428548', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4710528J7', 'bio' => 'Professora Associada do Departamento de Farmácia Social da UFMG. Atua em assistência farmacêutica, avaliação de serviços de saúde, educação em saúde e ensino farmacêutico.' ),
            array( 'name' => 'Micheline Rosa Silveira', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'michelinerosa@gmail.com', 'lattes' => '0144496689713578', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4799555A0', 'bio' => 'Professora Titular do Departamento de Farmácia Social da UFMG, farmacêutica, mestre e doutora em Ciências Biológicas. Atua em Ética e Legislação Farmacêutica e integra o PPGMAF.' ),
            array( 'name' => 'Simone de A. Medina Mendonça', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'simoneamm@gmail.com', 'lattes' => '3448232730811718', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4779201H8', 'bio' => 'Professora Adjunta do Departamento de Farmácia Social da UFMG, educadora, pesquisadora e extensionista. Dedica-se à clínica farmacêutica na atenção primária à saúde, à educação de profissionais de saúde e às humanidades em saúde.' ),
        ),
        'colaboradores' => array(
            array( 'name' => 'Yone de Almeida Nascimento', 'unit' => 'FAS/FAFAR/UFMG', 'email' => 'yone.almeida1@gmail.com', 'lattes' => '7553187616002928', 'photo' => 'https://servicosweb.cnpq.br/wspessoa/servletrecuperafoto?tipo=1&id=K4751442A8', 'bio' => 'Farmacêutica, mestre em Ciências Farmacêuticas e doutora em Medicamentos e Assistência Farmacêutica pela UFMG. Professora Adjunta da Faculdade de Farmácia, atua em disciplinas e serviços de prática clínica farmacêutica.' ),
            array( 'name' => 'Ricardo Mesquita Camelo', 'unit' => 'MPS/MEDICINA/UFMG', 'email' => 'rmcamelo@hotmail.com', 'lattes' => '8637068903068965', 'photo' => '', 'bio' => 'Médico formado pela UFMG, com mestrado e doutorado em Ciências Biológicas e doutorado em Ciências Aplicadas à Saúde do Adulto. Atua em ensino médico, emergência e atenção primária. Pesquisa hemofilias e outras doenças hemorrágicas.' ),
        ),
    );

    echo '<p class="dynamic-intro">Conheça os docentes vinculados ao Programa de Pós-Graduação em Medicamentos e Assistência Farmacêutica da UFMG.</p>';
    foreach ( $faculty as $group => $members ) {
        $heading = 'permanentes' === $group ? 'Docentes permanentes' : 'Docentes colaboradores';
        echo '<section class="ppgmaf-faculty-group" aria-labelledby="ppgmaf-' . esc_attr( $group ) . '"><h2 id="ppgmaf-' . esc_attr( $group ) . '">' . esc_html( $heading ) . '</h2><div class="ppgmaf-faculty-grid">';
        foreach ( $members as $person ) {
            $has_bio = ! empty( $person['bio'] );
            $name = $person['name'];
            $name_parts = preg_split( '/\s+/', trim( $name ) );
            $initials = strtoupper( substr( $name_parts[0], 0, 1 ) . substr( end( $name_parts ), 0, 1 ) );
            echo '<article class="ppgmaf-faculty-card' . ( $has_bio ? '' : ' is-incomplete' ) . '">';
            echo '<div class="ppgmaf-faculty-header">';
            if ( ! empty( $person['photo'] ) ) {
                echo '<img class="ppgmaf-faculty-photo" src="' . esc_url( $person['photo'] ) . '" alt="Fotografia de ' . esc_attr( $name ) . '" loading="lazy" decoding="async">';
            } else {
                echo '<div class="ppgmaf-faculty-photo ppgmaf-photo-placeholder" role="img" aria-label="Foto não informada para ' . esc_attr( $name ) . '"><span>' . esc_html( $initials ) . '</span><small>Foto não informada</small></div>';
            }
            echo '<div class="ppgmaf-faculty-content">';
            if ( ! $has_bio ) { echo '<span class="ppgmaf-placeholder-label">Dados em atualização</span>'; }
            echo '<h3>' . esc_html( $name ) . '</h3><p class="ppgmaf-faculty-unit">' . esc_html( $person['unit'] ) . '</p>';
            if ( ! empty( $person['email'] ) ) {
                echo '<p class="ppgmaf-faculty-email"><a href="mailto:' . esc_attr( sanitize_email( $person['email'] ) ) . '">' . esc_html( $person['email'] ) . '</a></p>';
            } else {
                echo '<p class="ppgmaf-faculty-email"><span class="ppgmaf-placeholder">E-mail não informado</span></p>';
            }
            if ( ! empty( $person['lattes'] ) ) {
                echo '<p><a class="ppgmaf-lattes-link" href="' . esc_url( 'https://lattes.cnpq.br/' . $person['lattes'] ) . '" target="_blank" rel="noopener noreferrer">Currículo Lattes <span aria-hidden="true">↗</span><span class="screen-reader-text"> (abre em nova aba)</span></a></p>';
            } else {
                echo '<p><span class="ppgmaf-placeholder">Link do Lattes não informado</span></p>';
            }
            echo '</div></div><div class="ppgmaf-faculty-bio"><h4>Biografia</h4><p>' . ( $has_bio ? esc_html( $person['bio'] ) : '<span class="ppgmaf-placeholder">Biografia não informada.</span>' ) . '</p></div></article>';
        }
        echo '</div></section>';
    }
}

/** Recupera o HTML legado da própria página para reutilizar os dados já mantidos no WordPress. */
function fafar_custom_faculty_source( $slug )
{
    $page = get_page_by_path( $slug, OBJECT, 'page' );
    if ( ! $page || ! class_exists( 'DOMDocument' ) ) { return null; }
    $previous_errors = libxml_use_internal_errors( true );
    $document = new DOMDocument( '1.0', 'UTF-8' );
    $loaded = $document->loadHTML( '<?xml encoding="UTF-8">' . get_post_field( 'post_content', $page ), LIBXML_NONET );
    libxml_clear_errors();
    libxml_use_internal_errors( $previous_errors );
    return $loaded ? array( $document, new DOMXPath( $document ) ) : null;
}

function fafar_custom_faculty_text( $node )
{
    return $node ? trim( preg_replace( '/\s+/u', ' ', $node->textContent ) ) : '';
}

function fafar_custom_faculty_element( $xpath, $context, $query )
{
    $nodes = $xpath->query( $query, $context );
    return $nodes && $nodes->length ? $nodes->item( 0 ) : null;
}

function fafar_custom_faculty_image( $xpath, $context )
{
    $image = fafar_custom_faculty_element( $xpath, $context, '(.//img)[1]' );
    return $image ? fafar_custom_faculty_image_url( $image->getAttribute( 'src' ) ) : '';
}

/** O banco local reescreve as URLs antigas para farmacia.local, onde esses arquivos não existem. */
function fafar_custom_faculty_image_url( $url )
{
    $parts = wp_parse_url( $url );
    if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) { return $url; }
    $host = strtolower( $parts['host'] );
    $local_hosts = array( 'farmacia.local', 'www.farmacia.local' );
    if ( in_array( $host, $local_hosts, true ) && 0 === strpos( $parts['path'], '/wp-content/uploads/' ) ) {
        return 'https://www.farmacia.ufmg.br' . $parts['path'] . ( empty( $parts['query'] ) ? '' : '?' . $parts['query'] );
    }
    return $url;
}

function fafar_custom_faculty_link( $xpath, $context )
{
    $link = fafar_custom_faculty_element( $xpath, $context, '(.//a[@href])[1]' );
    return $link ? $link->getAttribute( 'href' ) : '';
}

function fafar_custom_faculty_directory_groups()
{
    $source = fafar_custom_faculty_source( 'corpo-docente-4' );
    if ( ! $source ) { return array(); }
    list( $document, $xpath ) = $source;
    $groups = array();
    foreach ( $xpath->query( '//h4' ) as $heading ) {
        $wrapper = fafar_custom_faculty_element( $xpath, $heading, 'following::div[contains(concat(" ", normalize-space(@class), " "), " table-responsive ")][1]' );
        if ( ! $wrapper ) { continue; }
        $table = fafar_custom_faculty_element( $xpath, $wrapper, './/table[1]' );
        if ( ! $table ) { continue; }
        $group = array( 'title' => fafar_custom_faculty_text( $heading ), 'people' => array() );
        foreach ( $xpath->query( './/tr', $table ) as $row ) {
            $cells = $xpath->query( './td', $row );
            if ( ! $cells || $cells->length < 5 ) { continue; }
            $person = array(
                'name' => fafar_custom_faculty_text( $cells->item( 1 ) ),
                'unit' => fafar_custom_faculty_text( $cells->item( 2 ) ),
                'email' => fafar_custom_faculty_text( $cells->item( 3 ) ),
                'photo' => fafar_custom_faculty_image( $xpath, $cells->item( 0 ) ),
                'lattes' => fafar_custom_faculty_link( $xpath, $cells->item( 4 ) ),
                'bio' => '',
                'profiles' => array(),
            );
            if ( $person['name'] ) { $group['people'][] = $person; }
        }
        if ( $group['people'] ) { $groups[] = $group; }
    }
    return $groups;
}

function fafar_custom_faculty_ppgact_groups()
{
    $source = fafar_custom_faculty_source( 'corpo-docente-3' );
    if ( ! $source ) { return array(); }
    list( $document, $xpath ) = $source;
    $root = fafar_custom_faculty_element( $xpath, $document, '//*[contains(concat(" ", normalize-space(@class), " "), " ppgact-docentes ")]' );
    if ( ! $root ) { return array(); }
    $groups = array();
    $current = null;
    foreach ( $root->childNodes as $child ) {
        if ( ! ( $child instanceof DOMElement ) ) { continue; }
        if ( 'h3' === strtolower( $child->tagName ) ) {
            if ( $current && $current['people'] ) { $groups[] = $current; }
            $current = array( 'title' => fafar_custom_faculty_text( $child ), 'people' => array() );
            continue;
        }
        if ( ! $current || 'div' !== strtolower( $child->tagName ) || false === strpos( ' ' . $child->getAttribute( 'class' ) . ' ', ' grade ' ) ) { continue; }
        foreach ( $xpath->query( './/article[contains(concat(" ", normalize-space(@class), " "), " docente ")]', $child ) as $article ) {
            $name_node = fafar_custom_faculty_element( $xpath, $article, './/*[contains(concat(" ", normalize-space(@class), " "), " nome ")]' );
            $unit_node = fafar_custom_faculty_element( $xpath, $article, './/*[contains(concat(" ", normalize-space(@class), " "), " unidade ")]' );
            $email_node = fafar_custom_faculty_element( $xpath, $article, './/*[contains(concat(" ", normalize-space(@class), " "), " contato ")][1]' );
            $photo_node = fafar_custom_faculty_element( $xpath, $article, './/*[contains(concat(" ", normalize-space(@class), " "), " foto ")]' );
            $bio_node = fafar_custom_faculty_element( $xpath, $article, './/*[contains(concat(" ", normalize-space(@class), " "), " biografia ")]' );
            $lattes = '';
            foreach ( $xpath->query( './/*[contains(concat(" ", normalize-space(@class), " "), " contato ")]//a[@href]', $article ) as $link ) {
                if ( false !== stripos( $link->getAttribute( 'href' ), 'lattes' ) || false !== stripos( $link->textContent, 'lattes' ) ) { $lattes = $link->getAttribute( 'href' ); }
            }
            $photo = '';
            if ( $photo_node ) {
                $photo = 'img' === strtolower( $photo_node->tagName ) ? fafar_custom_faculty_image_url( $photo_node->getAttribute( 'src' ) ) : fafar_custom_faculty_image( $xpath, $photo_node );
            }
            $current['people'][] = array(
                'name' => fafar_custom_faculty_text( $name_node ),
                'unit' => fafar_custom_faculty_text( $unit_node ),
                'email' => fafar_custom_faculty_text( $email_node ),
                'photo' => $photo,
                'lattes' => $lattes,
                'bio' => fafar_custom_faculty_text( $bio_node ),
                'profiles' => array(),
            );
        }
    }
    if ( $current && $current['people'] ) { $groups[] = $current; }
    return $groups;
}

function fafar_custom_faculty_ppgca_groups()
{
    $source = fafar_custom_faculty_source( 'corpo-docente-ppgca' );
    if ( ! $source ) { return array(); }
    list( $document, $xpath ) = $source;
    $groups = array();
    foreach ( $xpath->query( '//table[1]//tr' ) as $row ) {
        $cells = $xpath->query( './td', $row );
        $headers = $xpath->query( './th', $row );
        if ( $headers && $headers->length ) {
            if ( $groups && empty( $groups[ count( $groups ) - 1 ]['people'] ) ) { array_pop( $groups ); }
            $groups[] = array( 'title' => fafar_custom_faculty_text( $headers->item( 0 ) ), 'people' => array() );
            continue;
        }
        if ( ! $cells || $cells->length < 8 ) { continue; }
        if ( ! $groups ) { $groups[] = array( 'title' => 'Docentes', 'people' => array() ); }
        $profiles = array();
        foreach ( array( 3 => 'Lattes', 4 => 'ORCID', 5 => 'ResearchID', 6 => 'Google Acadêmico' ) as $index => $label ) {
            $href = fafar_custom_faculty_link( $xpath, $cells->item( $index ) );
            if ( $href ) { $profiles[] = array( 'label' => $label, 'url' => $href ); }
        }
        $groups[ count( $groups ) - 1 ]['people'][] = array(
            'name' => fafar_custom_faculty_text( $cells->item( 0 ) ),
            'unit' => fafar_custom_faculty_text( $cells->item( 1 ) ),
            'email' => fafar_custom_faculty_text( $cells->item( 2 ) ),
            'photo' => '',
            'lattes' => '',
            'bio' => '',
            'profiles' => $profiles,
            'ramal' => fafar_custom_faculty_text( $cells->item( 7 ) ),
        );
    }
    return array_values( array_filter( $groups, function ( $group ) { return ! empty( $group['people'] ); } ) );
}

function fafar_custom_faculty_render( $groups, $intro, $show_bio = false )
{
    if ( ! $groups ) { echo fafar_dynamic_notice( 'Os dados do corpo docente estão temporariamente indisponíveis.' ); return; }
    echo '<div class="fafar-custom-faculty"><p class="dynamic-intro">' . esc_html( $intro ) . '</p>';
    foreach ( $groups as $index => $group ) {
        echo '<section class="fafar-custom-faculty-group"><h2>' . esc_html( $group['title'] ) . '</h2><div class="fafar-custom-faculty-grid">';
        foreach ( $group['people'] as $person ) {
            $name = $person['name'];
            $parts = preg_split( '/\s+/u', trim( $name ) );
            $first = $parts[0] ?? '';
            $last = end( $parts ) ?: '';
            $initials = strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $first, 0, 1 ) . mb_substr( $last, 0, 1 ) : substr( $first, 0, 1 ) . substr( $last, 0, 1 ) );
            echo '<article class="fafar-custom-faculty-card"><div class="fafar-custom-faculty-header">';
            if ( $person['photo'] ) {
                echo '<div class="fafar-custom-faculty-photo-wrap"><img class="fafar-custom-faculty-photo" src="' . esc_url( $person['photo'] ) . '" alt="Fotografia de ' . esc_attr( $name ) . '" loading="lazy" decoding="async"></div>';
            } else {
                echo '<div class="fafar-custom-faculty-photo-wrap fafar-custom-faculty-placeholder" role="img" aria-label="Fotografia não informada para ' . esc_attr( $name ) . '"><span>' . esc_html( $initials ) . '</span></div>';
            }
            echo '<div class="fafar-custom-faculty-info"><h3>' . esc_html( $name ) . '</h3><p class="fafar-custom-faculty-unit">' . esc_html( $person['unit'] ) . '</p>';
            $email = sanitize_email( $person['email'] );
            if ( $email ) { echo '<p><a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>'; }
            if ( $person['lattes'] ) { echo '<p><a href="' . esc_url( $person['lattes'] ) . '" target="_blank" rel="noopener noreferrer">Currículo Lattes ↗</a></p>'; }
            if ( ! empty( $person['profiles'] ) ) {
                echo '<ul class="fafar-custom-faculty-profiles">';
                foreach ( $person['profiles'] as $profile ) { echo '<li><a href="' . esc_url( $profile['url'] ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $profile['label'] ) . '</a></li>'; }
                echo '</ul>';
            }
            if ( ! empty( $person['ramal'] ) && '---' !== $person['ramal'] ) { echo '<p>Ramal: ' . esc_html( $person['ramal'] ) . '</p>'; }
            echo '</div></div>';
            if ( $show_bio && $person['bio'] ) { echo '<div class="fafar-custom-faculty-bio"><h4>Biografia</h4><p>' . esc_html( $person['bio'] ) . '</p></div>'; }
            echo '</article>';
        }
        echo '</div></section>';
    }
    echo '</div>';
}

function corpo_docente_ppgact_content_handler()
{
    fafar_custom_faculty_render( fafar_custom_faculty_ppgact_groups(), 'Conheça os docentes vinculados ao Programa de Pós-Graduação em Análises Clínicas e Toxicológicas da UFMG.', true );
}

function corpo_docente_fafar_directory_content_handler()
{
    fafar_custom_faculty_render( fafar_custom_faculty_directory_groups(), 'Conheça os docentes da Faculdade de Farmácia da UFMG.' );
}

function corpo_docente_ppgca_content_handler()
{
    fafar_custom_faculty_render( fafar_custom_faculty_ppgca_groups(), 'Conheça os docentes do Programa de Pós-Graduação em Ciência de Alimentos da UFMG.' );
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
