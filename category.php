<?php

namespace helper;
class category {

    /**
     * 
     * @param string $slug
     * @param string $taxonomy posts | game | product
     * @return \mod\category\ob\o_category
     */
    public static function find_category_by_slug($slug, $taxonomy) {
        $key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__ . '-' . $slug . '_' . $taxonomy;
        $result = null;
        if (\helper\memcached::get($key)) {
            $result = \helper\memcached::get($key);
        } else {
            $category_list = \mod\category\mysql\m_category::get_me()->find(array('slug' => $slug, 'taxonomy' => $taxonomy));
            if ($category_list != null && $category_list[0] != null) {
                $result = $category_list[0];
            } else {
                $result = null;
            }
            if ($result != null) {
                \helper\memcached::set($key, $result, 60 * 60 * 24);
            }
        }
        return $result;
    }

    public static function find_category($id) {
        $key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__ . '-' . $id;
        $result = null;
        if (\helper\memcached::get($key)) {
            $result = \helper\memcached::get($key);
        } else {
            $result = \mod\category\mysql\m_category::get_me()->find_by_id($id);
            if ($result != null && count($result) > 0) {
                \helper\memcached::set($key, $result, 60 * 60 * 24);
            }
        }
        return $result;
    }

    /**
     * 
     * @param type $taxonomy
     * @param type $page
     * @param type $limit
     * @param type $field_order
     * @param type $order_type
     * @param type $ar_where
     * @param array $ar_like
     * @param array $ar_notequal
     * @return \mod\category\ob\o_category[]
     */
    public static function paging($taxonomy = 'posts', $page = 1, $limit = 10, $field_order = 'id', $order_type = 'desc', $ar_where = array(), $ar_like = array(), $ar_notequal = array()) {
        $key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__ . '-' . $taxonomy . '-' . $page . '-' . $limit . '-' . $field_order . '-' . $order_type . '-' . serialize($ar_where) . '-' . serialize($ar_like) . '-' . serialize($ar_notequal);
        $result = null;
        if (\helper\memcached::get($key)) {
            $result = \helper\memcached::get($key);
        } else {
            $ar_where['taxonomy'] = $taxonomy;
            $result = \mod\category\mysql\m_category::get_me()->paging($field_order, $order_type, $page, $limit, $ar_where, $ar_like, $ar_notequal);
            if ($result != null && count($result) > 0) {
                \helper\memcached::set($key, $result, 60 * 60 * 24);
            }
        }
        return $result;
    }

    public static function find_by_taxonomy($taxonomy = 'posts', $field_order = 'name', $order_type = 'asc') {
        $key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__ . '-' . $taxonomy . '-' . $field_order . '-' . $order_type;
        $result = null;
        if (\helper\memcached::get($key)) {
            $result = \helper\memcached::get($key);
        } else {
            $where = array('taxonomy' => $taxonomy);
            $ar_like = array();
            $ar_notequal = array();
            $result = \mod\category\mysql\m_category::get_me()->find($where, $ar_like, $ar_notequal, $field_order, $order_type);
            if ($result != null && count($result) > 0) {
                \helper\memcached::set($key, $result, 60 * 60 * 24);
            }
        }
        return $result;
    }

    public static function find_child_category($parent_id, $field_order = 'priority', $order_type = 'asc') {
        $result = \mod\category\mysql\m_category::get_me()->find(array('parent' => $parent_id), array(), array(), $field_order, $order_type);
        return $result;
    }

    /**
     * 
     * @param type $taxonomy
     * @return type
     */
    public static function get_category_directory_style($taxonomy) {
        $mapper = \mod\category\mysql\m_category::get_me();
        $all_category = $mapper->find_by('taxonomy', $taxonomy);
        return self::to_category_directory_style($all_category);
    }

    /**
     * 
     * @param \mod\category\ob\o_category[] $all_category
     * @return type
     */
    public static function to_category_directory_style($all_category) {
        $category_parent_root = array();
        $category_menu_root = array();
        foreach ($all_category as $category_item) {
            $category_parent_root[$category_item->id] = $category_item;
            $category_item->child_items = array();
            $category_item->child = '';
        }
        foreach ($all_category as $category_item) {
            if ($category_item->parent != 0 && isset($category_parent_root[$category_item->parent])) {
                $category_parent = $category_parent_root[$category_item->parent];
                $category_parent->child_items[] = $category_item;
            } else {
                $category_item->set_parent(0);
                $category_menu_root[] = $category_item;
            }
        }
        return $category_menu_root;
    }

