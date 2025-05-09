<?php
/*
Template name: LDP - Khảo Sát
*/


// get_header(); 
// wp_enqueue_style('css_ldp_ivftahanhtrinh', get_template_directory_uri() . '/css/css_ldp_viftahanhtrinhcuanhungdieukydieu.css', false, rand(), 'all');
session_start();
$survey_id = session_id() ?: md5(time() . wp_rand(1000, 9999));
$list_khuvuc = get_field('danh_sach_vnvc_tinh_thanh') ?: [];
$list_question_part1 = get_field('list_question_dadieutri') ?: [];
$enable_back_button = get_field('enable_back_button') ?: true;
function generateQuestionHtml($list_question = [], $prefix = "part_", $input_name = "dieutri", $list_khuvuc = []) {
    $html = '';
    $index=0;

    $group1_ids = ['f3', 'f6'];
    $group2_ids = ['f4', 'f5'];
    $group1 = [];
    $group2 = [];
    $group1_indexes = [];
    $group2_indexes = [];
    
    // Gom nhóm và lưu vị trí gốc
    foreach ($list_question as $index => $item) {
        if (!isset($item['id_ques'])) continue;
        if (in_array($item['id_ques'], $group1_ids)) {
            $group1[] = $item;
            $group1_indexes[] = $index;
        } elseif (in_array($item['id_ques'], $group2_ids)) {
            $group2[] = $item;
            $group2_indexes[] = $index;
        }
    }
    	// echo count($group1);
		// echo count($group2);
		// echo rand(0, 20) % 2;	
    // Chỉ thực hiện khi đủ số lượng và đều có 2 phần tử
    if (rand(0, 20) % 2 === 0 && count($group1) === 2 && count($group2) === 2) {
        // Gán toàn bộ group2 vào vị trí cũ của group1
        foreach ($group1_indexes as $i => $index) {
            $list_question[$index] = $group2[$i];
        }
        // Gán toàn bộ group1 vào vị trí cũ của group2
        foreach ($group2_indexes as $i => $index) {
            $list_question[$index] = $group1[$i];
        }
    }
    
    foreach ($list_question as $key => $listquestion) {
        $custom_key = $prefix . $key;
        $is_hidden = in_array($listquestion['display'] ?? '', ['ques_child_show', 'ques_child_notshow']) ? 'hidden' : '';
        
        $html .= '<div id="' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" class="item_question mb_20 wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . ' ' . $is_hidden . '" data-index="' . esc_attr($key) . '">';
        
        if (($listquestion['question_type'] ?? '') === 'text_intro') {
            $html .= '<div class="intro_question">' . htmlspecialchars($listquestion['intro_text'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>';
        } else {
            $html .= '<div class="title_question">';
            if (!empty($listquestion['question_intro'])) {
                $html .= '<div class="title_question_intro font-roboto-bold cl_main">' . htmlspecialchars($listquestion['question_intro'], ENT_QUOTES, 'UTF-8') . '</div>';
            }
            // $html .= '<div class="title_question_name cl_main font-roboto-medium ">' . htmlspecialchars($listquestion['question_text'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>';
            $html .= '<div class="title_question_name cl_main font-roboto-medium ">' . ($listquestion['question_text'] ?? '') . '</div>';
            $html .= '<input data-id="' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" type="hidden" class="input_title_question_name" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][question]" value="' . htmlspecialchars($listquestion['question_text'] ?? '', ENT_QUOTES, 'UTF-8') . '">';
            $html .= '</div>';
        }

        if (($listquestion['question_type'] ?? '') === 'radio_random') {
            $answers = $listquestion['list_answer'] ?? [];
            // Tách câu trả lời 'show_input_other' ra riêng
            $others = [];
            $normal_answers = [];
            foreach ($answers as $ans) {
                if (($ans['answer_type'] ?? '') === 'show_input_other') {
                    $others[] = $ans;
                } else {
                    $normal_answers[] = $ans;
                }
            }
            // Random các câu trả lời thường
            shuffle($normal_answers);
            // Gộp lại, đảm bảo 'show_input_other' ở cuối
            $sorted_answers = array_merge($normal_answers, $others);
            foreach ($sorted_answers as $item) {
                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                $answer_type_class = ($item['answer_type'] ?? '') === 'show_input_other' ? 'show_input_other' : '';
                $stop = ($item['answer_type'] ?? '') === 'stop' ? 'stop' : '';

                $code = !empty($item['id_answer_child']) ? htmlspecialchars($item['id_answer_child'], ENT_QUOTES, 'UTF-8') : '0';
                $condition_data = !empty($item['code']) ? htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') : '0';

                $html .= '<div class="row_answer">';
                $html .= '<label>';
                $html .= '<input class="input_radio input_requice ' . $answer_type_class . $stop . ' r' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" type="radio" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '" data-condition="' . $condition_data . '">';
                $html .= '<span>' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
                $html .= '</label>';
                $html .= '</div>';
            }
        }
        if (($listquestion['question_type'] ?? '') === 'checkbox_random') {
            $answers = $listquestion['list_answer'] ?? [];
        
            // Tách riêng câu có input_text hoặc answer_type = show_input_other
            $others = [];
            $normal_answers = [];
        
            foreach ($answers as $ans) {
                if (!empty($ans['input_text']) || ($ans['answer_type'] ?? '') === 'show_input_other') {
                    $others[] = $ans;
                } else {
                    $normal_answers[] = $ans;
                }
            }
        
            // Random phần còn lại
            shuffle($normal_answers);
        
            // Gộp lại danh sách
            $sorted_answers = array_merge($normal_answers, $others);
        
            foreach ($sorted_answers as $item) {
                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                $answer_type_class = ($item['answer_type'] ?? '') === 'show_input_other' ? 'show_input_other' : '';
                $stop = ($item['answer_type'] ?? '') === 'stop' ? 'stop' : '';
                $code = !empty($item['id_answer_child']) ? htmlspecialchars($item['id_answer_child'], ENT_QUOTES, 'UTF-8') : '0';
                $condition_data = !empty($item['code']) ? htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') : '0';
        
                if (!empty($item['input_text'])) {
                    $input_id = 'input_' . htmlspecialchars($listquestion['id_ques'], ENT_QUOTES, 'UTF-8') . '_' . $key;
                    $checkbox_id = 'checkbox_' . htmlspecialchars($listquestion['id_ques'], ENT_QUOTES, 'UTF-8') . '_' . $key;
        
                    $html .= '<div class="row_answer">';
                    $html .= '<label>';
                    $html .= '<input class="input_checkbox input_requice with_text_input ' . $answer_type_class . $stop . ' r' . htmlspecialchars($listquestion["id_ques"], ENT_QUOTES, "UTF-8") . '"';
                    $html .= ' type="checkbox" id="' . $checkbox_id . '"';
                    $html .= ' name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer][]"';
                    $html .= ' value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '"';
                    $html .= ' data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '" data-condition="' . $condition_data . '">';
                    $html .= htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8');
                    $html .= ' <span>(ghi rõ ' . htmlspecialchars($item['input_text'], ENT_QUOTES, 'UTF-8') . '</span>';
                    $html .= '<input type="text" name="' . htmlspecialchars($item['input_name'], ENT_QUOTES, 'UTF-8') . '" id="' . $input_id . '" style="width: 250px; display: inline-block;">';
                    $html .= '<span>)</span>';
                    $html .= '</label>';
                    $html .= '</div>';
                } else {
                    $html .= '<div class="row_answer">';
                    $html .= '<label>';
                    $html .= '<input class="input_checkbox input_requice ' . $answer_type_class . $stop . ' r' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" type="checkbox" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer][]" value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '" data-condition="' . $condition_data . '">';
                    $html .= '<span>' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
                    $html .= '</label>';
                    $html .= '</div>';
                }
            }
        }
        if (($listquestion['question_type'] ?? '') === 'select_random') {
            $html .= '<div class="row_answer">';
            $html .= '<select class="input_select input_requice s' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" data-code="0">';
            $html .= '<option value="">Chọn giá trị</option>';
            $answers = $listquestion['list_answer'] ?? [];
            // Tách riêng các loại đặc biệt
            $special_answers = [];
            $normal_answers = [];
            foreach ($answers as $item) {
                if (($item['answer_type'] ?? '') === 'show_input_other') {
                    $special_answers[] = $item;
                } else {
                    $normal_answers[] = $item;
                }
            }
            // Random phần thường
            shuffle($normal_answers);
            // Gộp lại: phần thường trước, đặc biệt sau
            $sorted_answers = array_merge($normal_answers, $special_answers);
            foreach ($sorted_answers as $item) {
                $answer_type_class = ($item['answer_type'] ?? '') === 'show_input_other' ? 'show_input_other' : '';
                $stop = ($item['answer_type'] ?? '') === 'stop' ? 'stop' : '';
                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                $code = !empty($item['id_answer_child']) ? htmlspecialchars($item['id_answer_child'], ENT_QUOTES, 'UTF-8') : '0';
                $html .= '<option class="input_option ' . $answer_type_class . $stop . '" value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '">';
                $html .= htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }
        
        if (($listquestion['question_type'] ?? '') === 'radio') {
            foreach ($listquestion['list_answer'] ?? [] as $item) {
                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                $answer_type_class = ($item['answer_type'] ?? '') === 'show_input_other' ? 'show_input_other' : '';
                $stop = ($item['answer_type'] ?? '') === 'stop' ? 'stop' : '';


                $code = !empty($item['id_answer_child']) ? htmlspecialchars($item['id_answer_child'], ENT_QUOTES, 'UTF-8') : '0';

                $condition_data = !empty($item['code']) ? htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') : '0';
                
                $html .= '<div class="row_answer">';
                $html .= '<label>';
                $html .= '<input class="input_radio input_requice ' . $answer_type_class . $stop . ' r' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" type="radio" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '" data-condition="' . $condition_data . '">';
                $html .= '<span>' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
                $html .= '</label>';
                $html .= '</div>';
            }
        }        
        
        if (($listquestion['question_type'] ?? '') === 'checkbox') {
            foreach ($listquestion['list_answer'] ?? [] as $item) {
                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                $answer_type_class = ($item['answer_type'] ?? '') === 'show_input_other' ? 'show_input_other' : '';
                $stop = ($item['answer_type'] ?? '') === 'stop' ? 'stop' : '';
                $code = !empty($item['id_answer_child']) ? htmlspecialchars($item['id_answer_child'], ENT_QUOTES, 'UTF-8') : '0';
                
                $condition_data = !empty($item['code']) ? htmlspecialchars($item['code'], ENT_QUOTES, 'UTF-8') : '0';

                if (!empty($item['input_text'])) {
                    $input_id = 'input_' . htmlspecialchars($listquestion['id_ques'], ENT_QUOTES, 'UTF-8') . '_' . $key;
                    $checkbox_id = 'checkbox_' . htmlspecialchars($listquestion['id_ques'], ENT_QUOTES, 'UTF-8') . '_' . $key;
                    
                    $html .= '<div class="row_answer">';
                    $html .= '<label>';
                    $html .= '<input class="input_checkbox input_requice with_text_input ' . $answer_type_class . $stop . ' r' . htmlspecialchars($listquestion["id_ques"], ENT_QUOTES, "UTF-8") . '"';
                    $html .= ' type="checkbox" id="' . $checkbox_id . '"';
                    $html .= ' name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer][]"';
                    $html .= ' value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '"';
                    $html .= ' data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '" data-condition="' . $condition_data . '">';
                    $html .= htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8');
                    $html .= ' <span>(ghi rõ ' . htmlspecialchars($item['input_text'], ENT_QUOTES, 'UTF-8') . '</span>';
                    $html .= '<input type="text" name="' . htmlspecialchars($item['input_text'], ENT_QUOTES, 'UTF-8') . '" id="' . $input_id . '" style="width: 250px; display: inline-block;">';
                    $html .= '<span>)</span>';
                    $html .= '</label>';
                    $html .= '</div>';
                    
                }else{
                    $html .= '<div class="row_answer">';
                    $html .= '<label>';
                    $html .= '<input class="input_checkbox input_requice with_text_input' . $answer_type_class . $stop . ' r' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" type="checkbox" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer][]" value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '" data-condition="' . $condition_data . '">';
                    $html .= '<span>' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '</span>';
                    $html .= '</label>';
                    $html .= '</div>';
                }

            }
        }

        if (($listquestion['question_type'] ?? '') === 'select') {
            $html .= '<div class="row_answer">';
            $html .= '<select class="input_select input_requice s' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" data-code="0">';
            $html .= '<option value="">Chọn giá trị</option>';
            foreach ($listquestion['list_answer'] ?? [] as $item) {
                $answer_type_class = ($item['answer_type'] ?? '') === 'show_input_other' ? 'show_input_other' : '';
                $stop = ($item['answer_type'] ?? '') === 'stop' ? 'stop' : '';
                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                $code = !empty($item['id_answer_child']) ? htmlspecialchars($item['id_answer_child'], ENT_QUOTES, 'UTF-8') : '0';
                $html .= '<option class="input_option ' . $answer_type_class . $stop . '" value="' . htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8') . '" data-value=\'' . htmlspecialchars($arr, ENT_QUOTES, 'UTF-8') . '\' data-code="' . $code . '">';
                $html .= htmlspecialchars($item['answer_text'] ?? '', ENT_QUOTES, 'UTF-8');
                $html .= '</option>';
            }
        
            $html .= '</select>';
            $html .= '</div>';
        }
        
        if (($listquestion['question_type'] ?? '') === 'text') {
            $html .= '<div class="row_answer">';
            $html .= '<input type="text" class="input_text input_requice" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" data-code="0">';
            $html .= '</div>';
        }
        
        if (($listquestion['question_type'] ?? '') === 'number') {
            $html .= '<div class="row_answer">';
            $html .= '<input type="number" class="input_text input_requice" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" data-code="0">';
            $html .= '</div>';
        }
        
        if (($listquestion['question_type'] ?? '') === 'textarea') {
            $html .= '<div class="row_answer">';
            $html .= '<textarea class="input_textarea input_requice" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" data-code="0"></textarea>';
            $html .= '</div>';
        }
        
        if (($listquestion['question_type'] ?? '') === 'date') {
            $html .= '<div class="row_answer">';
            $html .= '<input type="text" class="input_text input_date input_requice" name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]" value="" data-code="0">';
            $html .= '</div>';
        }

        if (($listquestion['question_type'] ?? '') === 'likert') {
            $html .= '<div class="rating-table">';
            $html .= '<table>';
                $html .= '        <thead>';
                $html .= '            <tr>';
                if($listquestion['table_first_heading'] && isset($listquestion['table_first_heading'])){
                    $html .= '<th>'.$listquestion['table_first_heading'].'</th>';
                }else{
                    $html .= '<th></th>';
                }
                if($listquestion['chonnhieudapan']){
                    $type = "checkbox";
                }else{
                    $type = "radio";
                }
                foreach ($listquestion['rating_heading'] ?? [] as $item) {
                    $html .= '<th>'.$item['heading_title'].'</th>';
                }
                $html .= '            </tr>';
                $html .= '        </thead>';

                $html .= '        <tbody>';
                $stt = 1;
                
                // Kiểm tra và random mảng câu hỏi nếu tồn tại
                if (isset($listquestion['rating_questions'])) {
                    $randomQuestions = $listquestion['rating_questions'];
                    if ($type === "radio") {
                        shuffle($randomQuestions); // Xáo trộn mảng câu hỏi
                    }
                    foreach ($randomQuestions as $item) {
                        $html .= '<tr>';
                        $html .= '<td class="rating-question">'.$item['question_text'].'</td>';
                        
                        // Kiểm tra và random mảng heading nếu tồn tại
                        if (isset($listquestion['rating_heading'])) {
                            $randomHeadings = $listquestion['rating_heading'];
                            // if ($type === "radio") {
                            //     shuffle($randomHeadings); // Xáo trộn mảng heading
                            // }
                            
                            foreach ($randomHeadings as $headingItem) {
                                $html .= '<td data-label="'.$headingItem['heading_title'].'" class="rating-option"><input type="'.$type.'"  name="' . htmlspecialchars($input_name, ENT_QUOTES, 'UTF-8') . '[' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '][answer]['.$stt.']" value="'.$headingItem['heading_title'].'"></td>';
                            }
                        }
                        
                        $html .= '</tr>';
                        $stt++;
                    }
                }
                
                $html .= '        </tbody>';

            $html .= '    </table>';
            $html .= '</div>';
        }

        if (($listquestion['question_type'] ?? '') === 'show_select_tinhthanh' || ($listquestion['question_type'] ?? '') === 'show_select_trungtam') {
            // Lấy dữ liệu từ ACF
            $danh_sach_vnvc_tinh_thanh = get_field('danh_sach_vnvc_tinh_thanh');
            $data = [];
            if ($danh_sach_vnvc_tinh_thanh) {
                foreach ($danh_sach_vnvc_tinh_thanh as $row) {
                    $province = strtoupper(trim($row['tinh_thanh']));
                    $province_value = strtoupper(trim($row['value']));
                    
                    $center = trim($row['trung_tam']);
                    $center_code = trim($row['ma_trung_tam']);
        
                    if (!isset($data[$province])) {
                        $data[$province] = [];
                    }
                    $data[$province][] = [
                        'name' => $center,
                        'code' => $center_code,
                    ];
                }
            }
        }
        
        if (($listquestion['question_type'] ?? '') === 'show_select_tinhthanh') {
            $html .= '<div class="form-group">';
            // $html .= '<select id="provinceSelect_'.$index.'" class="input_select input_requice">';
            $html .= '<select class="input_select input_requice province-select" data-index="'.$index.'">';
            $html .= '<option value="">Chọn tỉnh/thành</option>';
            foreach ($data as $province => $centers) {
                $html .= '<option value="' . esc_attr($province_value) . '">' . esc_html($province) . '</option>';
            }
            $html .= '</select>';
            $html .= '</div>';
        }
        
        if (($listquestion['question_type'] ?? '') === 'show_select_trungtam') {
            $html .= '<div class="form-group">';
            // $html .= '<select id="vnvcSelect_'.$index.'" class="input_select input_requice">';
            $html .= '<select class="input_select input_requice vnvc-select" data-index="'.$index.'">';
            $html .= '<option value="">Chọn trung tâm VNVC</option>';
            $html .= '</select>';
            $html .= '</div>';
        
            $html .= '<script>';
            $html .= 'document.addEventListener("DOMContentLoaded", function () {';
            $html .= 'const vnvcData = ' . json_encode($data, JSON_UNESCAPED_UNICODE) . ';';

            // $html .= 'const provinceSelect = document.getElementById("provinceSelect_'.$index.'");';
            // $html .= 'const vnvcSelect = document.getElementById("vnvcSelect_'.$index.'");';

            $html .= 'const provinceSelect = document.querySelector(".province-select[data-index=\"'.$index.'\"]");';
            $html .= 'const vnvcSelect = document.querySelector(".vnvc-select[data-index=\"'.$index.'\"]");';
        
            $html .= 'provinceSelect.addEventListener("change", function () {';
            $html .= 'const selectedProvinceValue = this.value;';
            $html .= 'const selectedProvince = this.options[this.selectedIndex].text;';
            $html .= 'vnvcSelect.innerHTML = \'<option value="">Chọn trung tâm VNVC</option>\';';
            $html .= 'if (vnvcData[selectedProvince]) {';
            $html .= 'vnvcData[selectedProvince].forEach(function (center) {';
            $html .= 'const option = document.createElement("option");';
            $html .= 'option.value = center.code;';
            $html .= 'option.textContent = center.name;';
            $html .= 'vnvcSelect.appendChild(option);';
            $html .= '});';
            $html .= '}';
            $html .= '});';
        
            $html .= 'vnvcSelect.addEventListener("click", function () {';
            $html .= 'if (!provinceSelect.value) {';
            $html .= 'alert("Vui lòng chọn Tỉnh/Thành trước khi chọn Trung tâm VNVC.");';
            $html .= '}';
            $html .= '});';
            $html .= '});';
            $html .= '</script>';
            $index++;
        }
        if (($listquestion['question_type'] ?? '') === 'show_select_bvpktmv') {
            // Lấy dữ liệu từng nhóm
            $danh_sach_list_benhvien = get_field('list_benhvien') ?: [];
            $danh_sach_list_phong_kham = get_field('list_phong_kham') ?: [];
            $danh_sach_list_tham_my_vien = get_field('list_tham_my_vien') ?: [];
        
            // Tạo mảng các nhóm
            $groups = [
                ['title' => 'Bệnh viện', 'data' => $danh_sach_list_benhvien],
                ['title' => 'Phòng khám', 'data' => $danh_sach_list_phong_kham],
                ['title' => 'Thẩm mỹ viện', 'data' => $danh_sach_list_tham_my_vien],
            ];
        
            // Thêm CSS cho group_title và hospital_list
            $html .= '<style>
                .hospital_group .group_title {
                    cursor: pointer;
                    padding: 10px;
                    background: #f5f5f5;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    margin-bottom: 5px;
                    position: relative;
                }
                .hospital_group .group_title:hover {
                    background: #e9e9e9;
                }
                .hospital_group .group_title:after {
                    content: "▼";
                    position: absolute;
                    right: 10px;
                    transition: transform 0.3s;
                }
                .hospital_group.active .group_title:after {
                    transform: rotate(180deg);
                }
                .hospital_group .hospital_list {
                    display: none;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    margin-top: -5px;
                }
                .hospital_group.active .hospital_list {
                    display: block;
                }
            </style>';

            $html .= '<div class="question_hospital_group">';
            $first = true;
            foreach ($groups as $group) {
                if (!empty($group['data'])) {
                    foreach ($group['data'] as $row) {
                        $type_hospital = trim($row['type_hospital'] ?? $group['title']);
                        $list_hospital = $row['list_hospital'] ?? [];
                        if (!empty($list_hospital)) {
                            $active_class = $first ? ' active' : '';
                            $html .= '<div class="hospital_group ' . $active_class . '">';
                            $html .= '<div class="group_title"><strong>' . esc_html($type_hospital) . '</strong></div>';
                            // Tách items thành 2 mảng: normal và other
                            $first = false;
                            $normal_items = [];
                            $other_items = [];
                            foreach ($list_hospital as $item) {
                                if (!empty($item['name']) && !empty($item['value'])) {
                                    if (($item['answer_type'] ?? '') === 'show_input_other') {
                                        $other_items[] = $item;
                                    } else {
                                        $normal_items[] = $item;
                                    }
                                }
                            }
                            // Random chỉ các items bình thường
                            shuffle($normal_items);
                            // Wrap tất cả items trong một div hospital_list
                            $html .= '<div class="hospital_list">';
                            // Hiển thị các items bình thường đã random
                            foreach ($normal_items as $item) {
                                $name = $item['name'] ?? '';
                                // $value = $item['value'] ?? '';
                                $code = $item['code'] ?? '0';
                                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                                
                                $html .= '<div class="hospital_item">';
                                $html .= '<div class="row_answer">';
                                $html .= '<label>';
                                $html .= '<input class="input_checkbox input_requice r' . esc_attr($listquestion['id_ques'] ?? '') . '" type="checkbox" name="' . esc_attr($input_name) . '[' . esc_attr($key) . '][answer][]" value="' . esc_attr($name) . '" data-value=\'' . esc_attr($arr) . '\' data-code="' . esc_attr($code) . '"  data-condition="' . esc_attr($code) . '">';
                                $html .= '<span>' . esc_html($name) . '</span>';
                                $html .= '</label>';
                                $html .= '</div>';
                                $html .= '</div>';
                            }
                            // Hiển thị các items "other" ở cuối
                            foreach ($other_items as $item) {
                                $name = $item['name'] ?? '';
                                $value = $item['value'] ?? '';
                                $code = $item['code'] ?? '0';
                                $arr = json_encode(explode(",", $item['id_answer_child'] ?? ''), JSON_UNESCAPED_UNICODE);
                                
                                $html .= '<div class="hospital_item">';
                                $html .= '<div class="row_answer">';
                                $html .= '<label>';
                                $html .= '<input class="input_checkbox input_requice show_input_other r' . esc_attr($listquestion['id_ques'] ?? '') . '" type="checkbox" name="' . esc_attr($input_name) . '[' . esc_attr($key) . '][answer][]" value="' . esc_attr($name) . '" data-value=\'' . esc_attr($arr) . '\' data-code="' . esc_attr($code) . '" data-condition="' . esc_attr($code) . '">';
                                $html .= '<span>' . esc_html($name) . '</span>';
                                $html .= '</label>';
                                $html .= '</div>';
                                $html .= '</div>';
                            }
                            
                            $html .= '</div>'; // .hospital_list
                            $html .= '</div>'; // .hospital_group
                        }
                    }
                }
            }
            $html .= '</div>'; // .question_hospital_group

            // Thêm JavaScript để xử lý sự kiện click
            $html .= '<script>
            jQuery(document).ready(function() {
                jQuery(".hospital_group .group_title").click(function() {
                    const $group = jQuery(this).closest(".hospital_group");
                    
                    // Đóng tất cả các nhóm khác
                    // jQuery(".hospital_group").not($group).removeClass("active");
                    
                    // Toggle nhóm hiện tại
                    $group.toggleClass("active");
                });
            });
            </script>';
        }
        if (in_array($listquestion['display'] ?? '', ['ques_child_show', 'ques_child_notshow'])) {
            $html .= '<script>';
            $html .= 'jQuery(document).ready(function () {';
            
            // if (($listquestion['display'] ?? '') === 'ques_child_show') {
            //     $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques_parend'] ?? '', ENT_QUOTES, 'UTF-8') . ' .r' . htmlspecialchars($listquestion['id_ques_parend'] ?? '', ENT_QUOTES, 'UTF-8') . '.input_radio\').on(\'click\', function() {';
            //     $html .= 'const $attr' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . '=jQuery(this).data(\'value\') || [];';
            //     $html .= 'if($attr' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . '.includes("' . htmlspecialchars($listquestion['value_select'] ?? '', ENT_QUOTES, 'UTF-8') . '")){';
            //     $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '\').removeClass(\'hidden\');';
            //     $html .= '} else {';
            //     $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '\').addClass(\'hidden\');';
            //     $html .= '}';
            //     $html .= '});';
            // }

            if (($listquestion['display'] ?? '') === 'ques_child_show') {
                $parentId = htmlspecialchars($listquestion['id_ques_parend'] ?? '', ENT_QUOTES, 'UTF-8');
                $childId = htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8');
                $customKey = htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8');
                $valueSelect = htmlspecialchars($listquestion['value_select'] ?? '', ENT_QUOTES, 'UTF-8');
            
                $html .= 'jQuery(\'.wrap_item' . $parentId . ' .r' . $parentId . '.input_requice\').on(\'click\', function() {';
                $html .= 'let dataVal = jQuery(this).data(\'value\') || \'\';';
                $html .= 'let values = Array.isArray(dataVal) ? dataVal : (typeof dataVal === \'string\' ? dataVal.split(\',\') : []);';
                $html .= 'values = values.map(v => v.trim());';
                $html .= 'if (values.includes(\'' . $valueSelect . '\')) {';
                $html .= 'jQuery(\'.wrap_item' . $childId . '\').removeClass(\'hidden\');';
                $html .= '} else {';
                $html .= 'jQuery(\'.wrap_item' . $childId . '\').addClass(\'hidden\');';
                $html .= '}';
                $html .= '});';
            }
            
            
            
            if (($listquestion['display'] ?? '') === 'ques_child_notshow') {
                $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques_parend'] ?? '', ENT_QUOTES, 'UTF-8') . ' .s' . htmlspecialchars($listquestion['id_ques_parend'] ?? '', ENT_QUOTES, 'UTF-8') . '.input_select\').on(\'change\', function() {';
                $html .= 'const $attr' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . '=jQuery(this).find("option:selected").attr(\'data-value\') || "[]";';
                $html .= 'const attrArray = Array.isArray(JSON.parse($attr' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ')) ? JSON.parse($attr' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ') : [];';
                $html .= 'if(!attrArray.includes("' . htmlspecialchars($listquestion['value_select'] ?? '', ENT_QUOTES, 'UTF-8') . '")){';
                $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '\').removeClass(\'hidden\');';
                $html .= '} else {';
                $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . '\').addClass(\'hidden\');';
                $html .= '}';
                
                if (($listquestion['title_question'] ?? '') === 'insert') {
                    $html .= 'const $value' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . '=jQuery.trim(jQuery(this).val());';
                    $html .= 'const $title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ' = ' . json_encode($listquestion['question_text'] ?? '', JSON_UNESCAPED_UNICODE) . '.replace(\'<span class="title_insert"></span>\', $value' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ');';
                    $html .= 'if($attr' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ' != \'other\'){';
                    $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . ' .title_question_name\').text(typeof sanitizeInput === \'function\' ? sanitizeInput($title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ') : $title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ');';
                    $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . ' .input_title_question_name\').val(typeof sanitizeInput === \'function\' ? sanitizeInput($title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ') : $title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ');';
                    $html .= '}';
                }
                
                $html .= '});';
                
                $html .= 'jQuery(document).on(\'blur\', \'.wrap_item' . htmlspecialchars($listquestion['id_ques_parend'] ?? '', ENT_QUOTES, 'UTF-8') . ' .input_select_text_other\', function() {';
                $html .= 'const $textother' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ' = jQuery.trim(jQuery(this).val());';
                $html .= 'const $title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ' = ' . json_encode($listquestion['question_text'] ?? '', JSON_UNESCAPED_UNICODE) . '.replace(\'<span class="title_insert"></span>\', $textother' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ');';
                $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . ' .title_question_name\').text(typeof sanitizeInput === \'function\' ? sanitizeInput($title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ') : $title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ');';
                $html .= 'jQuery(\'.wrap_item' . htmlspecialchars($listquestion['id_ques'] ?? '', ENT_QUOTES, 'UTF-8') . ' .input_title_question_name\').val(typeof sanitizeInput === \'function\' ? sanitizeInput($title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ') : $title' . htmlspecialchars($custom_key, ENT_QUOTES, 'UTF-8') . ');';
                $html .= '});';
            }
            
            $html .= '});';
            $html .= '</script>';
        }
        
        $html .= '</div>';
    }
    
    return $html;
}
?>
<?php global $post;  ?>
<?php include( locate_template( 'templates/page-layout.php' ) ); ?>

