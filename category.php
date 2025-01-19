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

	

}