    /**
     * 
     * @param type $taxonomy
     * @return type
     */
    public static function get_filter_directory_style($taxonomy) {
        $mapper = \mod\category\mysql\m_category::get_me();
        $ar_where = array('taxonomy' => $taxonomy);
        $ar_notequal = array();
        $ar_like = array();
        $field_order = 'priority';
        $order_type = 'asc';
        $all_category = $mapper->find($ar_where, $ar_like, $ar_notequal, $field_order, $order_type); //$mapper->find_by('taxonomy', $taxonomy);
        return self::to_category_directory_style($all_category);
    }

    public static function get_filter_group($taxonomy = 'product') {
        $key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__;
        $result = "";
        if (\helper\memcached::get($key)) {
            $result = \helper\memcached::get($key);
        } else {
            $category_root = self::get_filter_directory_style($taxonomy);
            
              
            if ($category_root) { 
                 $filter_group_list = array();
                foreach ($category_root as $category) {
                    $cat_meta = json_decode($category->metadata);
                    if ($cat_meta->display == 'yes' && $cat_meta->filter == 'yes') {
                        $filter_group_list[] = $category;
                    }
                }
       
                if ($filter_group_list) { 
                    foreach ($filter_group_list as $filter_group) {


if ($filter_group->id == '43' && 1==2) {
						$result .= "<li class='filter-item filter-item-hang'>";
                        $result .= " <div class='arrow-filter'></div>";
                        $result .= '  <div class="filter-item__title"><div class="arrow-filter"></div> <span>' . $filter_group->name . '</span></div>';
                        // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        $result .= ' <div class="filter-option" style="display: none;"><div><div class="filter-childs">';
                        $result .= self::get_listcheckbox_filter_item_hang($filter_group->child_items);
                        $result .= '</div><div class="choosedfilter" id="choosedfilter"></div></div</div';
                        $result .= "</li>";
} else {
						$result .= "<li class='filter-item'>";
                        $result .= " <div class='arrow-filter'></div>";
                        $result .= '  <div class="filter-item__title"><div class="arrow-filter"></div> <span>' . $filter_group->name . '</span></div>';
                        // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        $result .= ' <div class="filter-option" style="display: none;"><div><div class="filter-childs">';
                        $result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        $result .= '</div><div class="choosedfilter" id="choosedfilter"></div></div</div';
                        $result .= "</li>";
}

						
                        //dropdown b? l?c
                        //$result .='<ul id="menu-menu-dienthoai" class="menu">';
						
                        // $result .= "<li class='menu-item menu-item-type-taxonomy menu-item-object-product_cat current-menu-item'>";
                        // $result .= '<span class="ex_hien">' . $filter_group->name . '</span>';
                        // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        // $result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        // $result .= '</div';
                        // $result .= "</li>";
						
						// $result .= "<li class='filter-item'>";
                        // $result .= " <div class='arrow-filter'></div>";
                        // $result .= '  <div class="filter-item__title"><div class="arrow-filter"></div> <span>' . $filter_group->name . '</span></div>';
                        // // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        // $result .= ' <div class="filter-option" style="display: none;"><div><div class="filter-childs">';
                        // $result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        // $result .= '</div><div class="choosedfilter" id="choosedfilter"></div></div</div';
                        // $result .= "</li>";
						
                        //$result .= '</ul>';
                        //$result .= '<span class="ex_hien">' . $filter_group->name . '</span>';
                        //$result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        //hi?n th? b? l?c ko danh sách
                    } 
                    //
                    if ($result != null) {
                        \helper\memcached::set($key, $result, 60 * 60 * 24);
                    }
                }
            } else {
                $result = '';
            }
        }
        return $result;
    }