<div class="inner-container">
	<?php include( locate_template( 'templates/page-header.php' ) ); // Page Header Template ?>
<style>
    .ldp-khaosat { padding: 20px 0; }
    .container { max-width: 800px; margin: 0 auto; }
    .title_headding_page { font-size: 24px; font-weight: bold; text-align: center; margin-bottom: 20px; }
    .section { display: none; }
    .section.active { display: block; }
    .item_question { display: none; }
    #part1 .item_question { display: block; }
    .item_question.active { display: block; }
    .hidden { display: none !important; }
    .mb_20 { margin-bottom: 20px; }
    .title_question_name { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
    .title_question_intro { font-size: 16px; margin-bottom: 10px; }
    .intro_question, .intro_part_dieutri { margin-bottom: 20px; font-size: 16px; }
    .row_answer { margin: 10px 0; }
    .row_answer label { display: flex; align-items: center; gap: 10px; }
    .input_text, .input_select, textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    .input_date { width: 200px; }
    textarea { height: 100px; }
    .wrap_button { margin-top: 20px; display: flex; gap: 10px; }
    .wrap_button button { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
    .next_button { background-color: #007bff; color: white; }
    .back_button { background-color: #6c757d; color: white; }
    .error { color: red; margin-top: 10px; }
    .input_other { margin-top: 10px; width: 100%; }
    .elle-futura-bold {
        font-family: "Elle Futura", sans-serif;
        font-weight: 700;
        font-style: normal;
    }
    select.error {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220,53,69,0.15);
    }
    input.input_radio.error[type="radio"] {
        appearance: none;
        -webkit-appearance: none;
        background-color: #fff;
        border: 2px solid red;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        cursor: pointer;
        position: relative;
    }

    input.input_radio.error[type="radio"]:checked::before {
        content: '';
        position: absolute;
        top: 4px;
        left: 4px;
        width: 10px;
        height: 10px;
        background-color: red;
        border-radius: 50%;
    }

</style>

<div class="survey-container">
<div class="progress-container">
    <div class="progress-bar" id="progress-bar"></div>
</div>

<div class="intro_before text-center fz_16 mb_30 cl_main"><?php echo get_field('intro_before') ?: [];; ?></div>
    <div id="part1" class="section section_part part1">
        <?php
        $prefix1 = "part1_";
        $input_name_part1 = "mophongkham";
        echo generateQuestionHtml($list_question_part1, $prefix1, $input_name_part1, $list_khuvuc);
        ?>
        <div class="wrap_button hidden">
            <button type="button" class="back_button hidden" onclick="prevQuestion()">Quay lại</button>
            <button type="button" class="next_button" onclick="nextQuestion(currentQuestionIndex)">Tiếp theo</button>
        </div>
    </div>
</div>
<div id="stop-message" style="display:none; color:red; font-weight:bold; text-align:center; margin:30px 0;">
    <?php
    $intro_end = get_field('intro_end');
    if($intro_end){
        echo $intro_end;
    }
    ?>
</div>
<div style="display: none;" id="thankYouMessage">
<?php
    $intro_final = get_field('intro_final');
    if($intro_final){
        echo $intro_final;
    }
    ?>
</div>

<div id="customAlert" class="custom-alert" style="display: none;">
  <div class="custom-alert-box">
    <div id="customAlertContent" class="custom-alert-content text-center"></div>
    <div class="text-center"><button class="custom-alert-close" onclick="document.getElementById('customAlert').style.display='none'">Đóng</button></div>
  </div>
</div>


<script>

document.addEventListener('DOMContentLoaded', function() {
    showSection('part1');
});

let currentQuestionIndex = 0;
let questionHistory = [];
let answeredQuestions = {};
let surveyData = {
    survey_id: '<?php echo esc_js($survey_id); ?>',
    q1: null,
    questions: []
};
let section_id = '';
let listQuestions = [];
const enableBackButton = <?php echo json_encode($enable_back_button); ?>;
const sectionQuestions = {
    'part1': <?php echo json_encode($list_question_part1, JSON_UNESCAPED_UNICODE); ?>,
};

function sanitizeInput(input) {
    if (typeof input !== 'string') return input;
    return input.replace(/[<>"'%;()&\n\r\t]/g, '').replace(/\s+/g, ' ').trim();
}

function showSection(sectionId, resetHistory = true) {
    jQuery('.section').removeClass('active').hide();
    jQuery('#' + sectionId).addClass('active').show();
    if (sectionId !== 'q1' && sectionId !== 'final' && sectionId !== 'end' && sectionId !== 'intro') {
        section_id = sectionId;
        listQuestions = sectionQuestions[sectionId] || [];
        if (resetHistory) {
            currentQuestionIndex = 0;
            questionHistory = [];
            showQuestion(currentQuestionIndex);
        }
    }
}

function showQuestion(index) {
    if (index >= listQuestions.length) {
        // Cập nhật trạng thái hoàn thành
        surveyData.status = {
            current_question: currentQuestionIndex,
            section: section_id,
            state: 'done'
        };
        sendSurveyData();
        showSection('final');
        jQuery('#thankYouMessage').show();
        return;
    }
    jQuery(`#${section_id} .item_question`).hide().removeClass('active');
    jQuery(`#${section_id} .item_question[data-index="${index}"]`).show().addClass('active');
    if (enableBackButton && index > 0) {
        jQuery(`#${section_id} .back_button`).show();
    } else {
        jQuery(`#${section_id} .back_button`).hide();
        jQuery(".intro_before").show();
    }
    jQuery(`#${section_id} .wrap_button`).removeClass('hidden');

    const prevAnswer = answeredQuestions[`${section_id}_${index}`];
    if (prevAnswer) {
        const $question = jQuery(`#${section_id} .item_question[data-index="${index}"]`);
        $question.find('.input_checkbox').prop('checked', false);
        $question.find('.input_radio').prop('checked', false);
        $question.find('.input_select').val('');
        $question.find('.input_text, .input_number, .input_date, textarea').val('');
        $question.find('.input_other').val('');

        if (prevAnswer.answer !== undefined && prevAnswer.answer !== null) {
            if (Array.isArray(prevAnswer.answer)) {
                $question.find('.input_checkbox').each(function() {
                    jQuery(this).prop('checked', prevAnswer.answer.includes(this.value));
                });
            } else {
                $question.find('.input_radio').each(function() {
                    jQuery(this).prop('checked', this.value === prevAnswer.answer);
                });
                $question.find('.input_select').val(prevAnswer.answer);
                $question.find('.input_text, .input_number, .input_date, textarea').val(prevAnswer.answer);
            }
        }


        $question.find('.input_other').val(prevAnswer.other || '');
    }

    // Lấy id của câu hỏi hiện tại
    const currentQuesId = listQuestions[index]?.id_ques;
    // Lấy đáp án của s01
    // const s01Answer = answeredQuestions['part1_' + listQuestions.findIndex(q => q.id_ques === 's01')]?.answer;
    const province = surveyData.centerInfo?.province;
    const center_name = surveyData.centerInfo?.center_name;
    const centerCode = surveyData.centerInfo?.center_code;
    // Danh sách các câu cần bỏ qua nếu s01 là '8.1'
    const skipIds = ['d1', 'd2', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'b1', 'b2'];
    if (centerCode === '8.1') {
        // Ẩn các phần tử có id tương ứng
        skipIds.forEach(id => {
            const $el = jQuery('#' + id);
            if ($el.length) {
                $el.addClass('hidden').css('display', 'none');
            }
        });
        // Nếu câu hiện tại nằm trong danh sách cần skip thì nhảy tới câu kế tiếp
        if (skipIds.includes(currentQuesId)) {
            let nextIndex = index + 1;
            // Tìm câu tiếp theo không nằm trong danh sách skip
            while (
                nextIndex < listQuestions.length &&
                skipIds.includes(listQuestions[nextIndex]?.id_ques)
            ) {
                nextIndex++;
            }
            // Nhảy tới câu hỏi tiếp theo phù hợp
            showQuestion(nextIndex);
            return;
        }
    }


}

function validateQuestion(index) {
    const $question = jQuery(`#${section_id} .item_question[data-index="${index}"]`);
    if ($question.hasClass('hidden')) return true;
    if (!$question.is(':visible')) return true;

    // const $inputs = $question.find('.input_requice:not(.hidden)');
    const $inputs = $question.find('.input_requice:visible');

    let isValid = true;
    let errorMessages = [];
    const questionTitle = $question.find('.input_title_question_name').val() || 'Câu hỏi không có tiêu đề';
    if ($inputs.length > 0) {
        const $checkboxes = $question.find('.input_checkbox.input_requice:checked');
        const $radio = $question.find('.input_radio.input_requice:checked');
        const $select = $question.find('.input_select.input_requice');
        const $text = $question.find('.input_text.input_requice, .input_number.input_requice, .input_date.input_requice, textarea.input_requice');
        const $otherInput = $question.find('.input_other.input_requice');

        const $checkboxes3 = $question.find('.input_checkbox.input_requice.rg2:checked');
        if ($checkboxes3.length !== 3 && $question.find('.input_checkbox.input_requice.rg2').length > 0) {
            isValid = false;
            errorMessages.push(`Vui lòng chọn <strong>chính xác 3 câu trả lời</strong>.`);
            $question.find('.input_checkbox.input_requice.rg2').addClass('error');
        }else if ($checkboxes.length === 0 && $question.find('.input_checkbox.input_requice').length > 0) {
            isValid = false;
            errorMessages.push(`Vui lòng chọn ít nhất một câu trả lời.`);
            $question.find('.input_checkbox.input_requice').addClass('error');
        } else {
            $question.find('.input_checkbox.input_requice').removeClass('error');
                // Kiểm tra các checkbox đã chọn có kèm input text và input phải được nhập
            $checkboxes.each(function () {
                const $checkbox = jQuery(this);
                const $textInput = $checkbox.closest('label').find('input[type="text"]');
                if ($textInput.length && $textInput.val().trim() === '') {
                    isValid = false;
                    $textInput.addClass('error');
                    const labelText = $checkbox.closest('label').text().trim();
                    errorMessages.push(`Trường "${labelText}" yêu cầu bạn ghi rõ thông tin.`);
                } else {
                    $textInput.removeClass('error');
                }
            });
        }
        if ($radio.length === 0 && $question.find('.input_radio.input_requice').length > 0) {
            isValid = false;
            errorMessages.push(`Vui lòng chọn câu trả lời.`);
            $question.find('.input_radio.input_requice').addClass('error');
        } else {
            $question.find('.input_radio.input_requice').removeClass('error');
        }
        if (($select.val() === '0' || $select.val() === '' || $select.val() === null) && $select.length > 0) {
            isValid = false;
            errorMessages.push(`Vui lòng chọn một giá trị.`);
            $select.addClass('error');
        } else {
            $select.removeClass('error');
        }

        const hasOther = $checkboxes.filter(function() {
            return jQuery(this).hasClass('show_input_other') || JSON.parse(jQuery(this).attr('data-value') || '[]').includes('other');
        }).length > 0 || $radio.filter(function() {
            return jQuery(this).hasClass('show_input_other') || JSON.parse(this.getAttribute('data-value') || '[]').includes('other');
        }).length > 0 || ($select.val() !== '0' && JSON.parse($select.find('option:selected').attr('data-value') || '[]').includes('other'));
        
        if (hasOther && $otherInput.val()?.trim() === '') {
            isValid = false;
            errorMessages.push(`Vui lòng nhập thông tin khác.`);
        }else if ($text.val()?.trim() === '' && $text.length > 0 ) {
            isValid = false;
            errorMessages.push(`Vui lòng điền câu trả lời.`);
            $text.addClass('error');
        } else {
            $text.removeClass('error');
        }


    }

    // Kiểm tra bảng likert
    const $likertTable = $question.find('.rating-table');
    if ($likertTable.length > 0) {
        let hasError = false;
        $likertTable.find('tbody tr').each(function() {
            const $row = jQuery(this);
            const $checked = $row.find('input[type="radio"]:checked, input[type="checkbox"]:checked');
            if ($checked.length === 0) {
                hasError = true;
                $row.find('input[type="radio"], input[type="checkbox"]').addClass('error');
            } else {
                $row.find('input[type="radio"], input[type="checkbox"]').removeClass('error');
            }
        });
        if (hasError) {
            isValid = false;
            errorMessages.push(`Vui lòng cho ý kiến đánh giá ở tất cả các dòng.`);
        }
    }


    if (!isValid) {
        const htmlContent = errorMessages.join('<br>');
        document.getElementById('customAlertContent').innerHTML = htmlContent;
        document.getElementById('customAlert').style.display = 'flex';
        // alert('Vui lòng hoàn thành các câu hỏi sau:\n' + errorMessages.join('\n'));
    }
    return isValid;
}

function validateQ1() {
    return true;
}

function sendSurveyData() {
    // Thêm trạng thái và câu hiện tại vào surveyData
    if (!surveyData.status) {
        surveyData.status = {
            current_question: currentQuestionIndex,
            section: section_id,
            state: 'in_progress'  // Mặc định là đang tiến hành
        };
    }

    let jsonString;
    try {
        jsonString = JSON.stringify(surveyData, null, 2);
        console.log('JSON Data:', jsonString);
    } catch (e) {
        console.error('JSON Stringify Error:', e.message, surveyData);
        alert('Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.');
        return false;
    }

    jQuery.ajax({
        type: 'POST',
        url: '<?php echo get_stylesheet_directory_uri() . "/modules/wp-insert-khaosat.php"; ?>',
        data: {
            survey_id: surveyData.survey_id,
            form_action: 'form_survey',
            wpnonce: '<?php echo wp_create_nonce('form_survey'); ?>',
            data: jsonString,
            current_question: currentQuestionIndex,
            section: section_id
        },
        async: false,
        success: function(response) {
            console.log('AJAX Response:', response);
            if (!response.success) {
                alert(response.message || 'Có lỗi khi lưu dữ liệu');
                return false;
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            alert('Có lỗi khi gửi dữ liệu. Vui lòng thử lại.');
            return false;
        }
    });
    return true;
}

function nextSection(currentSectionId) {
    let nextSectionId = '';
    if (currentSectionId === 'intro') {
        const consent = jQuery('input[name="consent"]:checked');
        if (!consent.length) {
            alert('Vui lòng chọn một lựa chọn.');
            return;
        }
        if (consent.val() === '1') {
            nextSectionId = 'q1';
        } else {
            nextSectionId = 'end';
        }
    } else if (currentSectionId === 'q1') {
        if (!validateQ1()) return;
        const status = jQuery('#q1 .input_status:checked');
        surveyData.q1 = {
            question: sanitizeInput(jQuery('#q1 input[name="status[question]"]').val()),
            answer: sanitizeInput(status.val()),
            code: sanitizeInput(status.attr('data-code') || '0')
        };
        if (!sendSurveyData()) return;
        nextSectionId = status.attr('data-value');
    }
    if (nextSectionId) {
        showSection(nextSectionId);
    }
}

function nextQuestion(index) {
    jQuery(".intro_before").hide();
    if (!validateQuestion(index)) return;
    const $question = jQuery(`#${section_id} .item_question[data-index="${index}"]`);
    if (!$question.hasClass('hidden')) {
        // --- KIỂM TRA ĐÁP ÁN STOP ---
        let isStop = false;
        // Radio
        if ($question.find('.input_radio.stop:checked').length > 0) {
            isStop = true;
        }
        // Checkbox
        if ($question.find('.input_checkbox.stop:checked').length > 0) {
            isStop = true;
        }
        // Select
        if ($question.find('.input_select').length > 0) {
            var $selected = $question.find('.input_select option:selected');
            if ($selected.hasClass('stop')) {
                isStop = true;
            }
        }

        // Tính toán câu hỏi tiếp theo trước
        let nextIndex = index + 1;
        const currentQuestion = listQuestions[index];
        const currentAnswer = answeredQuestions[`${section_id}_${index}`]?.answer;

        const childQuestions = listQuestions.filter(q => q.id_ques_parend === currentQuestion.id_ques);
        for (const child of childQuestions) {
            const valueSelect = child.value_select ?? [];
            const isShow = (child.display === 'ques_child_show' && (Array.isArray(currentAnswer) ? currentAnswer.includes(valueSelect) : currentAnswer === valueSelect)) ||
                          (child.display === 'ques_child_notshow' && (Array.isArray(currentAnswer) ? !currentAnswer.includes(valueSelect) : currentAnswer !== valueSelect));
            if (isShow) {
                nextIndex = listQuestions.findIndex(q => q.id_ques === child.id_ques);
                break;
            }
        }

        while (nextIndex < listQuestions.length && jQuery(`#${section_id} .item_question[data-index="${nextIndex}"]`).hasClass('hidden')) {
            nextIndex++;
        }

        if (isStop) {
            // Cập nhật trạng thái stop với câu hỏi tiếp theo
            surveyData.status = {
                current_question: nextIndex,
                section: section_id,
                state: 'stop'
            };
            // Gửi dữ liệu lần cuối
            sendSurveyData();
            // Hiển thị thông báo
            jQuery('.survey-container').hide();
            jQuery('#stop-message').show();
            return;
        }

        let answer = null, code = null, other = null;
        let province = null, center_name = null, center_code = null; you_are = null; province_value = null; // 3 field riêng

        if ($question.find('.rating-table').length > 0) {
            // Lấy dữ liệu likert
            answer = [];
            $question.find('.rating-table tbody tr').each(function() {
                let questionText = jQuery(this).find('.rating-question').text().trim();
                let value = jQuery(this).find('input[type="radio"]:checked, input[type="checkbox"]:checked').map(function() {
                    return this.value;
                }).get();
                // Nếu là radio thì chỉ lấy 1 giá trị
                if (jQuery(this).find('input[type="radio"]').length > 0) {
                    value = value.length > 0 ? value[0] : null;
                }
                answer.push({
                    question: questionText,
                    answer: value
                });
            });

        } else if ($question.find('.question_hospital_group').length > 0) {
            answer = $question.find('.input_checkbox.input_requice:checked').map((i, el) => sanitizeInput(el.value)).get();
            code = $question.find('.input_checkbox.input_requice:checked').map((i, el) => sanitizeInput(jQuery(el).attr('data-code') || '0')).get();
            other = $question.find('.input_other.input_requice').map((i, el) => sanitizeInput(jQuery(el).val())).get();

        } else if ($question.find('.input_checkbox.with_text_input').length > 0) {
            // Checkbox có input text đi kèm (ghi rõ...)
            answer = [];
            code = [];
            $question.find('.input_checkbox.with_text_input:checked').each(function () {
                const $checkbox = jQuery(this);
                const $textInput = $checkbox.closest('label').find('input[type="text"]');
                const checkboxVal = sanitizeInput($checkbox.val());
                const inputVal = $textInput.length ? sanitizeInput($textInput.val()) : '';
                if (inputVal) {
                    answer.push(`${checkboxVal} (${inputVal})`);
                } else {
                    answer.push(checkboxVal);
                }
                code.push(sanitizeInput($checkbox.attr('data-code') || '0'));
            });
            other = sanitizeInput($question.find('.input_other.input_requice').val()) || null;
        } else if ($question.find('.input_checkbox.input_requice').length > 0) {
            // Checkbox thông thường (giữ nguyên)
            answer = $question.find('.input_checkbox.input_requice:checked').map((i, el) => sanitizeInput(el.value)).get();
            code = $question.find('.input_checkbox.input_requice:checked').map((i, el) => sanitizeInput(jQuery(el).attr('data-code') || '0')).get();
            other = sanitizeInput($question.find('.input_other.input_requice').val()) || null;
        } else  if ($question.find('.input_radio.input_requice').length > 0) {
            // Radio
            if($question.find('.input_radio.input_requice.rs0a').length > 0){
                you_are = sanitizeInput($question.find('.input_radio.input_requice:checked').val());
            }else{
                answer = sanitizeInput($question.find('.input_radio.input_requice:checked').val());
                code = sanitizeInput($question.find('.input_radio.input_requice:checked').attr('data-code') || '0');
                other = sanitizeInput($question.find('.input_other.input_requice').val()) || null;
            }
        } else if ($question.find('.input_select.input_requice').length > 0) {
                // Select riêng cho tỉnh thành và trung tâm 
            if ($question.find('.input_select.input_requice.province-select').length > 0) {
                province = sanitizeInput($question.find('.input_select.input_requice.province-select option:selected').text()) || null;
                province_value = sanitizeInput($question.find('.input_select.input_requice.province-select').val()) || null;
            }else if ($question.find('.input_select.input_requice.vnvc-select').length > 0) {
                center_name = sanitizeInput($question.find('.input_select.input_requice.vnvc-select option:selected').text()) || null;
                center_code = sanitizeInput($question.find('.input_select.input_requice.vnvc-select option:selected').val()) || null;
            }else{
                // Select chung
                answer = sanitizeInput($question.find('.input_select.input_requice').val());
                code = sanitizeInput($question.find('.input_select.input_requice option:selected').attr('data-code') || '0');
                other = sanitizeInput($question.find('.input_other.input_requice').val()) || null;
            }
           
        } else {
            // Text, textarea, number, date
            answer = sanitizeInput($question.find('.input_text.input_requice, .input_number.input_requice, .input_date.input_requice, textarea.input_requice').val());
            code = '0';
            other = sanitizeInput($question.find('.input_other.input_requice').val()) || null;
        }

        if (!province && !center_name && !center_code && !you_are) {
            // Tạo object chung cho mọi loại
            const id_ques = $question.find('.input_title_question_name').data('id') || '';
            const questionData = {
                id_ques: id_ques,
                question: sanitizeInput($question.find('.input_title_question_name').val()),
                answer: answer,
                other: other,
                code: code
            };
            answeredQuestions[`${section_id}_${index}`] = questionData;
            const existingIndex = surveyData.questions.findIndex(q => q.question === questionData.question);
            if (existingIndex >= 0) {
                surveyData.questions[existingIndex] = questionData;
            } else {
                surveyData.questions.push(questionData);
            }
        }

        if (!surveyData.centerInfo) {
            surveyData.centerInfo = {};
        }
        if (province && province.trim() !== '') {
            surveyData.centerInfo.province = province.trim();
        }
        if (province_value && province_value.trim() !== '') {
            surveyData.centerInfo.province_value = province_value.trim();
        }
        if (center_name && center_name.trim() !== '') {
            surveyData.centerInfo.center_name = center_name.trim();
        }
        if (center_code && center_code.trim() !== '') {
            surveyData.centerInfo.center_code = center_code.trim();
        }
        if (you_are && you_are.trim() !== '') {
            surveyData.centerInfo.you_are = you_are.trim();
        }

        
        // Cập nhật trạng thái với câu hỏi tiếp theo
        surveyData.status = {
            current_question: nextIndex,
            section: section_id,
            state: 'in_progress'
        };
        
        // Gửi dữ liệu
        if (!sendSurveyData()) return;
        // alert(currentQuestionIndex);
        // Cập nhật UI
        questionHistory.push({section: section_id, index: currentQuestionIndex});
        currentQuestionIndex = nextIndex;
        showQuestion(currentQuestionIndex);
    }
}

function prevQuestion() {
    if (questionHistory.length === 0 || !enableBackButton) return;
    const prev = questionHistory.pop();
    section_id = prev.section;
    listQuestions = sectionQuestions[section_id] || [];
    currentQuestionIndex = prev.index;
    showSection(section_id, false);
    showQuestion(currentQuestionIndex);
}

// jQuery(document).on('change', '.input_radio, .input_checkbox', function() {
//     const $question = jQuery(this).closest('.item_question');
//     const $wrapInputOther = $question.find('.input-wrapper');
//     $wrapInputOther.remove();

//     if (jQuery(this).is(':checked')) {
//         let showOther = jQuery(this).hasClass('show_input_other') || JSON.parse(jQuery(this).attr('data-value') || '[]').includes('other');
//         if (showOther) {
//             const baseName = jQuery(this).attr('name');
//             const newName = baseName.replace('[answer]', '[other]').replace('[]', '');
//             let placeholderText = 'Vui lòng nhập lý do khác...';
//             if ($question.hasClass('wrap_itemd1')) {
//                 placeholderText = 'Vui lòng ghi rõ';
//             }
//             const $input = jQuery('<input>', {
//                 type: 'text',
//                 name: newName,
//                 class: 'dynamic-text-input input_text input_other input_requice',
//                 placeholder: placeholderText
//             });
//             const $wrapper = jQuery('<div>', { class: 'input-wrapper' });
//             $wrapper.append($input);
//             jQuery(this).parent().after($wrapper);
//         }
//     }
// });

jQuery(document).on('change', '.input_select', function() {
    const $question = jQuery(this).closest('.item_question');
    const $wrapInputOther = $question.find('.input-wrapper');
    $wrapInputOther.remove();

    const $selectedOption = jQuery(this).find('option:selected');
    const dataValue = $selectedOption.attr('data-value') || '[]';
    if (JSON.parse(dataValue).includes('other')) {
        const baseName = jQuery(this).attr('name');
        const newName = baseName.replace('[answer]', '[other]');
        const $input = jQuery('<input>', {
            type: 'text',
            name: newName,
            class: 'input_text input_select_text_other input_other input_requice',
            placeholder: 'Nhập thông tin khác'
        });
        const $wrapper = jQuery('<div>', { class: 'input-wrapper' });
        $wrapper.append($input);
        jQuery(this).after($wrapper);
    }
});

// jQuery(document).ready(function() {
//     jQuery('.input_date').datepicker({
//         maxDate: new Date(),
//         changeMonth: true,
//         changeYear: true,
//         yearRange: "-80:+0",
//         dateFormat: 'yy-mm-dd'
//     });
//     jQuery('.section_part .item_question').hide();
// });

jQuery(document).on('focus change input', '.input_select, .input_text, .input_number, .input_date, textarea', function() {
    jQuery(this).removeClass('error');
});

jQuery(document).on('change', '.input_radio, .input_checkbox', function() {
    jQuery(this).removeClass('error');
});

function getHospitalGroupAnswers($question) {
    const $groups = $question.find('.question_hospital_group .hospital_group');
    let result = [];

    $groups.each(function() {
        const $group = jQuery(this);
        const groupTitle = $group.find('.group_title strong').text().trim();
        
        // Lấy tất cả checkbox được chọn trong nhóm này
        const $checked = $group.find('.input_checkbox:checked');
        
        if ($checked.length > 0) {
            let selections = [];
            let codes = [];
            
            $checked.each(function() {
                const $checkbox = jQuery(this);
                const text = $checkbox.siblings('span').text().trim();
                selections.push(text);
                codes.push($checkbox.attr('data-code') || '0');
            });

            result.push({
                group: groupTitle,
                selections: selections,
                codes: codes
            });
        }
    });

    return {
        answer: result,
        code: result.flatMap(group => group.codes),
        other: {}
    };
}

// Sửa lại event handler cho checkbox
jQuery(document).on('change', '.input_radio, .input_checkbox', function () {
    const $input = jQuery(this);
    const $question = $input.closest('.item_question');
    const $hospitalItem = $input.closest('.hospital_item');
    const $group = $input.closest('.hospital_group');
    const isInHospitalGroup = $input.closest('.question_hospital_group').length > 0;

    // Xác định vùng cần xóa input-wrapper cũ
    if (isInHospitalGroup) {
        $hospitalItem.find('.input-wrapper').remove();
    } else {
        $question.find('.input-wrapper').remove();
    }

    // Kiểm tra xem có hiển thị ô nhập "khác" không
    if ($input.is(':checked')) {
        const showOther = $input.hasClass('show_input_other') || JSON.parse($input.attr('data-value') || '[]').includes('other');
        if (showOther) {
            const baseName = $input.attr('name');
            const newName = baseName.replace('[answer]', '[other]').replace('[]', '');
            let placeholderText = 'Vui lòng nhập lý do khác...';

            // Nếu trong nhóm bệnh viện thì gán placeholder riêng và thêm data-group
            let dataGroup = '';
            if (isInHospitalGroup) {
                placeholderText = 'Vui lòng nhập chi tiết...';
                dataGroup = $group.find('.group_title strong').text().trim();
            } else if ($question.hasClass('wrap_itemd1')) {
                placeholderText = 'Vui lòng ghi rõ';
            }

            const $textInput = jQuery('<input>', {
                type: 'text',
                name: newName,
                class: 'dynamic-text-input input_text input_other input_requice',
                placeholder: placeholderText
            });

            // Thêm data-group nếu có
            if (dataGroup) {
                $textInput.attr('data-group', dataGroup);
            }

            const $wrapper = jQuery('<div>', {
                class: 'input-wrapper'
            });

            if (dataGroup) {
                $wrapper.attr('data-group', dataGroup);
            }

            $wrapper.append($textInput);
            $input.parent().after($wrapper);
        }
    }
});



jQuery(document).ready(function () {
    jQuery('.item_question  input.input_checkbox').on('change', function () {
        const $currentGroup = jQuery(this).closest('.item_question');
        const $checkboxes = $currentGroup.find('input.input_checkbox');
        const $noneOption = $checkboxes.filter('[data-condition="0"]');
        const $otherOptions = $checkboxes.filter('[data-condition!="0"]');

        if (jQuery(this).attr('data-condition') === '0') {
            if (jQuery(this).is(':checked')) {
                $otherOptions.prop('checked', false).attr('disabled', true).addClass('disabled');
            } else {
                $otherOptions.removeAttr('disabled').removeClass('disabled');
            }
        } else {
            if ($otherOptions.filter(':checked').length > 0) {
                $noneOption.prop('checked', false).attr('disabled', true).addClass('disabled');
            } else {
                $noneOption.removeAttr('disabled').removeClass('disabled');
            }
        }
    });
});

function updateD2TitleFromD1() {
    // Duyệt tất cả checkbox
    jQuery('#d1 .input_checkbox').each(function () {
        const $cb = jQuery(this);
        const label = $cb.val();
        const dataVal = $cb.data('value'); // ví dụ: 'd2_a'

        if (!dataVal) return;

        const $d2Block = jQuery(`#${dataVal}`);

        if ($cb.is(':checked')) {
            // Lấy giá trị text input nếu có
            const inputText = $cb.closest('label').find('input[type="text"]').val()?.trim() || '';
            const finalLabel = inputText ? `${label} (${inputText})` : label;
            const questionText = `D2. Lý do vì sao anh chị cho bé khám ở ${finalLabel} ?`;

            $d2Block.removeClass('hidden').addClass('active');
            $d2Block.find('.title_question_name').text(questionText);
            $d2Block.find('.input_title_question_name').val(questionText);
        } else {
            $d2Block.removeClass('active').addClass('hidden');
            $d2Block.find('.title_question_name').text('[chưa chọn]');
            $d2Block.find('.input_title_question_name').val('');
        }
    });
}

// Gọi lại mỗi khi chọn checkbox hoặc nhập input
jQuery(document).on('change', '#d1 .input_checkbox', updateD2TitleFromD1);
jQuery(document).on('input', '#d1 input[type="text"]', updateD2TitleFromD1);


jQuery(document).ready(function() {
    jQuery('.wrap_itemc2 .rc2.input_requice').on('click', function() {
        let dataVal = jQuery(this).data('value') || '';
        let values = Array.isArray(dataVal) ? dataVal : (typeof dataVal === 'string' ? dataVal.split(',') : []);
        values = values.map(v => v.trim());

        if (values.includes('c3')) {
            jQuery('.wrap_itemc3').removeClass('hidden');
            jQuery('.wrap_itemc4').removeClass('hidden'); // Mở cả c4 nếu cần
        } else {
            jQuery('.wrap_itemc3').addClass('hidden');
            jQuery('.wrap_itemc4').addClass('hidden'); // Ẩn c4 khi c3 ẩn
        }
    });
});

document.querySelectorAll('td.rating-option').forEach(td => {
    td.addEventListener('click', function(e) {
      // Ngăn việc click trực tiếp vào checkbox để tránh toggle 2 lần
      if (e.target.tagName.toLowerCase() !== 'input') {
        const checkbox = this.querySelector('input[type="checkbox"]');
        if (checkbox) {
          checkbox.checked = !checkbox.checked;
        }
      }
    });
  });
  document.querySelectorAll('td.rating-option').forEach(td => {
    td.addEventListener('click', function(e) {
      if (e.target.tagName.toLowerCase() !== 'input') {
        const radio = this.querySelector('input[type="radio"]');
        if (radio) {
          radio.checked = true;
          radio.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    });
  });
  
</script>
<style>
.div_header{
    display: none;
}
/* CSS Styles */
.survey-container {
max-width: 800px;
margin: 0 auto;
padding: 10px;
}

.form-section {
margin-bottom: 30px;
padding: 20px;
background: #f9f9f9;
border-radius: 8px;
display: none;
}

.form-section.active {
display: block;
}
.question-container.active .form-section{
display: block;
}

.form-group {
margin-bottom: 15px;
}

label {
display: block;
margin-bottom: 5px;
font-weight: bold;
}

input[type="text"],
input[type="tel"],
input[type="email"],
select,
textarea {
width: 100%;
padding: 5px;
border: 1px solid #ddd;
border-radius: 6px;
}
.radio-group {
/* display: flex;
gap: 20px; */
}

.radio-option {
display: flex;
align-items: center;
gap: 8px;
}

.radio-option input[type="radio"] {
accent-color: #2a388f; 
width: 18px;
height: 18px;
}
input[type="number"] {
width: 100%;
padding: 10px;
border: 1px solid #ddd;
border-radius: 4px;
}
.required-field:invalid {
border-color: #dc3545;
}

.required-field:invalid:focus {
outline: none;
box-shadow: 0 0 0 0.2rem rgba(220,53,69,0.25);
}
input[type="date"] {
width: 100%;
padding: 10px;
border: 1px solid #ddd;
border-radius: 4px;
}

.options label {
display: block;
margin: 5px 0;
}

.rating-table {
width: 100%;
border-collapse: collapse;
margin: 15px 0;
}

.rating-table th, .rating-table td {
padding: 10px;
border: 1px solid #ddd;
text-align: center;
}

.rating-table .rating-question {
text-align: left;
}

.navigation-buttons {
display: flex;
justify-content: space-between;
margin-top: 20px;
}

.btn {
padding: 10px 20px;
border: none;
border-radius: 4px;
cursor: pointer;
}

.btn-prev {
background: #6c757d;
color: white;
}

.btn-next, .btn-submit {
background: #007bff;
color: white;
}

.btn:disabled {
opacity: 0.5;
cursor: not-allowed;
}

.error {
border-color: #dc3545 !important;
}

.error-message {
color: #dc3545;
font-size: 0.85em;
margin-top: 5px;
}

.progress-container {
margin-bottom: 20px;
}

.progress-bar {
height: 5px;
background: #007bff;
width: 0%;
transition: width 0.3s;
}

.hidden {
display: none;
}
/* Phần nội dung giới thiệu đầu */
.fz_16 {
    font-size: 16px;
}
.cl_main {
    color: #2a388f; /* hoặc màu bạn dùng làm chủ đạo */
}
.mb_30 {
    margin-bottom: 30px;
}
.text-center {
    text-align: center;
}

/* Phần chính câu hỏi */
.section_part {
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    margin-bottom: 30px;
}

/* Nút điều hướng */
.wrap_button {
    margin-top: 20px;
    text-align: center;
}
.back_button, .next_button {
    background-color: #2a388f;
    color: white;
    border: none;
    padding: 12px 25px;
    font-size: 16px;
    border-radius: 8px;
    cursor: pointer;
    /*margin: 0 10px;*/
    transition: background-color 0.3s;
}
.back_button:hover, .next_button:hover {
    background-color: #005f8d;
}

/* Thông báo dừng bài khảo sát */
#stop-message {
    background-color: #fff3f3;
    border: 1px solid #ffcccc;
    padding: 20px;
    margin: 20px;
    border-radius: 10px;
}

/* Cảm ơn sau khi hoàn thành */
#thankYouMessage {
    text-align: center;
    background-color: #f0fff4;
    border: 1px solid #b2f2bb;
    padding: 20px;
    margin: 20px;
    border-radius: 10px;
    font-weight: bold;
    color: #2b8a3e;
}
@media (max-width:767px){
  #a7 .rating-table tr:nth-child(2)>th,
  #a7 .rating-table tr:nth-child(3)>th{
        min-width: 60px;
        width: 60px;
    }
}
.custom-alert {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
}

.custom-alert-box {
  background: #fff;
  padding: 20px 25px;
  border-radius: 8px;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
  max-width: 500px;
  width: 90%;
  font-family: Arial, sans-serif;
  text-align: left;
  animation: fadeInScale 0.3s ease;
}

.custom-alert-content {
  font-size: 16px;
  color: #333;
  margin-bottom: 15px;
}

.custom-alert-content strong {
  color: #e74c3c;
}

.custom-alert-close {
  padding: 8px 16px;
  background: #2a388f;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.custom-alert-close:hover {
  background: #2a388f;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}


table {
  border: 1px solid #ccc;
  border-collapse: collapse;
  margin: 0;
  padding: 0;
  width: 100%;
  table-layout: fixed;
}
.top-company {
    padding-top: 15px;
}
@media screen and (max-width: 600px) {
    body .rating-table table {
    border: 0;
  }
  
  body .rating-table table thead {
    border: none;
    clip: rect(0 0 0 0);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
  }
  
  body .rating-table table tr {
    display: block;
  }
  
  body .rating-table table td {
    font-weight: 600;
    border-bottom: 1px solid #ddd;
    display: block;
    text-align: right !important;
    padding: 5px 25px 5px 5px !important;
  }
  body .rating-table table td:first-child{
    text-align: left !important;
  }
  body .rating-table table td::before {
    /*
    * aria-label has no advantage, it won't be read inside a table
    content: attr(aria-label);
    */
    content: attr(data-label);
    float: left;
    font-weight: 500;
  }
  
  body .rating-table table td:last-child {
    border-bottom: 0;
  }
  #d1 .row_answer label{
    display: block;
  }
  #d1 .row_answer label input{
    margin-right: 10px;
  }
}
@media screen and (max-width: 767px) {
    .top-company {
        display: block;
        position: static;
        background: #2a388f !important;
        top: 0;
        left: 0;
    }
}
</style>

<?php //get_footer(); ?>
</div><!-- /.inner-container -->
