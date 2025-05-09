<?php 
error_reporting(E_ALL);
ini_set('display_errors', 0);
$root = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
if ( ! defined('ABSPATH') ) {
        /** Set up WordPress environment */
        require_once( $root . '/wp-load.php' );
}

global $wpdb;

if ($_POST['form_action'] === 'form_survey') {
    header('Content-Type: application/json');

    if (!check_ajax_referer('form_survey', 'wpnonce', false)) {
        echo json_encode(['success' => false, 'message' => 'Phiên làm việc của bạn đã hết hạn. Vui lòng tải lại trang và thử lại.']);
        exit;
    }

    $survey_id = isset($_POST['survey_id']) ? sanitize_text_field($_POST['survey_id']) : '';
    $data = isset($_POST['data']) ? stripslashes($_POST['data']) : '';

    if (empty($survey_id) || empty($data)) {
        echo json_encode(['success' => false, 'message' => 'Dữ liệu đầu vào không hợp lệ']);
        exit;
    }

    // error_log('IVF Survey Input - Survey ID: ' . $survey_id . ', Data: ' . $data);

    $survey_data = json_decode($data, true);

    // error_log('IVF Survey Input - Survey ID: ' . $survey_id . ', Data: ' . json_encode($survey_data));

    if (json_last_error() !== JSON_ERROR_NONE) {
        $error_message = 'Dữ liệu JSON không hợp lệ: ' . json_last_error_msg();
        error_log('IVF Survey JSON Error: ' . $error_message . ', Raw Data: ' . $data);
        echo json_encode(['success' => false, 'message' => $error_message]);
        exit;
    }

    $sanitized_data = [
        'user_info' => !empty($survey_data['user_info']) ? [
            'fullname' => sanitize_text_field($survey_data['user_info']['fullname']),
            'gender' => sanitize_text_field($survey_data['user_info']['gender']),
            'datebirth' => sanitize_text_field($survey_data['user_info']['datebirth']),
            'phone' => sanitize_text_field(trim($survey_data['user_info']['phone']))
        ] : null,
        'q1' => !empty($survey_data['q1']) ? [
            'question' => sanitize_text_field($survey_data['q1']['question']),
            'answer' => sanitize_text_field($survey_data['q1']['answer']),
            'code' => sanitize_text_field($survey_data['q1']['code'] ?: '0')
        ] : null,

        'questions' => array_map(function($item) {
            return [
                'id_ques' => isset($item['id_ques']) ? sanitize_text_field($item['id_ques']) : '',
                'question' => sanitize_text_field($item['question']),
                'answer' => is_array($item['answer'])
                    ? array_map(function($ans) {
                        if (is_array($ans)) {
                            return [
                                'question' => sanitize_text_field($ans['question'] ?? ''),
                                'answer' => is_array($ans['answer'])
                                    ? array_map('sanitize_text_field', $ans['answer'])
                                    : sanitize_text_field($ans['answer'] ?? ''),
                            ];
                        } else {
                            return sanitize_text_field($ans);
                        }
                    }, $item['answer'])
                    : sanitize_text_field($item['answer']),
                // 'other' => isset($item['other']) ? sanitize_text_field($item['other']) : null,
                'other' => isset($item['other']) ? (is_array($item['other']) ? array_map('sanitize_text_field', $item['other']) : sanitize_text_field($item['other'])) : null,
                'code' => is_array($item['code'])
                    ? array_map('sanitize_text_field', $item['code'])
                    : sanitize_text_field($item['code'] ?? '0'),
            ];
        }, $survey_data['questions'] ?? []),


        'center_info' => [
            'province' => sanitize_text_field($survey_data['centerInfo']['province'] ?? ''),
            'province_value' => sanitize_text_field($survey_data['centerInfo']['province_value'] ?? ''),
            'center_name' => sanitize_text_field($survey_data['centerInfo']['center_name'] ?? ''),
            'center_code' => sanitize_text_field($survey_data['centerInfo']['center_code'] ?? ''),
            'you_are' => sanitize_text_field($survey_data['centerInfo']['you_are'] ?? '')
        ],
        'status' => [
            'current_question' => sanitize_text_field($survey_data['status']['current_question'] ?? ''),
            'section' => sanitize_text_field($survey_data['status']['section'] ?? ''),
            'state' => sanitize_text_field($survey_data['status']['state'] ?? '')
        ]
    ];

    // error_log('IVF Survey Sanitized Data: ' . json_encode($sanitized_data, JSON_UNESCAPED_UNICODE));

    global $wpdb;
    $table_name = 'ldp_survey_responses';
    $wpdb->set_charset($wpdb->dbh, 'utf8mb4');
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if (!$table_exists) {
        error_log('IVF Survey Database Error: Table ' . $table_name . ' does not exist');
        echo json_encode(['success' => false, 'message' => 'Bảng database không tồn tại']);
        exit;
    }

    $ip_user = !empty($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : null;
    $user_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : null;

    $survey_data_json = json_encode([
        'q1' => $sanitized_data['q1'],
        'questions' => $sanitized_data['questions']
    ], JSON_UNESCAPED_UNICODE);
    if ($survey_data_json === false) {
        error_log('IVF Survey Database Error: Failed to encode sanitized data to JSON');
        echo json_encode(['success' => false, 'message' => 'Lỗi khi mã hóa dữ liệu để lưu']);
        exit;
    }

    // error_log('IVF Survey Data to Save: ' . $sanitized_data['questions']);

    $province = $sanitized_data['center_info']['province'] ?? null;
    $province_value = $sanitized_data['center_info']['province_value'] ?? null;
    $center_name = $sanitized_data['center_info']['center_name'] ?? null;
    $center_code = $sanitized_data['center_info']['center_code'] ?? null;
    $you_are = $sanitized_data['center_info']['you_are'] ?? null;

    $current_question = $sanitized_data['status']['current_question'] ?? null;
    $section = $sanitized_data['status']['section'] ?? null;
    $state = $sanitized_data['status']['state'] ?? null;

    // Xử lý voucher và status
    $voucher = isset($survey_data['voucher']) ? sanitize_text_field($survey_data['voucher']) : '';
    $json_voucher = isset($survey_data['json_voucher']) ? $survey_data['json_voucher'] : '';
    $complete = isset($survey_data['is_complete']) && $survey_data['is_complete'] ? 1 : 0;

    // error_log('IVF ID: ' .$sanitized_data['questions']['id_ques']);
    if (($sanitized_data['status']['state'] ?? null) === 'done') {
        // Thực hiện gì đó khi $state là "done"
        // Khởi tạo mảng lưu trữ câu trả lời và câu hỏi
        $answers = [];
        $answers_with_questions = [];
        // Kiểm tra xem 'questions' có phải là mảng không
        if (is_array($sanitized_data['questions'])) {
            foreach ($sanitized_data['questions'] as $q) {
                $id = $q['id_ques'];
                $answers[$id] = [];
        
                if (is_array($q['answer'])) {
                    foreach ($q['answer'] as $sub_answer) {
                        if (is_array($sub_answer)) {
                            // Trường hợp answer dạng mảng có question và answer
                            $question = $sub_answer['question'] ?? '';
                            $ans_text = is_array($sub_answer['answer'])
                                ? implode(";", $sub_answer['answer'])
                                : $sub_answer['answer'];
                            $answers[$id][] = $question . ": ;" . $ans_text;
                        } else {
                            // Trường hợp answer chỉ là chuỗi
                            $answers[$id][] = $sub_answer;
                        }
                    }
                } else {
                    $answers[$id][] = $q['answer'];
                }
        
                // Gộp thành chuỗi cho dễ sử dụng
                $answers[$id] = implode(";", $answers[$id]);
        
                // Nếu có "other", thêm vào cuối
                if (!empty($q['other'])) {
                    $answers[$id] .= ";Khác: " . (is_array($q['other']) ? implode(', ', $q['other']) : $q['other']);
                }
        
                // Câu hỏi + câu trả lời đầy đủ
                $answers_with_questions[$id] = $q['question'] . ";" . $answers[$id];
            }
        }
        // Gộp nhiều câu trả lời vào một entry
        $gop_answers = implode(", ", [
            $answers_with_questions['d2-1'] ?? '',
            $answers_with_questions['d2-2'] ?? '',
            $answers_with_questions['d2-3'] ?? '',
            $answers_with_questions['d2-4'] ?? ''
        ]);
        $form_url = 'https://docs.google.com/forms/u/0/d/e/1FAIpQLSdCctzj2hOKPWfFkKmeKkMMEL0X-fM1ajDjdTxJC5InJn829w/formResponse';
        $param = [
            'entry.298469445' => $you_are,
            'entry.1520940990' => $province,
            'entry.558268237' => $center_name.' - '.$center_code,
            'entry.355467332' => $answers['s1'] ?? '',
            'entry.1616756185' => $answers['s2'] ?? '',
            'entry.608591481' => $answers['s3'] ?? '',
            'entry.469798261' => $answers['s4'] ?? '',
            'entry.2067231038' => $answers['a1'] ?? '',
            'entry.1227229471' => $answers['a2'] ?? '',
            'entry.1051804093' => $answers['a3'] ?? '',
            'entry.1180133593' => $answers['a4'] ?? '',
            'entry.1849148433' => $answers['a5'] ?? '',
            'entry.669774659' => $answers['a6'] ?? '',
            'entry.1066412337' => $answers['a7'] ?? '',
            'entry.1538031387' => $answers['b1'] ?? '',
            'entry.605855177' => $answers['b2'] ?? '',
            'entry.1511295581' => $answers['c1'] ?? '',
            'entry.500955898' => $answers['c2'] ?? '',
            'entry.1719593991' => $answers['c3'] ?? '',
            'entry.748853862' => $answers['c4'] ?? '',
            'entry.1405565924' => $answers['d1'] ?? '',
            'entry.920045270' => $gop_answers,
            'entry.2064499682' => $answers['e1'] ?? '',
            'entry.1208425335' => $answers['e2'] ?? '',
            'entry.1663311993' => $answers['e3'] ?? '',
            'entry.41849717' => $answers['e4'] ?? '',
            'entry.155335697' => $answers['e5'] ?? '',
            'entry.546035486' => $answers['e6'] ?? '',
            'entry.2024801145' => $answers['e7'] ?? '',
            'entry.1862672608' => $answers['f1'] ?? '',
            'entry.1195099776' => $answers['f2'] ?? '',
            'entry.1914791071' => $answers['f3'] ?? '',
            'entry.991409892' => $answers['f4'] ?? '',
            'entry.1458208022' => $answers['f5'] ?? '',
            'entry.1510979021' => $answers['f6'] ?? '',
            'entry.1843959477' => $answers['g1'] ?? '',
            'entry.1844099878' =>  $answers['g2'] ?? '',
			'entry.1915272945' =>  $survey_id ?? '',
        ];
        $ch = curl_init($form_url);        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);        
        curl_setopt($ch, CURLOPT_POST, count($param));        
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);            
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //error_log($result);
        $result = curl_exec($ch);
        curl_close($ch);      
    }

    $existing_response = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE survey_id = %s",
        $survey_id
    ));

    if ($existing_response) {
        $result = $wpdb->update(
            $table_name,
            [
                'fullname' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['fullname'] : $existing_response->fullname,
                'gender' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['gender'] : $existing_response->gender,
                'datebirth' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['datebirth'] : $existing_response->datebirth,
                'phone' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['phone'] : $existing_response->phone,
                'survey_data' => $survey_data_json,
                'voucher' => $voucher,
                'json_voucher' => $json_voucher,
                'complete' => $complete,
                'center_address' => $province,
                'address_value' => $province_value,
                'center_name' => $center_name,
                'center_code' => $center_code,
                'you_are' => $you_are,
                'current_question' => $current_question,
                'section' => $section,
                'status' => $state,
                'date_added' => current_time('mysql'),
                'ip_user' => $ip_user,
                'user_agent' => $user_agent
            ],
            ['survey_id' => $survey_id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'],
            ['%s']
        );

        if ($result === false) {
            error_log('IVF Survey Database Error (Update): ' . $wpdb->last_error);
            echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật dữ liệu: ' . $wpdb->last_error]);
            exit;
        }
    } else {
        $result = $wpdb->insert(
            $table_name,
            [
                'survey_id' => $survey_id,
                'fullname' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['fullname'] : null,
                'gender' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['gender'] : null,
                'datebirth' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['datebirth'] : null,
                'phone' => $sanitized_data['user_info'] ? $sanitized_data['user_info']['phone'] : null,
                'survey_data' => $survey_data_json,
                'voucher' => $voucher,
                'json_voucher' => $json_voucher,
                'complete' => $complete,
                'center_address' => $province,
                'center_name' => $center_name,
                'center_code' => $center_code,
                'current_question' => $current_question,
                'section' => $section,
                'status' => $state,
                'ip_user' => $ip_user,
                'date_added' => current_time('mysql'),
                'user_agent' => $user_agent
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($result === false) {
            error_log('IVF Survey Database Error (Insert): ' . $wpdb->last_error);
            echo json_encode(['success' => false, 'message' => 'Lỗi khi thêm dữ liệu: ' . $wpdb->last_error]);
            exit;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Dữ liệu đã được lưu thành công'
    ]);
    exit;
}