    private static function get_listcheckbox_filter_item($category_child_items, $object_class = 'filter_box', $lvl = 0) {

        $des = "";
        foreach ($category_child_items as $child_items) {
            $cat_meta = json_decode($child_items->metadata);
            if ($cat_meta->display == 'yes') {
                $des .= '<span style="float:left; margin-left:' . ($lvl * 20) . 'px" data-id="filter_'.$child_items->id.'" class="btn filter ' . $child_items->slug . '"><input id="filter_'.$child_items->id.'" class="' . $object_class . '"  data-name="'.$child_items->name.'" value="' . $child_items->slug . '" type="checkbox" />' . $child_items->name . '</span>';
                if ($child_items->child_items != null) {
                    $des .= self::get_listcheckbox_filter_item($child_items->child_items, $object_class, $lvl + 1);
                }
            }
        }
        return $des;
    }
	
	public static function get_filter_group_show_total($taxonomy = 'product') {
        $key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__;
        $result = "";
        if (\helper\memcached::get($key)) {
            $result = \helper\memcached::get($key);
        } else {
            $category_root = self::get_filter_directory_style($taxonomy);
            
              
            if ($category_root) { 
                 $filter_group_list = array();
                foreach ($category_root as $category) {
                    $cat_meta = json_decode($category->metadata);
                    if ($cat_meta->display == 'yes' && $cat_meta->filter == 'yes') {
                        $filter_group_list[] = $category;
                    }
                }
       
                if ($filter_group_list) { 
                    foreach ($filter_group_list as $filter_group) {
if ($filter_group->id == '43' && 1==2) {
                        //dropdown b? l?c
                        //$result .='<ul id="menu-menu-dienthoai" class="menu">';
                        $result .= "<div class='show-total-item count-item count-item-hang'>";
                        $result .= '<p class="show-total-txt">' . $filter_group->name . '</p>';
                        $result .='<div class="filter-list-hang">';
                        // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        $result .= self::get_listcheckbox_filter_item_hang($filter_group->child_items);
                        $result .= "</div>";
                        $result .= "</div>";
                        //$result .= '</ul>';
                        //$result .= '<span class="ex_hien">' . $filter_group->name . '</span>';
                        //$result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        //hi?n th? b? l?c ko danh s�ch
}else{
	                        //dropdown b? l?c
                        //$result .='<ul id="menu-menu-dienthoai" class="menu">';
                        $result .= "<div class='show-total-item count-item'>";
                        $result .= '<p class="show-total-txt">' . $filter_group->name . '</p>';
                        $result .='<div class="filter-list">';
                        // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        $result .= self::get_listcheckbox_filter_item_show_total($filter_group->child_items);
                        $result .= "</div>";
                        $result .= "</div>";
                        //$result .= '</ul>';
                        //$result .= '<span class="ex_hien">' . $filter_group->name . '</span>';
                        //$result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        //hi?n th? b? l?c ko danh s�ch
	
}
                        // //dropdown b? l?c
                        // //$result .='<ul id="menu-menu-dienthoai" class="menu">';
                        // $result .= "<div class='show-total-item count-item'>";
                        // $result .= '<p class="show-total-txt">' . $filter_group->name . '</p>';
                        // $result .='<div class="filter-list">';
                        // // $result .= ' <div class="ex_sublist" style="display: none;"><button type="button" class="ex_close">X</button>';
                        // $result .= self::get_listcheckbox_filter_item_show_total($filter_group->child_items);
                        // $result .= "</div>";
                        // $result .= "</div>";
                        // //$result .= '</ul>';
                        // //$result .= '<span class="ex_hien">' . $filter_group->name . '</span>';
                        // //$result .= self::get_listcheckbox_filter_item($filter_group->child_items);
                        // //hi?n th? b? l?c ko danh s�ch
                    } 
                    //
                    if ($result != null) {
                        \helper\memcached::set($key, $result, 60 * 60 * 24);
                    }
                }
            } else {
                $result = '';
            }
        }
        return $result;
    }
    private static function get_listcheckbox_filter_item_show_total($category_child_items, $object_class = 'filter_box', $lvl = 0) {

        $des = "";
        foreach ($category_child_items as $child_items) {
            $cat_meta = json_decode($child_items->metadata);
            if ($cat_meta->display == 'yes') {
                $des .= '<span style="float:left; margin-left:' . ($lvl * 20) . 'px" data-id="filter_'.$child_items->id.'_show_total" class="btn filter ' . $child_items->slug . '"><input id="filter_'.$child_items->id.'_show_total" data-name="'.$child_items->name.'" value="' . $child_items->slug . '_show_total" type="checkbox" />' . $child_items->name . '</span>';
                if ($child_items->child_items != null) {
                    $des .= self::get_listcheckbox_filter_item_show_total($child_items->child_items, $object_class, $lvl + 1);
                }
            }
        }
        return $des;
    }
	private static function get_listcheckbox_filter_item_hang($category_child_items, $object_class = 'filter_box', $lvl = 0) {

        $des = "";
        foreach ($category_child_items as $child_items) {
            $cat_meta = json_decode($child_items->metadata);
			$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            if ($cat_meta->display == 'yes') {
                $des .= '<a style="float:left; margin-left:' . ($lvl * 20) . 'px" id="filter_'.$child_items->id.'" class="' . $object_class . ' btn" href="'. $uri . '?filter='. $child_items->slug . '">' . $child_items->name . '</a>';
                if ($child_items->child_items != null) {
                    $des .= self::get_listcheckbox_filter_item_hang($child_items->child_items, $object_class, $lvl + 1);
                }
            }
        }
        return $des;
    }

/**
* Lấy tất cả danh mục có chứa image.
*
* @return array Mảng chứa danh mục có image. NMT
*/
public static function get_categories_with_image() {
    // Lấy mapper cho category (để truy vấn database)
    $mapper = \mod\category\mysql\m_category::get_me();
    // Điều kiện lọc: chỉ lấy danh mục có image không NULL và không rỗng
    $ar_where = array();
    $ar_like = array();
    $ar_notequal = array();
    // Điều kiện SQL cho image
    $ar_where[] = "image IS NOT NULL AND image != ''"; // Lọc các bản ghi có image không phải NULL và không phải rỗng
    $field_order = 'priority'; // Sắp xếp theo priority
    $order_type = 'asc'; // Theo thứ tự tăng dần
    // Truy vấn danh mục
    $categories_with_image = $mapper->find($ar_where, $ar_like, $ar_notequal, $field_order, $order_type);
    // Kiểm tra và trả về kết quả
    return !empty($categories_with_image) ? $categories_with_image : [];
}


public static function get_filter_group_nmt($taxonomy = 'product') {
	$key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__;
	$result = "";
	if (\helper\memcached::get($key)) {
		$result = \helper\memcached::get($key);
	} else {
		$category_root = self::get_filter_directory_style($taxonomy);
		if ($category_root) { 
			 $filter_group_list = array();
			foreach ($category_root as $category) {
				$cat_meta = json_decode($category->metadata);
				if ($cat_meta->display == 'yes' && $cat_meta->filter == 'yes') {
					$filter_group_list[] = $category;
				}
			}
			if ($filter_group_list) { 
				$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
				$host = $_SERVER['HTTP_HOST'];
				$path = $_SERVER['REQUEST_URI'];
				$url = $protocol . $host . $path;
				$path = parse_url($url, PHP_URL_PATH);
				$slug = trim($path, '/');
				
				foreach ($filter_group_list as $filter_group) {
					if ($filter_group->id == '43' && !in_array($slug, ['laptop-moi', 'laptop-cu'])) {
						$result .= "<li class='filter-item filter-item-hang nmt ".$filter_group->slug."'>";
						$result .= " <div class='arrow-filter'></div>";
						$result .= '  <div class="filter-item__title"><div class="arrow-filter"></div> <span>' . $filter_group->name . '</span></div>';
						$result .= ' <div class="filter-option" style="display: none;"><div><div class="filter-childs">';
						$result .= self::get_listcheckbox_filter_item_hang_nmt($filter_group->child_items);
						$result .= '</div><div class="choosedfilter" id="choosedfilter"></div></div</div';
						$result .= "</li>";
					} else {
						$result .= "<li class='filter-item kkkk ".$filter_group->slug ."'>";
						$result .= " <div class='arrow-filter'></div>";
						$result .= '  <div class="filter-item__title"><div class="arrow-filter"></div> <span>' . $filter_group->name . '</span></div>';
						$result .= ' <div class="filter-option" style="display: none;"><div><div class="filter-childs">';
						$result .= self::get_listcheckbox_filter_item_nmt($filter_group->child_items);
						$result .= '</div><div class="choosedfilter" id="choosedfilter"></div></div</div';
						$result .= "</li>";
					}
				} 
				if ($result != null) {
					\helper\memcached::set($key, $result, 60 * 60 * 24);
				}
			}
		} else {
			$result = '';
		}
	}
	return $result;
}
public static function get_filter_group_show_total_nmt($taxonomy = 'product') {
	$key = serialize(load_url()->domain_url()) . '-' . __CLASS__ . '-' . __FUNCTION__;
	$result = "";
	if (\helper\memcached::get($key)) {
		$result = \helper\memcached::get($key);
	} else {
		$category_root = self::get_filter_directory_style($taxonomy);
		
		  
		if ($category_root) { 
			 $filter_group_list = array();
			foreach ($category_root as $category) {
				$cat_meta = json_decode($category->metadata);
				if ($cat_meta->display == 'yes' && $cat_meta->filter == 'yes') {
					$filter_group_list[] = $category;
				}
			}
			if ($filter_group_list) { 
				$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
				$host = $_SERVER['HTTP_HOST'];
				$path = $_SERVER['REQUEST_URI'];
				$url = $protocol . $host . $path;
				$path = parse_url($url, PHP_URL_PATH);
				$slug = trim($path, '/');
				foreach ($filter_group_list as $filter_group) {
					if ($filter_group->id == '43' && !in_array($slug, ['laptop-moi', 'laptop-cu'])) {
						$result .= "<div class='show-total-item count-item count-item-hang ". $filter_group->slug ."'>";
						$result .= '<p class="show-total-txt">' . $filter_group->name . '</p>';
						$result .='<div class="filter-list-hang">';
						$result .= self::get_listcheckbox_filter_item_hang_nmt($filter_group->child_items);
						$result .= "</div>";
						$result .= "</div>";
					}else{
						$result .= "<div class='show-total-item count-item ". $filter_group->slug ."'>";
						$result .= '<p class="show-total-txt">' . $filter_group->name . '</p>';
						$result .='<div class="filter-list">';
						$result .= self::get_listcheckbox_filter_item_show_total_nmt($filter_group->child_items);
						$result .= "</div>";
						$result .= "</div>";
					}
					} 
				if ($result != null) {
					\helper\memcached::set($key, $result, 60 * 60 * 24);
				}
			}
		} else {
			$result = '';
		}
	}
	return $result;
}
private static function get_listcheckbox_filter_item_hang_nmt($category_child_items, $object_class = 'filter_box', $lvl = 0) {
    $des = "";
    foreach ($category_child_items as $child_items) {
        $cat_meta = json_decode($child_items->metadata);
        // Lấy đường dẫn hiện tại
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        // Kiểm tra nếu trường 'display' trong metadata là 'yes'
        if ($cat_meta->display == 'yes') {
            // Kiểm tra nếu danh mục có trường image và thêm thẻ <img> nếu có
            $image_html = '';
            if (!empty($child_items->image)) {
                $image_html = '<img src="' . htmlspecialchars($child_items->image) . '" alt="' . htmlspecialchars($child_items->name) . '" style="margin-right: 10px; max-width: 50px; vertical-align: middle;" />';
            }
            // Tạo thẻ <a> với hình ảnh và tên danh mục
            $des .= '<a style="float:left; margin-left:' . ($lvl * 20) . 'px" id="filter_'.$child_items->id.'" class="' . $object_class . ' btn box-quicklink__item" href="'. $child_items->slug . '">';
            // Thêm hình ảnh nếu có, rồi đến tên danh mục
            if (!empty($image_html)) {
				 $des .='<i class="quick-link-icon"></i>';
                $des .= $image_html; // Chỉ hiển thị hình ảnh
				$des .= '<span class="text">'.$child_items->name.'</span>';
            } else {
                $des .= $child_items->name; // Hiển thị văn bản nếu không có hình ảnh
            }
            $des .= '</a>';
            // Nếu danh mục có danh mục con, đệ quy gọi lại chính nó
            if ($child_items->child_items != null) {
                // $des .= self::get_listcheckbox_filter_item_hang_nmt($child_items->child_items, $object_class, $lvl + 1);
            }
        }
    }
    return $des;
}

private static function get_listcheckbox_filter_item_show_total_nmt($category_child_items, $object_class = 'filter_box', $lvl = 0) {
    $des = "";
    foreach ($category_child_items as $child_items) {
        $cat_meta = json_decode($child_items->metadata);
        if ($cat_meta->display == 'yes') {
            // Kiểm tra nếu có hình ảnh
            $image_html = '';
            if (!empty($child_items->image)) {
                // Nếu có hình ảnh, chỉ hiển thị hình ảnh, không hiển thị văn bản
                $image_html = '<img src="' . htmlspecialchars($child_items->image) . '" alt="' . htmlspecialchars($child_items->name) . '" style="margin-right: 10px; max-width: 50px; vertical-align: middle;" />';
            }
            // Thêm thẻ <span> và hiển thị hình ảnh nếu có, hoặc văn bản nếu không có
            $des .= '<span style="float:left; margin-left:' . ($lvl * 20) . 'px" data-id="filter_'.$child_items->id.'_show_total" class="btn filter ' . $child_items->slug . ' box-quicklink__item">';
            $des .= '<input id="filter_'.$child_items->id.'_show_total" data-name="'.$child_items->name.'" value="' . $child_items->slug . '_show_total" type="checkbox" />';
            if (!empty($image_html)) {
                // Hiển thị hình ảnh nếu có
				$des .='<i class="quick-link-icon"></i>';
                $des .= $image_html;
				$des .= '<span class="text">'.$child_items->name.'</span>';
            } else {
                // Nếu không có hình ảnh, hiển thị tên danh mục
                $des .= $child_items->name;
            }
            $des .= '</span>';
            // Nếu danh mục có các danh mục con, đệ quy gọi lại chính nó
            if ($child_items->child_items != null) {
                // $des .= self::get_listcheckbox_filter_item_show_total_nmt($child_items->child_items, $object_class, $lvl + 1);
            }
        }
    }
    return $des;
}

private static function get_listcheckbox_filter_item_nmt($category_child_items, $object_class = 'filter_box', $lvl = 0) {
    $des = "";
    foreach ($category_child_items as $child_items) {
        $cat_meta = json_decode($child_items->metadata);
        if ($cat_meta->display == 'yes') {
            // Kiểm tra nếu có hình ảnh
            $image_html = '';
            if (!empty($child_items->image)) {
                // Nếu có hình ảnh, chỉ hiển thị hình ảnh, không hiển thị văn bản
                $image_html = '<img src="' . htmlspecialchars($child_items->image) . '" alt="' . htmlspecialchars($child_items->name) . '" style="margin-right: 10px; max-width: 50px; vertical-align: middle;" />';
            }
            // Thêm thẻ <span> và hiển thị hình ảnh nếu có, hoặc văn bản nếu không có
            $des .= '<span style="float:left; margin-left:' . ($lvl * 20) . 'px" data-id="filter_'.$child_items->id.'" class="btn filter ' . $child_items->slug . ' box-quicklink__item">';
            $des .= '<input id="filter_'.$child_items->id.'" class="' . $object_class . '" data-name="'.$child_items->name.'" value="' . $child_items->slug . '" type="checkbox" />';
            if (!empty($image_html)) {
                // Nếu có hình ảnh, chỉ hiển thị hình ảnh
				$des .='<i class="quick-link-icon"></i>';
				$des .= $image_html;
                $des .= '<span class="text">'.$child_items->name.'</span>';
            } else {
                // Nếu không có hình ảnh, hiển thị tên danh mục
                $des .= $child_items->name;
            }
            $des .= '</span>';
            // Nếu danh mục có các danh mục con, đệ quy gọi lại chính nó
            if ($child_items->child_items != null) {
                // $des .= self::get_listcheckbox_filter_item($child_items->child_items, $object_class, $lvl + 1);
            }
        }
    }
    return $des;
}


}
